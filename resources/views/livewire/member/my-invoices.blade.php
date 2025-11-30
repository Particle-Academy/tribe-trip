{{-- Member invoices dashboard --}}
<div class="space-y-6">
    {{-- Page header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" class="!text-[#3D4A36]">My Invoices</flux:heading>
            <flux:text class="mt-1 !text-[#5A6350]">View your billing history and statements</flux:text>
        </div>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" dismissible>
            {{ session('success') }}
        </flux:callout>
    @endif

    {{-- Balance summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <flux:card class="!p-6 !bg-white border-l-4 border-l-amber-500">
            <flux:text size="sm" class="!text-[#5A6350]">Outstanding Balance</flux:text>
            <flux:heading size="xl" class="!text-amber-600">${{ number_format($totals['outstanding'], 2) }}</flux:heading>
            @if ($totals['outstanding'] > 0)
                <flux:text size="sm" class="!text-[#7A8B6E] mt-1">{{ $counts['outstanding'] }} invoice(s) due</flux:text>
            @else
                <flux:text size="sm" class="!text-green-600 mt-1">All paid up!</flux:text>
            @endif
        </flux:card>
        <flux:card class="!p-6 !bg-white border-l-4 border-l-green-500">
            <flux:text size="sm" class="!text-[#5A6350]">Total Paid</flux:text>
            <flux:heading size="xl" class="!text-green-600">${{ number_format($totals['paid_total'], 2) }}</flux:heading>
            <flux:text size="sm" class="!text-[#7A8B6E] mt-1">{{ $counts['paid'] }} invoice(s)</flux:text>
        </flux:card>
    </div>

    {{-- Filter tabs --}}
    <flux:tabs wire:model.live="filter">
        <flux:tab name="all">
            All Invoices
            @if ($counts['all'] > 0)
                <flux:badge size="sm" color="zinc" class="ml-2">{{ $counts['all'] }}</flux:badge>
            @endif
        </flux:tab>
        <flux:tab name="outstanding">
            Outstanding
            @if ($counts['outstanding'] > 0)
                <flux:badge size="sm" color="amber" class="ml-2">{{ $counts['outstanding'] }}</flux:badge>
            @endif
        </flux:tab>
        <flux:tab name="paid">
            Paid
            @if ($counts['paid'] > 0)
                <flux:badge size="sm" color="green" class="ml-2">{{ $counts['paid'] }}</flux:badge>
            @endif
        </flux:tab>
    </flux:tabs>

    {{-- Invoices list --}}
    @if ($invoices->count() > 0)
        <div class="space-y-4">
            @foreach ($invoices as $invoice)
                <flux:card wire:key="invoice-{{ $invoice->id }}" class="!bg-white">
                    <div class="flex flex-col sm:flex-row gap-4">
                        {{-- Invoice icon/indicator --}}
                        @php
                            $bgClass = match($invoice->status) {
                                \App\Enums\InvoiceStatus::Paid => 'bg-green-100',
                                \App\Enums\InvoiceStatus::Overdue => 'bg-red-100',
                                default => 'bg-[#E8E2D6]',
                            };
                            $iconClass = match($invoice->status) {
                                \App\Enums\InvoiceStatus::Paid => 'text-green-600',
                                \App\Enums\InvoiceStatus::Overdue => 'text-red-600',
                                default => 'text-[#7A8B6E]',
                            };
                        @endphp
                        <div class="sm:w-16 flex-shrink-0">
                            <div class="w-16 h-16 rounded-lg flex items-center justify-center {{ $bgClass }}">
                                <flux:icon
                                    name="{{ $invoice->status === \App\Enums\InvoiceStatus::Paid ? 'check-circle' : 'document-text' }}"
                                    class="w-8 h-8 {{ $iconClass }}"
                                />
                            </div>
                        </div>

                        {{-- Invoice details --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 mb-2">
                                <div>
                                    <flux:heading size="lg" class="!text-[#3D4A36]">
                                        {{ $invoice->invoice_number }}
                                    </flux:heading>
                                    <flux:text size="sm" class="!text-[#7A8B6E]">
                                        {{ $invoice->billing_period }}
                                    </flux:text>
                                </div>
                                <flux:badge size="lg" :color="$invoice->status->color()">
                                    {{ $invoice->status->label() }}
                                </flux:badge>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4">
                                <div>
                                    <flux:text size="sm" class="!text-[#7A8B6E]">Items</flux:text>
                                    <flux:text class="font-medium !text-[#3D4A36]">{{ $invoice->items->count() }}</flux:text>
                                </div>
                                <div>
                                    <flux:text size="sm" class="!text-[#7A8B6E]">Total</flux:text>
                                    <flux:text class="font-bold !text-[#3D4A36]">{{ $invoice->formatted_total }}</flux:text>
                                </div>
                                <div>
                                    <flux:text size="sm" class="!text-[#7A8B6E]">Due Date</flux:text>
                                    <flux:text :class="'font-medium ' . ($invoice->due_date?->isPast() && $invoice->status !== \App\Enums\InvoiceStatus::Paid ? '!text-red-600' : '!text-[#3D4A36]')">
                                        {{ $invoice->due_date?->format('M j, Y') ?? '—' }}
                                    </flux:text>
                                </div>
                                <div>
                                    <flux:text size="sm" class="!text-[#7A8B6E]">
                                        @if ($invoice->status === \App\Enums\InvoiceStatus::Paid)
                                            Paid On
                                        @else
                                            Sent On
                                        @endif
                                    </flux:text>
                                    <flux:text class="font-medium !text-[#3D4A36]">
                                        @if ($invoice->status === \App\Enums\InvoiceStatus::Paid)
                                            {{ $invoice->paid_at?->format('M j, Y') ?? '—' }}
                                        @else
                                            {{ $invoice->sent_at?->format('M j, Y') ?? '—' }}
                                        @endif
                                    </flux:text>
                                </div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex sm:flex-col gap-2 sm:w-32 flex-shrink-0">
                            <flux:button wire:click="viewInvoice({{ $invoice->id }})" variant="filled" size="sm" class="flex-1 sm:w-full !bg-[#4A5240] hover:!bg-[#3D4A36]">
                                View Details
                            </flux:button>
                            <flux:button href="{{ route('member.invoices.download', $invoice) }}" variant="ghost" size="sm" class="flex-1 sm:w-full !text-[#5A6350]" icon="arrow-down-tray">
                                Download
                            </flux:button>
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $invoices->links() }}
        </div>
    @else
        <flux:card class="text-center py-12 !bg-white">
            <flux:icon name="document-text" class="w-12 h-12 text-[#7A8B6E] mx-auto mb-4" />
            <flux:heading size="lg" class="mb-2 !text-[#3D4A36]">
                @if ($filter === 'outstanding')
                    No outstanding invoices
                @elseif ($filter === 'paid')
                    No paid invoices yet
                @else
                    No invoices yet
                @endif
            </flux:heading>
            <flux:text class="!text-[#5A6350]">
                @if ($filter === 'outstanding')
                    You're all caught up! No payments due.
                @else
                    Your invoices will appear here after you use resources.
                @endif
            </flux:text>
        </flux:card>
    @endif

    {{-- Invoice detail modal --}}
    <flux:modal wire:model="showDetailModal" class="max-w-2xl">
        @if ($selectedInvoice)
            <div class="space-y-6">
                {{-- Header --}}
                <div class="flex justify-between items-start">
                    <div>
                        <flux:heading size="xl" class="!text-[#3D4A36]">{{ $selectedInvoice->invoice_number }}</flux:heading>
                        <flux:text class="!text-[#7A8B6E]">{{ $selectedInvoice->billing_period }}</flux:text>
                    </div>
                    <flux:badge size="lg" :color="$selectedInvoice->status->color()">
                        {{ $selectedInvoice->status->label() }}
                    </flux:badge>
                </div>

                {{-- Line items --}}
                <div>
                    <flux:heading size="sm" class="mb-3 !text-[#3D4A36]">Usage Details</flux:heading>
                    <div class="border border-[#D4C9B8] dark:border-zinc-700 rounded-lg overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-[#E8E2D6] dark:bg-zinc-800">
                                <tr>
                                    <th class="text-left px-4 py-3 font-medium text-[#3D4A36]">Description</th>
                                    <th class="text-right px-4 py-3 font-medium text-[#3D4A36]">Qty</th>
                                    <th class="text-right px-4 py-3 font-medium text-[#3D4A36]">Rate</th>
                                    <th class="text-right px-4 py-3 font-medium text-[#3D4A36]">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#D4C9B8] dark:divide-zinc-700 bg-white">
                                @foreach ($selectedInvoice->items as $item)
                                    <tr>
                                        <td class="px-4 py-3 text-[#5A6350]">{{ $item->description }}</td>
                                        <td class="px-4 py-3 text-right text-[#5A6350]">{{ $item->formatted_quantity }}</td>
                                        <td class="px-4 py-3 text-right text-[#5A6350]">{{ $item->formatted_unit_price }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-[#3D4A36]">{{ $item->formatted_amount }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-[#E8E2D6] dark:bg-zinc-800">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right font-medium text-[#3D4A36]">Subtotal</td>
                                    <td class="px-4 py-3 text-right font-medium text-[#3D4A36]">{{ $selectedInvoice->formatted_subtotal }}</td>
                                </tr>
                                @if ($selectedInvoice->adjustments != 0)
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right font-medium text-[#3D4A36]">
                                            Adjustment
                                            @if ($selectedInvoice->adjustment_reason)
                                                <span class="font-normal text-[#7A8B6E]">({{ $selectedInvoice->adjustment_reason }})</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium text-[#3D4A36]">{{ $selectedInvoice->formatted_adjustments }}</td>
                                    </tr>
                                @endif
                                <tr class="text-lg">
                                    <td colspan="3" class="px-4 py-3 text-right font-bold text-[#3D4A36]">Total</td>
                                    <td class="px-4 py-3 text-right font-bold text-[#3D4A36]">{{ $selectedInvoice->formatted_total }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Payment info --}}
                <div class="grid grid-cols-2 gap-4 p-4 bg-[#F2EDE4] rounded-lg">
                    <div>
                        <flux:text size="sm" class="!text-[#7A8B6E]">Due Date</flux:text>
                        <flux:text class="font-medium !text-[#3D4A36]">
                            {{ $selectedInvoice->due_date?->format('F j, Y') ?? '—' }}
                        </flux:text>
                    </div>
                    <div>
                        <flux:text size="sm" class="!text-[#7A8B6E]">Status</flux:text>
                        @php
                            $statusClass = match($selectedInvoice->status) {
                                \App\Enums\InvoiceStatus::Paid => '!text-green-600',
                                \App\Enums\InvoiceStatus::Overdue => '!text-red-600',
                                default => '!text-[#3D4A36]',
                            };
                        @endphp
                        <flux:text :class="'font-medium ' . $statusClass">
                            @if ($selectedInvoice->status === \App\Enums\InvoiceStatus::Paid)
                                Paid on {{ $selectedInvoice->paid_at?->format('F j, Y') }}
                            @elseif ($selectedInvoice->status === \App\Enums\InvoiceStatus::Overdue)
                                Payment overdue
                            @else
                                Payment pending
                            @endif
                        </flux:text>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex justify-between gap-3 pt-4 border-t border-[#D4C9B8] dark:border-zinc-700">
                    <flux:button href="{{ route('member.invoices.download', $selectedInvoice) }}" variant="ghost" icon="arrow-down-tray" class="!text-[#5A6350]">
                        Download PDF
                    </flux:button>
                    <flux:button wire:click="closeDetailModal" variant="filled" class="!bg-[#4A5240] hover:!bg-[#3D4A36]">
                        Close
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
