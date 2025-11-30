<?php

namespace App\Livewire\Admin;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\InvoiceGenerationService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin component for managing invoices.
 *
 * Lists all invoices with filtering, generation, and payment tracking.
 */
#[Layout('components.layouts.admin')]
#[Title('Invoices - TribeTrip Admin')]
class InvoiceList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    // Generate invoices modal
    public bool $showGenerateModal = false;

    public string $generateMonth = '';

    public bool $generatePreview = true;

    /** @var array<int, array<string, mixed>> */
    public array $previewData = [];

    // Invoice detail modal
    public bool $showDetailModal = false;

    public ?Invoice $selectedInvoice = null;

    // Mark paid modal
    public bool $showMarkPaidModal = false;

    public ?Invoice $invoiceToMarkPaid = null;

    // Adjustment modal
    public bool $showAdjustmentModal = false;

    public ?Invoice $invoiceToAdjust = null;

    #[Validate('required|numeric|between:-9999,9999')]
    public string $adjustmentAmount = '0';

    #[Validate('required_if:adjustmentAmount,!=,0|max:255')]
    public string $adjustmentReason = '';

    /**
     * Initialize the component.
     */
    public function mount(): void
    {
        $this->generateMonth = now()->subMonth()->format('Y-m');
    }

    /**
     * Reset pagination when filters change.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Open the generate invoices modal.
     */
    public function openGenerateModal(): void
    {
        $this->generatePreview = true;
        $this->previewData = [];
        $this->showGenerateModal = true;
    }

    /**
     * Close the generate modal.
     */
    public function closeGenerateModal(): void
    {
        $this->showGenerateModal = false;
        $this->previewData = [];
    }

    /**
     * Preview invoices that would be generated.
     */
    public function previewInvoices(InvoiceGenerationService $service): void
    {
        $period = $this->parseBillingPeriod();
        $users = $service->getUsersWithUninvoicedUsage($period['start'], $period['end']);

        $this->previewData = $users->map(function ($user) use ($service, $period) {
            $preview = $service->previewForUser($user, $period['start'], $period['end']);

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'item_count' => $preview['items']->count(),
                'subtotal' => $preview['subtotal'],
            ];
        })->toArray();

        $this->generatePreview = false;
    }

    /**
     * Generate invoices for the selected month.
     */
    public function generateInvoices(InvoiceGenerationService $service): void
    {
        $period = $this->parseBillingPeriod();

        $invoices = $service->generateForAllUsers(
            $period['start'],
            $period['end'],
            auth()->id()
        );

        $this->closeGenerateModal();

        if ($invoices->isEmpty()) {
            session()->flash('info', 'No invoices were generated - no uninvoiced usage found.');
        } else {
            session()->flash('success', "Generated {$invoices->count()} invoice(s) for the billing period.");
        }
    }

    /**
     * View invoice details.
     */
    public function viewInvoice(int $invoiceId): void
    {
        $this->selectedInvoice = Invoice::with(['user', 'items.resource', 'items.usageLog'])->findOrFail($invoiceId);
        $this->showDetailModal = true;
    }

    /**
     * Close the detail modal.
     */
    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->selectedInvoice = null;
    }

    /**
     * Open mark as paid modal.
     */
    public function openMarkPaidModal(int $invoiceId): void
    {
        $this->invoiceToMarkPaid = Invoice::findOrFail($invoiceId);
        $this->showMarkPaidModal = true;
    }

    /**
     * Close mark paid modal.
     */
    public function closeMarkPaidModal(): void
    {
        $this->showMarkPaidModal = false;
        $this->invoiceToMarkPaid = null;
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(): void
    {
        if (! $this->invoiceToMarkPaid || ! $this->invoiceToMarkPaid->canBeMarkedPaid()) {
            return;
        }

        $invoiceNumber = $this->invoiceToMarkPaid->invoice_number;
        $this->invoiceToMarkPaid->markAsPaid();
        $this->closeMarkPaidModal();

        session()->flash('success', "Invoice {$invoiceNumber} marked as paid.");
    }

    /**
     * Send an invoice (mark as sent).
     */
    public function sendInvoice(int $invoiceId): void
    {
        $invoice = Invoice::findOrFail($invoiceId);

        if (! $invoice->canBeSent()) {
            session()->flash('error', 'This invoice cannot be sent.');

            return;
        }

        $invoice->markAsSent();
        session()->flash('success', "Invoice {$invoice->invoice_number} has been sent.");
    }

    /**
     * Void an invoice.
     */
    public function voidInvoice(int $invoiceId): void
    {
        $invoice = Invoice::findOrFail($invoiceId);

        if (! $invoice->canBeVoided()) {
            session()->flash('error', 'This invoice cannot be voided.');

            return;
        }

        $invoice->void();
        session()->flash('success', "Invoice {$invoice->invoice_number} has been voided.");
    }

    /**
     * Open adjustment modal.
     */
    public function openAdjustmentModal(int $invoiceId): void
    {
        $this->invoiceToAdjust = Invoice::findOrFail($invoiceId);
        $this->adjustmentAmount = (string) $this->invoiceToAdjust->adjustments;
        $this->adjustmentReason = $this->invoiceToAdjust->adjustment_reason ?? '';
        $this->showAdjustmentModal = true;
    }

    /**
     * Close adjustment modal.
     */
    public function closeAdjustmentModal(): void
    {
        $this->showAdjustmentModal = false;
        $this->invoiceToAdjust = null;
        $this->adjustmentAmount = '0';
        $this->adjustmentReason = '';
    }

    /**
     * Apply adjustment to invoice.
     */
    public function applyAdjustment(): void
    {
        $this->validate([
            'adjustmentAmount' => 'required|numeric|between:-9999,9999',
            'adjustmentReason' => 'required_if:adjustmentAmount,!=,0|max:255',
        ]);

        if (! $this->invoiceToAdjust || ! $this->invoiceToAdjust->isEditable()) {
            return;
        }

        $this->invoiceToAdjust->applyAdjustment(
            (float) $this->adjustmentAmount,
            $this->adjustmentReason ?: null
        );

        $this->closeAdjustmentModal();
        session()->flash('success', 'Invoice adjustment applied successfully.');
    }

    /**
     * Parse the billing period from the month input.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    protected function parseBillingPeriod(): array
    {
        $date = Carbon::createFromFormat('Y-m', $this->generateMonth);

        return [
            'start' => $date->copy()->startOfMonth(),
            'end' => $date->copy()->endOfMonth(),
        ];
    }

    public function render()
    {
        $invoices = Invoice::query()
            ->with(['user', 'items'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('invoice_number', 'like', "%{$this->search}%")
                        ->orWhereHas('user', function ($uq) {
                            $uq->where('name', 'like', "%{$this->search}%")
                                ->orWhere('email', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->orderByDesc('created_at')
            ->paginate(15);

        // Get counts for filter badges
        $counts = [
            'total' => Invoice::count(),
            'draft' => Invoice::draft()->count(),
            'sent' => Invoice::sent()->count(),
            'paid' => Invoice::paid()->count(),
            'overdue' => Invoice::overdue()->count(),
        ];

        // Calculate totals
        $totals = [
            'outstanding' => Invoice::outstanding()->sum('total'),
            'paid_this_month' => Invoice::paid()
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('total'),
        ];

        return view('livewire.admin.invoice-list', [
            'invoices' => $invoices,
            'counts' => $counts,
            'totals' => $totals,
            'statuses' => InvoiceStatus::cases(),
        ]);
    }
}
