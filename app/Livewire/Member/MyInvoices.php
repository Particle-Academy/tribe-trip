<?php

namespace App\Livewire\Member;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Member invoices dashboard component.
 *
 * Shows members their invoices with status, details, and download options.
 */
#[Layout('components.layouts.app')]
#[Title('My Invoices - TribeTrip')]
class MyInvoices extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'all';

    // Invoice detail modal
    public bool $showDetailModal = false;

    public ?Invoice $selectedInvoice = null;

    /**
     * Reset pagination when filter changes.
     */
    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    /**
     * View invoice details.
     */
    public function viewInvoice(int $invoiceId): void
    {
        $this->selectedInvoice = Invoice::with(['items.resource', 'items.usageLog'])
            ->where('user_id', auth()->id())
            ->findOrFail($invoiceId);
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

    public function render()
    {
        $query = Invoice::query()
            ->with(['items'])
            ->forUser(auth()->id())
            ->whereNot('status', InvoiceStatus::Draft); // Members only see sent invoices

        $invoices = match ($this->filter) {
            'outstanding' => $query->clone()
                ->outstanding()
                ->orderByDesc('sent_at')
                ->paginate(10),
            'paid' => $query->clone()
                ->paid()
                ->orderByDesc('paid_at')
                ->paginate(10),
            default => $query->clone()
                ->orderByDesc('created_at')
                ->paginate(10),
        };

        // Get counts for filter tabs
        $counts = [
            'all' => Invoice::forUser(auth()->id())->whereNot('status', InvoiceStatus::Draft)->count(),
            'outstanding' => Invoice::forUser(auth()->id())->outstanding()->count(),
            'paid' => Invoice::forUser(auth()->id())->paid()->count(),
        ];

        // Get totals
        $totals = [
            'outstanding' => Invoice::forUser(auth()->id())->outstanding()->sum('total'),
            'paid_total' => Invoice::forUser(auth()->id())->paid()->sum('total'),
        ];

        return view('livewire.member.my-invoices', [
            'invoices' => $invoices,
            'counts' => $counts,
            'totals' => $totals,
        ]);
    }
}
