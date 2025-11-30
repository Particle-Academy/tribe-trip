<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\UsageLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service for generating invoices from usage data.
 *
 * Aggregates billable usage logs for a billing period and generates invoices.
 */
class InvoiceGenerationService
{
    /**
     * Generate an invoice for a user for a specific billing period.
     *
     * Collects all billable usage logs within the period that haven't been
     * invoiced yet, creates line items from them, and calculates totals.
     */
    public function generateForUser(
        User $user,
        Carbon $periodStart,
        Carbon $periodEnd,
        ?int $generatedBy = null
    ): ?Invoice {
        // Get uninvoiced billable usage logs for the period
        $usageLogs = $this->getUninvoicedUsageLogs($user, $periodStart, $periodEnd);

        // No usage to invoice
        if ($usageLogs->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($user, $periodStart, $periodEnd, $usageLogs, $generatedBy) {
            // Create the invoice
            $invoice = Invoice::create([
                'user_id' => $user->id,
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'billing_period_start' => $periodStart->toDateString(),
                'billing_period_end' => $periodEnd->toDateString(),
                'status' => InvoiceStatus::Draft,
                'due_date' => $periodEnd->copy()->addDays(30)->toDateString(),
                'generated_by' => $generatedBy,
            ]);

            // Create line items from usage logs
            $subtotal = 0;
            foreach ($usageLogs as $usageLog) {
                $item = InvoiceItem::createFromUsageLog($usageLog);
                $item->invoice_id = $invoice->id;
                $item->save();

                $subtotal += $item->amount;
            }

            // Update totals
            $invoice->update([
                'subtotal' => $subtotal,
                'total' => $subtotal,
            ]);

            return $invoice->fresh(['items', 'user']);
        });
    }

    /**
     * Generate invoices for all users with billable usage in a period.
     *
     * Returns a collection of generated invoices.
     *
     * @return Collection<Invoice>
     */
    public function generateForAllUsers(
        Carbon $periodStart,
        Carbon $periodEnd,
        ?int $generatedBy = null
    ): Collection {
        // Get all users with uninvoiced billable usage in the period
        $userIds = UsageLog::query()
            ->billable()
            ->whereDoesntHave('invoiceItem')
            ->whereBetween('checked_in_at', [$periodStart, $periodEnd])
            ->distinct()
            ->pluck('user_id');

        $invoices = collect();

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (! $user) {
                continue;
            }

            $invoice = $this->generateForUser($user, $periodStart, $periodEnd, $generatedBy);
            if ($invoice) {
                $invoices->push($invoice);
            }
        }

        return $invoices;
    }

    /**
     * Generate invoices for the previous month (standard monthly billing).
     *
     * @return Collection<Invoice>
     */
    public function generateMonthlyInvoices(?int $generatedBy = null): Collection
    {
        $lastMonth = now()->subMonth();
        $periodStart = $lastMonth->copy()->startOfMonth();
        $periodEnd = $lastMonth->copy()->endOfMonth();

        return $this->generateForAllUsers($periodStart, $periodEnd, $generatedBy);
    }

    /**
     * Preview what an invoice would contain without creating it.
     *
     * Useful for admin review before generating.
     *
     * @return array{user: User, period: array, items: Collection, subtotal: float}|null
     */
    public function previewForUser(
        User $user,
        Carbon $periodStart,
        Carbon $periodEnd
    ): ?array {
        $usageLogs = $this->getUninvoicedUsageLogs($user, $periodStart, $periodEnd);

        if ($usageLogs->isEmpty()) {
            return null;
        }

        $items = $usageLogs->map(fn ($log) => InvoiceItem::createFromUsageLog($log));
        $subtotal = $items->sum('amount');

        return [
            'user' => $user,
            'period' => [
                'start' => $periodStart,
                'end' => $periodEnd,
            ],
            'items' => $items,
            'subtotal' => $subtotal,
        ];
    }

    /**
     * Get users who have uninvoiced usage for a billing period.
     *
     * @return Collection<User>
     */
    public function getUsersWithUninvoicedUsage(
        Carbon $periodStart,
        Carbon $periodEnd
    ): Collection {
        $userIds = UsageLog::query()
            ->billable()
            ->whereDoesntHave('invoiceItem')
            ->whereBetween('checked_in_at', [$periodStart, $periodEnd])
            ->distinct()
            ->pluck('user_id');

        return User::whereIn('id', $userIds)->get();
    }

    /**
     * Check if a user has uninvoiced usage for a period.
     */
    public function hasUninvoicedUsage(
        User $user,
        Carbon $periodStart,
        Carbon $periodEnd
    ): bool {
        return $this->getUninvoicedUsageLogs($user, $periodStart, $periodEnd)->isNotEmpty();
    }

    /**
     * Get summary statistics for a billing period.
     *
     * @return array{user_count: int, usage_count: int, total_amount: float}
     */
    public function getPeriodSummary(Carbon $periodStart, Carbon $periodEnd): array
    {
        $query = UsageLog::query()
            ->billable()
            ->whereDoesntHave('invoiceItem')
            ->whereBetween('checked_in_at', [$periodStart, $periodEnd]);

        return [
            'user_count' => (clone $query)->distinct('user_id')->count('user_id'),
            'usage_count' => (clone $query)->count(),
            'total_amount' => (clone $query)->sum('calculated_cost'),
        ];
    }

    /**
     * Get uninvoiced billable usage logs for a user and period.
     *
     * @return Collection<UsageLog>
     */
    protected function getUninvoicedUsageLogs(
        User $user,
        Carbon $periodStart,
        Carbon $periodEnd
    ): Collection {
        return UsageLog::query()
            ->with(['resource', 'reservation'])
            ->forUser($user->id)
            ->billable()
            ->whereDoesntHave('invoiceItem')
            ->whereBetween('checked_in_at', [$periodStart, $periodEnd])
            ->orderBy('checked_in_at')
            ->get();
    }

    /**
     * Add a manual line item to an existing draft invoice.
     */
    public function addManualItem(
        Invoice $invoice,
        string $description,
        float $amount,
        ?int $resourceId = null
    ): InvoiceItem {
        if (! $invoice->isEditable()) {
            throw new \InvalidArgumentException('Cannot add items to a non-draft invoice.');
        }

        $item = $invoice->items()->create([
            'resource_id' => $resourceId,
            'description' => $description,
            'quantity' => 1,
            'unit' => null,
            'unit_price' => $amount,
            'amount' => $amount,
        ]);

        $invoice->recalculateTotals();

        return $item;
    }

    /**
     * Remove an item from a draft invoice.
     */
    public function removeItem(Invoice $invoice, InvoiceItem $item): bool
    {
        if (! $invoice->isEditable()) {
            throw new \InvalidArgumentException('Cannot remove items from a non-draft invoice.');
        }

        $deleted = $item->delete();

        if ($deleted) {
            $invoice->recalculateTotals();
        }

        return $deleted;
    }
}

