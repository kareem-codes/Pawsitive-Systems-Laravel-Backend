<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Response;

class InvoicePdfController extends Controller
{
    use AuthorizesRequests;

    /**
     * Generate and download invoice PDF
     */
    public function download(Invoice $invoice)
    {
        // Authorization: Only the invoice owner or staff can download
        $this->authorize('view', $invoice);

        // Load relationships needed for PDF
        $invoice->load([
            'owner',
            'pet',
            'appointment',
            'items.product',
            'createdBy'
        ]);

        // Generate PDF
        $pdf = Pdf::loadView('pdfs.invoice', [
            'invoice' => $invoice
        ]);

        // Configure PDF
        $pdf->setPaper('a4', 'portrait');

        // Download with filename
        $filename = 'invoice-' . $invoice->invoice_number . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Preview invoice PDF in browser
     */
    public function preview(Invoice $invoice)
    {
        // Authorization
        $this->authorize('view', $invoice);

        // Load relationships
        $invoice->load([
            'owner',
            'pet',
            'appointment',
            'items.product',
            'createdBy'
        ]);

        // Generate PDF
        $pdf = Pdf::loadView('pdfs.invoice', [
            'invoice' => $invoice
        ]);

        $pdf->setPaper('a4', 'portrait');

        // Stream in browser
        return $pdf->stream('invoice-' . $invoice->invoice_number . '.pdf');
    }
}
