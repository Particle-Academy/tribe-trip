{{-- Admin invoice list view --}}
<div class="space-y-6">
    {{-- Page header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Invoices</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Manage billing and track payments</flux:text>
        </div>
        <flux:button wire:click="openGenerateModal" variant="primary" icon="plus">
            Generate Invoices
        </flux:button>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" dismissible>
            {{ session('success') }}
        </flux:callout>
    @endif

    @if (session('info'))
        <flux:callout variant="info" icon="information-circle" dismissible>
            {{ session('info') }}
        </flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger" icon="exclamation-circle" dismissible>
            {{ session('error') }}
        </flux:callout>
    @endif

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <flux:card class="!p-4">
            <flux:text size="sm" class="text-zinc-500">Total Invoices</flux:text>
            <flux:heading size="lg">{{ $counts['total'] }}</flux:heading>
        </flux:card>
        <flux:card class="!p-4">
            <flux:text size="sm" class="text-zinc-500">Draft</flux:text>
            <flux:heading size="lg" class="text-zinc-600">{{ $counts['draft'] }}</flux:heading>
        </flux:card>
        <flux:card class="!p-4">
            <flux:text size="sm" class="text-zinc-500">Sent</flux:text>
            <flux:heading size="lg" class="text-blue-600">{{ $counts['sent'] }}</flux:heading>
        </flux:card>
        <flux:card class="!p-4">
            <flux:text size="sm" class="text-zinc-500">Paid</flux:text>
            <flux:heading size="lg" class="text-green-600">{{ $counts['paid'] }}</flux:heading>
        </flux:card>
        <flux:card class="!p-4">
            <flux:text size="sm" class="text-zinc-500">Overdue</flux:text>
            <flux:heading size="lg" class="text-amber-600">{{ $counts['overdue'] }}</flux:heading>
        </flux:card>
    </div>

    {{-- Financial summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <flux:card class="!p-4">
            <flux:text size="sm" class="text-zinc-500">Outstanding Balance</flux:text>
            <flux:heading size="xl" class="text-amber-600">${{ number_format($totals['outstanding'], 2) }}</flux:heading>
        </flux:card>
        <flux:card class="!p-4">
            <flux:text size="sm" class="text-zinc-500">Collected This Month</flux:text>
            <flux:heading size="xl" class="text-green-600">${{ number_format($totals['paid_this_month'], 2) }}</flux:heading>
        </flux:card>
    </div>

    {{-- Filters --}}
    <flux:card>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="Search by invoice number or member..."
                />
            </div>
            <div>
                <flux:select wire:model.live="status" class="w-40">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    </flux:card>

    {{-- Invoices table --}}
    @if ($invoices->count() > 0)
        <flux:card class="!p-0 overflow-hidden">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Invoice #</flux:table.column>
                    <flux:table.column>Member</flux:table.column>
                    <flux:table.column>Billing Period</flux:table.column>
                    <flux:table.column>Items</flux:table.column>
                    <flux:table.column>Total</flux:table.column>
                    <flux:table.column>Due Date</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($invoices as $invoice)
                        <flux:table.row wire:key="invoice-{{ $invoice->id }}">
                            <flux:table.cell>
                                <flux:button wire:click="viewInvoice({{ $invoice->id }})" variant="ghost" size="sm">
                                    {{ $invoice->invoice_number }}
                                </flux:button>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <flux:avatar size="sm" name="{{ $invoice->user->name }}" />
                                    <div>
                                        <flux:text class="font-medium">{{ $invoice->user->name }}</flux:text>
                                        <flux:text size="sm" class="text-zinc-500">{{ $invoice->user->email }}</flux:text>
                                    </div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:text size="sm">{{ $invoice->billing_period }}</flux:text>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:text>{{ $invoice->items->count() }}</flux:text>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:text class="font-semibold">{{ $invoice->formatted_total }}</flux:text>
                                @if ($invoice->adjustments != 0)
                                    <flux:text size="sm" class="text-zinc-500">
                                        ({{ $invoice->formatted_adjustments }})
                                    </flux:text>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($invoice->due_date)
                                    <flux:text size="sm" @class([
                                        'text-red-600' => $invoice->due_date->isPast() && $invoice->status !== \App\Enums\InvoiceStatus::Paid,
                                    ])>
                                        {{ $invoice->due_date->format('M j, Y') }}
                                    </flux:text>
                                @else
                                    <flux:text size="sm" class="text-zinc-400">—</flux:text>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge size="sm" :color="$invoice->status->color()">
                                    {{ $invoice->status->label() }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                                    <flux:menu>
                                        <flux:menu.item wire:click="viewInvoice({{ $invoice->id }})" icon="eye">
                                            View Details
                                        </flux:menu.item>

                                        @if ($invoice->isEditable())
                                            <flux:menu.item wire:click="openAdjustmentModal({{ $invoice->id }})" icon="adjustments-horizontal">
                                                Adjust Amount
                                            </flux:menu.item>
                                        @endif

                                        @if ($invoice->canBeSent())
                                            <flux:menu.item wire:click="sendInvoice({{ $invoice->id }})" icon="paper-airplane">
                                                Send Invoice
                                            </flux:menu.item>
                                        @endif

                                        @if ($invoice->canBeMarkedPaid())
                                            <flux:menu.item wire:click="openMarkPaidModal({{ $invoice->id }})" icon="check-circle">
                                                Mark as Paid
                                            </flux:menu.item>
                                        @endif

                                        @if ($invoice->canBeVoided())
                                            <flux:menu.separator />
                                            <flux:menu.item wire:click="voidInvoice({{ $invoice->id }})" icon="x-circle" variant="danger">
                                                Void Invoice
                                            </flux:menu.item>
                                        @endif
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $invoices->links() }}
        </div>
    @else
        <flux:card class="text-center py-12">
            <flux:icon name="document-text" class="w-12 h-12 text-zinc-400 mx-auto mb-4" />
            <flux:heading size="lg" class="mb-2">No invoices found</flux:heading>
            <flux:text class="text-zinc-500">
                @if ($search || $status)
                    Try adjusting your search or filter criteria.
                @else
                    Click "Generate Invoices" to create invoices for a billing period.
                @endif
            </flux:text>
        </flux:card>
    @endif

    {{-- Generate invoices modal --}}
    <flux:modal wire:model="showGenerateModal">
        <div class="space-y-4">
            <flux:heading size="lg">Generate Monthly Invoices</flux:heading>

            <flux:text class="text-zinc-500">
                Generate invoices for all members with uninvoiced usage during the selected billing period.
            </flux:text>

            <flux:input
                wire:model="generateMonth"
                type="month"
                label="Billing Month"
            />

            @if ($generatePreview)
                <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button wire:click="closeGenerateModal" variant="ghost">
                        Cancel
                    </flux:button>
                    <flux:button wire:click="previewInvoices" variant="primary">
                        Preview
                    </flux:button>
                </div>
            @else
                @if (count($previewData) > 0)
                    <flux:callout variant="info" icon="information-circle">
                        <flux:callout.heading>Preview</flux:callout.heading>
                        <flux:callout.text>{{ count($previewData) }} invoices will be generated.</flux:callout.text>
                    </flux:callout>

                    <div class="max-h-64 overflow-y-auto">
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Member</flux:table.column>
                                <flux:table.column>Items</flux:table.column>
                                <flux:table.column>Subtotal</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach ($previewData as $preview)
                                    <flux:table.row>
                                        <flux:table.cell>
                                            <flux:text class="font-medium">{{ $preview['name'] }}</flux:text>
                                        </flux:table.cell>
                                        <flux:table.cell>{{ $preview['item_count'] }}</flux:table.cell>
                                        <flux:table.cell>${{ number_format($preview['subtotal'], 2) }}</flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>

                    <div class="flex justify-between gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <flux:button wire:click="closeGenerateModal" variant="ghost">
                            Cancel
                        </flux:button>
                        <flux:button wire:click="generateInvoices" variant="primary" icon="document-plus">
                            Generate {{ count($previewData) }} Invoices
                        </flux:button>
                    </div>
                @else
                    <flux:callout variant="warning" icon="exclamation-triangle">
                        <flux:callout.heading>No Usage Found</flux:callout.heading>
                        <flux:callout.text>No uninvoiced usage was found for the selected billing period.</flux:callout.text>
                    </flux:callout>

                    <div class="flex justify-end pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <flux:button wire:click="closeGenerateModal" variant="ghost">
                            Close
                        </flux:button>
                    </div>
                @endif
            @endif
        </div>
    </flux:modal>

    {{-- Invoice detail modal --}}
    <flux:modal wire:model="showDetailModal" class="max-w-2xl">
        @if ($selectedInvoice)
            <div class="space-y-6">
                <div class="flex justify-between items-start">
                    <div>
                        <flux:heading size="lg">{{ $selectedInvoice->invoice_number }}</flux:heading>
                        <flux:text class="text-zinc-500">{{ $selectedInvoice->billing_period }}</flux:text>
                    </div>
                    <flux:badge :color="$selectedInvoice->status->color()">
                        {{ $selectedInvoice->status->label() }}
                    </flux:badge>
                </div>

                {{-- Member info --}}
                <div class="flex items-center gap-3 p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <flux:avatar name="{{ $selectedInvoice->user->name }}" />
                    <div>
                        <flux:text class="font-medium">{{ $selectedInvoice->user->name }}</flux:text>
                        <flux:text size="sm" class="text-zinc-500">{{ $selectedInvoice->user->email }}</flux:text>
                    </div>
                </div>

                {{-- Line items --}}
                <div>
                    <flux:heading size="sm" class="mb-3">Line Items</flux:heading>
                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50 dark:bg-zinc-800">
                                <tr>
                                    <th class="text-left px-4 py-2 font-medium">Description</th>
                                    <th class="text-right px-4 py-2 font-medium">Qty</th>
                                    <th class="text-right px-4 py-2 font-medium">Rate</th>
                                    <th class="text-right px-4 py-2 font-medium">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach ($selectedInvoice->items as $item)
                                    <tr>
                                        <td class="px-4 py-2">{{ $item->description }}</td>
                                        <td class="px-4 py-2 text-right">{{ $item->formatted_quantity }}</td>
                                        <td class="px-4 py-2 text-right">{{ $item->formatted_unit_price }}</td>
                                        <td class="px-4 py-2 text-right font-medium">{{ $item->formatted_amount }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-zinc-50 dark:bg-zinc-800">
                                <tr>
                                    <td colspan="3" class="px-4 py-2 text-right font-medium">Subtotal</td>
                                    <td class="px-4 py-2 text-right font-medium">{{ $selectedInvoice->formatted_subtotal }}</td>
                                </tr>
                                @if ($selectedInvoice->adjustments != 0)
                                    <tr>
                                        <td colspan="3" class="px-4 py-2 text-right font-medium">
                                            Adjustment
                                            @if ($selectedInvoice->adjustment_reason)
                                                <span class="font-normal text-zinc-500">({{ $selectedInvoice->adjustment_reason }})</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-right font-medium">{{ $selectedInvoice->formatted_adjustments }}</td>
                                    </tr>
                                @endif
                                <tr class="text-lg">
                                    <td colspan="3" class="px-4 py-2 text-right font-bold">Total</td>
                                    <td class="px-4 py-2 text-right font-bold">{{ $selectedInvoice->formatted_total }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Dates --}}
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <flux:text class="text-zinc-500">Due Date</flux:text>
                        <flux:text class="font-medium">
                            {{ $selectedInvoice->due_date?->format('M j, Y') ?? '—' }}
                        </flux:text>
                    </div>
                    <div>
                        <flux:text class="text-zinc-500">Sent</flux:text>
                        <flux:text class="font-medium">
                            {{ $selectedInvoice->sent_at?->format('M j, Y') ?? '—' }}
                        </flux:text>
                    </div>
                    <div>
                        <flux:text class="text-zinc-500">Paid</flux:text>
                        <flux:text class="font-medium">
                            {{ $selectedInvoice->paid_at?->format('M j, Y') ?? '—' }}
                        </flux:text>
                    </div>
                </div>

                {{-- Notes --}}
                @if ($selectedInvoice->notes)
                    <div>
                        <flux:text class="text-zinc-500 mb-1">Notes</flux:text>
                        <flux:text>{{ $selectedInvoice->notes }}</flux:text>
                    </div>
                @endif

                <div class="flex justify-end pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button wire:click="closeDetailModal" variant="ghost">
                        Close
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>

    {{-- Mark as paid modal --}}
    <flux:modal wire:model="showMarkPaidModal">
        @if ($invoiceToMarkPaid)
            <div class="space-y-4">
                <flux:heading size="lg">Mark Invoice as Paid</flux:heading>

                <flux:text class="text-zinc-500">
                    Confirm that payment has been received for invoice
                    <strong>{{ $invoiceToMarkPaid->invoice_number }}</strong>
                    from <strong>{{ $invoiceToMarkPaid->user->name }}</strong>.
                </flux:text>

                <flux:callout variant="info" icon="banknotes">
                    <flux:callout.heading>Amount Due</flux:callout.heading>
                    <flux:callout.text class="text-2xl font-bold">{{ $invoiceToMarkPaid->formatted_total }}</flux:callout.text>
                </flux:callout>

                <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button wire:click="closeMarkPaidModal" variant="ghost">
                        Cancel
                    </flux:button>
                    <flux:button wire:click="markAsPaid" variant="primary" icon="check-circle">
                        Confirm Payment
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>

    {{-- Adjustment modal --}}
    <flux:modal wire:model="showAdjustmentModal">
        @if ($invoiceToAdjust)
            <div class="space-y-4">
                <flux:heading size="lg">Adjust Invoice</flux:heading>

                <flux:text class="text-zinc-500">
                    Apply a discount or credit (negative) or additional charge (positive) to
                    invoice <strong>{{ $invoiceToAdjust->invoice_number }}</strong>.
                </flux:text>

                <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <flux:text size="sm" class="text-zinc-500">Current Subtotal</flux:text>
                    <flux:heading size="lg">{{ $invoiceToAdjust->formatted_subtotal }}</flux:heading>
                </div>

                <flux:input
                    wire:model="adjustmentAmount"
                    type="number"
                    step="0.01"
                    label="Adjustment Amount"
                    description="Negative for discount/credit, positive for additional charges"
                />

                <flux:input
                    wire:model="adjustmentReason"
                    label="Reason"
                    placeholder="e.g., Early payment discount, Damage fee..."
                />

                @error('adjustmentAmount')
                    <flux:text class="text-red-600 text-sm">{{ $message }}</flux:text>
                @enderror
                @error('adjustmentReason')
                    <flux:text class="text-red-600 text-sm">{{ $message }}</flux:text>
                @enderror

                <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button wire:click="closeAdjustmentModal" variant="ghost">
                        Cancel
                    </flux:button>
                    <flux:button wire:click="applyAdjustment" variant="primary">
                        Apply Adjustment
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
