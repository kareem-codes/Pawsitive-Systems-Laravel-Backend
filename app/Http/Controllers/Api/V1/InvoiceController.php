<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class InvoiceController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);
        
        $query = Invoice::with(['owner', 'pet', 'items', 'payments']);

        // Owners can only see their own invoices
        if ($request->user()->isOwner()) {
            $query->where('user_id', $request->user()->id);
        } else {
            // Staff can filter
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Get unpaid invoices
        if ($request->has('unpaid') && $request->unpaid) {
            $query->unpaid();
        }

        // Get overdue invoices
        if ($request->has('overdue') && $request->overdue) {
            $query->overdue();
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('invoice_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('invoice_date', '<=', $request->to_date);
        }

        $invoices = $query->orderBy('id', 'desc')->latest('invoice_date')->paginate($request->per_page ?? 15);

        return response()->json($invoices);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $this->authorize('create', Invoice::class);
        
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();
            $items = $validated['items'];
            unset($validated['items']);

            // Calculate totals
            $subtotal = 0;
            $totalTax = 0;
            $totalDiscount = 0;

            $invoiceItems = [];
            foreach ($items as $item) {
                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                
                // Calculate tax
                $taxAmount = 0;
                if (isset($item['tax_percentage'])) {
                    $taxAmount = ($itemSubtotal * $item['tax_percentage']) / 100;
                } elseif (isset($item['tax_fixed'])) {
                    $taxAmount = $item['tax_fixed'];
                }
                
                // Calculate discount
                $discountAmount = 0;
                if (isset($item['discount_percentage'])) {
                    $discountAmount = ($itemSubtotal * $item['discount_percentage']) / 100;
                } elseif (isset($item['discount_amount'])) {
                    $discountAmount = $item['discount_amount'];
                }
                
                $itemTotal = $itemSubtotal + $taxAmount - $discountAmount;
                
                $subtotal += $itemSubtotal;
                $totalTax += $taxAmount;
                $totalDiscount += $discountAmount;

                $invoiceItems[] = array_merge($item, [
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'total' => $itemTotal,
                ]);
            }

            $totalAmount = $subtotal + $totalTax - $totalDiscount;

            // Create invoice
            $invoice = Invoice::create(array_merge($validated, [
                'subtotal' => $subtotal,
                'tax_amount' => $totalTax,
                'discount_amount' => $totalDiscount,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
            ]));

            // Create invoice items and update stock
            foreach ($invoiceItems as $item) {
                $invoice->items()->create($item);
                
                // Deduct stock if this is a product
                if (isset($item['product_id']) && $item['type'] === 'product') {
                    $product = \App\Models\Product::find($item['product_id']);
                    if ($product) {
                        $product->decrement('quantity_in_stock', $item['quantity']);
                        
                        // Create stock movement record
                        \App\Models\StockMovement::create([
                            'product_id' => $product->id,
                            'type' => 'out',
                            'quantity' => $item['quantity'],
                            'reference_type' => 'App\Models\Invoice',
                            'reference_id' => $invoice->id,
                            'notes' => 'Sale via POS',
                            'created_by' => $validated['created_by'],
                            'quantity_before' => $product->quantity_in_stock + $item['quantity'],
                            'quantity_after' => $product->quantity_in_stock,
                        ]);
                    }
                }
            }

            $invoice->load(['owner', 'items', 'pet']);

            return response()->json([
                'message' => 'Invoice created successfully',
                'invoice' => $invoice,
            ], 200);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorize('view', $invoice);
        
        $invoice->load(['owner', 'pet', 'items.product', 'payments', 'createdBy']);

        return response()->json($invoice);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $this->authorize('update', $invoice);
        
        $invoice->update($request->validated());
        $invoice->load(['owner', 'items']);

        return response()->json([
            'message' => 'Invoice updated successfully',
            'invoice' => $invoice,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        $this->authorize('delete', $invoice);
        
        $invoice->delete();

        return response()->json([
            'message' => 'Invoice deleted successfully',
        ]);
    }
}
