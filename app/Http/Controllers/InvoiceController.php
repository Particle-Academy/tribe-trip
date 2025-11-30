<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Http\Request;

/**
 * Controller for invoice-related actions.
 *
 * Handles invoice PDF download and print-friendly views.
 */
class InvoiceController extends Controller
{
    /**
     * Display a print-friendly invoice view for download.
     *
     * Opens in a new tab optimized for printing/saving as PDF.
     */
    public function download(Request $request, Invoice $invoice)
    {
        // Ensure user can only access their own invoices
        if ($invoice->user_id !== auth()->id()) {
            abort(403, 'You are not authorized to view this invoice.');
        }

        // Ensure invoice has been sent (not draft)
        if ($invoice->status === InvoiceStatus::Draft) {
            abort(404, 'Invoice not found.');
        }

        // Load relationships
        $invoice->load(['user', 'items.resource', 'items.usageLog']);

        return view('invoices.print', [
            'invoice' => $invoice,
        ]);
    }
}
