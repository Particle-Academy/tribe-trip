<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Console\Command;

/**
 * Artisan command to check for and mark overdue invoices.
 *
 * Runs daily to update invoice statuses for those past their due date.
 */
class CheckOverdueInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:check-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for sent invoices past their due date and mark them as overdue';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $overdueInvoices = Invoice::query()
            ->where('status', InvoiceStatus::Sent)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->get();

        if ($overdueInvoices->isEmpty()) {
            $this->info('No invoices to mark as overdue.');

            return self::SUCCESS;
        }

        $this->info("Found {$overdueInvoices->count()} invoice(s) past due date.");

        foreach ($overdueInvoices as $invoice) {
            $invoice->markAsOverdue();
            $this->line(" - {$invoice->invoice_number} for {$invoice->user->name} marked as overdue.");
        }

        $this->newLine();
        $this->info('All overdue invoices have been updated.');

        return self::SUCCESS;
    }
}
