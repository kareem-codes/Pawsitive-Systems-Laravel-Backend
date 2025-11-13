<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class VaccinationCardPdfController extends Controller
{
    use AuthorizesRequests;

    /**
     * Generate and download vaccination card PDF for a pet
     */
    public function download(Pet $pet)
    {
        // Authorization: Only pet owner or staff can download
        $this->authorize('view', $pet);

        // Load relationships needed for vaccination card
        $pet->load([
            'owner',
            'vaccinations' => function ($query) {
                $query->orderBy('administered_date', 'desc');
            },
            'vaccinations.administeredBy'
        ]);

        // Generate PDF
        $pdf = Pdf::loadView('pdfs.vaccination-card', [
            'pet' => $pet
        ]);

        // Configure PDF
        $pdf->setPaper('a4', 'portrait');

        // Download with filename
        $filename = 'vaccination-card-' . $pet->name . '-' . now()->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Preview vaccination card PDF in browser
     */
    public function preview(Pet $pet)
    {
        // Authorization
        $this->authorize('view', $pet);

        // Load relationships
        $pet->load([
            'owner',
            'vaccinations' => function ($query) {
                $query->orderBy('administered_date', 'desc');
            },
            'vaccinations.administeredBy'
        ]);

        // Generate PDF
        $pdf = Pdf::loadView('pdfs.vaccination-card', [
            'pet' => $pet
        ]);

        $pdf->setPaper('a4', 'portrait');

        // Stream in browser
        return $pdf->stream('vaccination-card-' . $pet->name . '.pdf');
    }
}
