<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }} - TribeTrip</title>
    <style>
        /* Base styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #3D4A36;
            background: #fff;
            padding: 20px;
        }

        /* Print styles */
        @media print {
            body {
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .page-break {
                page-break-before: always;
            }
        }

        /* Layout */
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
        }

        /* Header */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #E8E2D6;
        }

        .company-info {
            flex: 1;
        }

        .company-name {
            font-size: 24px;
            font-weight: 700;
            color: #4A5240;
            margin-bottom: 4px;
        }

        .company-tagline {
            font-size: 12px;
            color: #7A8B6E;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h1 {
            font-size: 28px;
            font-weight: 700;
            color: #3D4A36;
            margin-bottom: 8px;
        }

        .invoice-number {
            font-size: 16px;
            color: #7A8B6E;
        }

        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 8px;
        }

        .status-paid {
            background: #dcfce7;
            color: #166534;
        }

        .status-sent {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-overdue {
            background: #fef3c7;
            color: #92400e;
        }

        /* Billing info section */
        .billing-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }

        .billing-box {
            flex: 1;
        }

        .billing-box h3 {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #7A8B6E;
            margin-bottom: 8px;
        }

        .billing-box p {
            margin: 2px 0;
        }

        .billing-box .name {
            font-weight: 600;
            font-size: 16px;
        }

        /* Invoice details */
        .invoice-details {
            display: flex;
            gap: 40px;
            margin-bottom: 40px;
        }

        .detail-item {
            text-align: left;
        }

        .detail-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #7A8B6E;
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 14px;
            font-weight: 500;
        }

        .detail-value.highlight {
            color: #8B5A3C;
            font-weight: 600;
        }

        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table thead {
            background: #E8E2D6;
        }

        .items-table th {
            padding: 12px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #3D4A36;
        }

        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }

        .items-table th:nth-child(2),
        .items-table th:nth-child(3),
        .items-table td:nth-child(2),
        .items-table td:nth-child(3) {
            text-align: right;
        }

        .items-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #E8E2D6;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 2px solid #E8E2D6;
        }

        /* Totals */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }

        .totals-table {
            width: 300px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }

        .totals-row.subtotal {
            border-bottom: 1px solid #E8E2D6;
        }

        .totals-row.total {
            font-size: 18px;
            font-weight: 700;
            border-top: 2px solid #3D4A36;
            padding-top: 12px;
            margin-top: 8px;
        }

        .totals-label {
            color: #7A8B6E;
        }

        .totals-row.total .totals-label {
            color: #3D4A36;
        }

        /* Footer */
        .invoice-footer {
            border-top: 1px solid #E8E2D6;
            padding-top: 20px;
            text-align: center;
            color: #7A8B6E;
            font-size: 12px;
        }

        .invoice-footer p {
            margin: 4px 0;
        }

        /* Print button */
        .print-actions {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #F2EDE4;
            border-radius: 8px;
        }

        .print-btn {
            display: inline-block;
            padding: 12px 24px;
            background: #4A5240;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 14px;
            margin: 0 8px;
        }

        .print-btn:hover {
            background: #3D4A36;
        }

        .print-btn.secondary {
            background: #7A8B6E;
        }

        .print-btn.secondary:hover {
            background: #5A6350;
        }
    </style>
</head>
<body>
    {{-- Print actions (hidden when printing) --}}
    <div class="print-actions no-print">
        <button onclick="window.print()" class="print-btn">
            Print / Save as PDF
        </button>
        <a href="{{ route('member.invoices') }}" class="print-btn secondary">
            Back to Invoices
        </a>
    </div>

    <div class="invoice-container">
        {{-- Header --}}
        <div class="invoice-header">
            <div class="company-info">
                <div class="company-name">TribeTrip</div>
                <div class="company-tagline">Community Resource Sharing</div>
            </div>
            <div class="invoice-title">
                <h1>INVOICE</h1>
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                <span class="status-badge status-{{ $invoice->status->value }}">
                    {{ $invoice->status->label() }}
                </span>
            </div>
        </div>

        {{-- Billing section --}}
        <div class="billing-section">
            <div class="billing-box">
                <h3>Bill To</h3>
                <p class="name">{{ $invoice->user->name }}</p>
                <p>{{ $invoice->user->email }}</p>
                @if ($invoice->user->phone)
                    <p>{{ $invoice->user->phone }}</p>
                @endif
            </div>
            <div class="billing-box" style="text-align: right;">
                <h3>From</h3>
                <p class="name">TribeTrip</p>
                <p>Community Resource Sharing</p>
            </div>
        </div>

        {{-- Invoice details --}}
        <div class="invoice-details">
            <div class="detail-item">
                <div class="detail-label">Billing Period</div>
                <div class="detail-value">{{ $invoice->billing_period }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Invoice Date</div>
                <div class="detail-value">{{ $invoice->sent_at?->format('F j, Y') ?? $invoice->created_at->format('F j, Y') }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Due Date</div>
                <div class="detail-value highlight">{{ $invoice->due_date?->format('F j, Y') ?? '—' }}</div>
            </div>
            @if ($invoice->paid_at)
                <div class="detail-item">
                    <div class="detail-label">Paid Date</div>
                    <div class="detail-value" style="color: #166534;">{{ $invoice->paid_at->format('F j, Y') }}</div>
                </div>
            @endif
        </div>

        {{-- Line items --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Rate</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td>{{ $item->formatted_quantity }}</td>
                        <td>{{ $item->formatted_unit_price }}</td>
                        <td>{{ $item->formatted_amount }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="totals-section">
            <div class="totals-table">
                <div class="totals-row subtotal">
                    <span class="totals-label">Subtotal</span>
                    <span>{{ $invoice->formatted_subtotal }}</span>
                </div>
                @if ($invoice->adjustments != 0)
                    <div class="totals-row">
                        <span class="totals-label">
                            Adjustment
                            @if ($invoice->adjustment_reason)
                                <br><small>({{ $invoice->adjustment_reason }})</small>
                            @endif
                        </span>
                        <span>{{ $invoice->formatted_adjustments }}</span>
                    </div>
                @endif
                <div class="totals-row total">
                    <span class="totals-label">Total Due</span>
                    <span>{{ $invoice->formatted_total }}</span>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="invoice-footer">
            <p>Thank you for being part of the TribeTrip community!</p>
            <p>Questions about this invoice? Contact your community administrator.</p>
        </div>
    </div>
</body>
</html>

