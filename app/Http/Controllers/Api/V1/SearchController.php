<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Global search across multiple models.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'types' => 'nullable|array',
            'types.*' => 'in:pets,owners,appointments,invoices,products',
        ]);

        $query = $request->q;
        $types = $request->types ?? ['pets', 'owners', 'appointments', 'invoices', 'products'];
        $results = [];

        // Search Pets
        if (in_array('pets', $types)) {
            $pets = Pet::where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('species', 'like', "%{$query}%")
                  ->orWhere('breed', 'like', "%{$query}%")
                  ->orWhere('microchip_id', 'like', "%{$query}%");
            })
            ->with('owner')
            ->limit(10)
            ->get()
            ->map(function ($pet) {
                return [
                    'type' => 'pet',
                    'id' => $pet->id,
                    'title' => $pet->name,
                    'subtitle' => $pet->species . ' - ' . $pet->breed,
                    'owner' => $pet->owner->name ?? null,
                    'data' => $pet,
                ];
            });

            $results = array_merge($results, $pets->toArray());
        }

        // Search Owners
        if (in_array('owners', $types)) {
            $owners = User::role('owner')
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('first_name', 'like', "%{$query}%")
                      ->orWhere('last_name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%")
                      ->orWhere('phone', 'like', "%{$query}%");
                })
                ->limit(10)
                ->get()
                ->map(function ($owner) {
                    return [
                        'type' => 'owner',
                        'id' => $owner->id,
                        'title' => $owner->name,
                        'subtitle' => $owner->email,
                        'phone' => $owner->phone,
                        'data' => $owner,
                    ];
                });

            $results = array_merge($results, $owners->toArray());
        }

        // Search Appointments
        if (in_array('appointments', $types)) {
            $appointments = Appointment::with(['pet', 'owner', 'veterinarian'])
                ->whereHas('pet', function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%");
                })
                ->orWhereHas('owner', function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%");
                })
                ->limit(10)
                ->get()
                ->map(function ($appointment) {
                    return [
                        'type' => 'appointment',
                        'id' => $appointment->id,
                        'title' => 'Appointment - ' . $appointment->pet->name,
                        'subtitle' => $appointment->appointment_date->format('Y-m-d H:i') . ' - ' . $appointment->status,
                        'owner' => $appointment->owner->name ?? null,
                        'data' => $appointment,
                    ];
                });

            $results = array_merge($results, $appointments->toArray());
        }

        // Search Invoices
        if (in_array('invoices', $types)) {
            $invoices = Invoice::with('user')
                ->where('invoice_number', 'like', "%{$query}%")
                ->orWhereHas('user', function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%");
                })
                ->limit(10)
                ->get()
                ->map(function ($invoice) {
                    return [
                        'type' => 'invoice',
                        'id' => $invoice->id,
                        'title' => 'Invoice #' . $invoice->invoice_number,
                        'subtitle' => $invoice->user->name . ' - $' . $invoice->total_amount,
                        'status' => $invoice->status,
                        'data' => $invoice,
                    ];
                });

            $results = array_merge($results, $invoices->toArray());
        }

        // Search Products
        if (in_array('products', $types)) {
            $products = Product::where('name', 'like', "%{$query}%")
                ->orWhere('sku', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->limit(10)
                ->get()
                ->map(function ($product) {
                    return [
                        'type' => 'product',
                        'id' => $product->id,
                        'title' => $product->name,
                        'subtitle' => 'SKU: ' . $product->sku . ' - $' . $product->price,
                        'stock' => $product->stock_quantity,
                        'data' => $product,
                    ];
                });

            $results = array_merge($results, $products->toArray());
        }

        return response()->json([
            'query' => $query,
            'total_results' => count($results),
            'results' => $results,
        ]);
    }
}
