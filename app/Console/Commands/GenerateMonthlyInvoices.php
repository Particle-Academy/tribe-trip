<?php

namespace App\Console\Commands;

use App\Services\InvoiceGenerationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Artisan command to generate monthly invoices for all members.
 *
 * Runs automatically on the 1st of each month to generate invoices
 * for the previous month's billable usage.
 */
class GenerateMonthlyInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-monthly
        {--month= : Month to generate for (format: YYYY-MM, defaults to last month)}
        {--preview : Preview only, do not create invoices}
        {--send : Automatically send invoices after generation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly invoices for all members with billable usage';

    /**
     * Execute the console command.
     */
    public function handle(InvoiceGenerationService $invoiceService): int
    {
        // Determine the billing period
        $period = $this->determineBillingPeriod();
        $periodStart = $period['start'];
        $periodEnd = $period['end'];

        $this->info("Generating invoices for period: {$periodStart->format('M j, Y')} - {$periodEnd->format('M j, Y')}");
        $this->newLine();

        // Get summary first
        $summary = $invoiceService->getPeriodSummary($periodStart, $periodEnd);

        if ($summary['usage_count'] === 0) {
            $this->info('No uninvoiced usage found for this period.');

            return self::SUCCESS;
        }

        $this->info("Found {$summary['usage_count']} usage records for {$summary['user_count']} members.");
        $this->info('Estimated total: $' . number_format($summary['total_amount'], 2));
        $this->newLine();

        // Preview mode
        if ($this->option('preview')) {
            $users = $invoiceService->getUsersWithUninvoicedUsage($periodStart, $periodEnd);

            $this->table(
                ['Member', 'Email', 'Usage Records'],
                $users->map(fn ($user) => [
                    $user->name,
                    $user->email,
                    $invoiceService->previewForUser($user, $periodStart, $periodEnd)['items']->count(),
                ])
            );

            $this->info('Preview complete. Run without --preview to generate invoices.');

            return self::SUCCESS;
        }

        // Generate invoices
        $this->info('Generating invoices...');
        $bar = $this->output->createProgressBar($summary['user_count']);
        $bar->start();

        $invoices = collect();
        $users = $invoiceService->getUsersWithUninvoicedUsage($periodStart, $periodEnd);

        foreach ($users as $user) {
            $invoice = $invoiceService->generateForUser($user, $periodStart, $periodEnd);
            if ($invoice) {
                $invoices->push($invoice);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info("Generated {$invoices->count()} invoices.");
        $this->newLine();

        $this->table(
            ['Invoice #', 'Member', 'Items', 'Total'],
            $invoices->map(fn ($invoice) => [
                $invoice->invoice_number,
                $invoice->user->name,
                $invoice->items->count(),
                $invoice->formatted_total,
            ])
        );

        // Optionally send invoices
        if ($this->option('send') && $invoices->isNotEmpty()) {
            $this->newLine();
            $this->info('Sending invoices...');

            foreach ($invoices as $invoice) {
                $invoice->markAsSent();
                // Email notification will be handled by Task 39
            }

            $this->info('All invoices marked as sent.');
        }

        return self::SUCCESS;
    }

    /**
     * Determine the billing period based on options.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    protected function determineBillingPeriod(): array
    {
        $monthOption = $this->option('month');

        if ($monthOption) {
            try {
                $date = Carbon::createFromFormat('Y-m', $monthOption);
            } catch (\Exception $e) {
                $this->error("Invalid month format. Use YYYY-MM (e.g., 2024-11)");
                exit(self::FAILURE);
            }
        } else {
            $date = now()->subMonth();
        }

        return [
            'start' => $date->copy()->startOfMonth(),
            'end' => $date->copy()->endOfMonth(),
        ];
    }
}
