<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ShopOrderController extends Controller
{
    /**
     * Get customer's orders (invoices from shop)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $orders = Invoice::where('user_id', $user->id)
            ->whereNotNull('notes')
            ->where('notes', 'like', '%SHOP ORDER%')
            ->with(['items.product'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($orders);
    }

    /**
     * Place a shop order (creates an invoice with shipping info)
     * Note: This is a self-service endpoint that doesn't require staff permissions
     */
    public function store(Request $request): JsonResponse
    {
        // No authorization check - users can create their own shop orders
        
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|string|max:500',
            'shipping_city' => 'nullable|string|max:100',
            'shipping_state' => 'nullable|string|max:100',
            'shipping_postal_code' => 'nullable|string|max:20',
            'shipping_phone' => 'required|string|max:20',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $validated = $validator->validated();
        
        return DB::transaction(function () use ($validated, $user) {
            $items = $validated['items'];

            // Calculate totals
            $subtotal = 0;
            $totalTax = 0;
            $invoiceItems = [];

            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                
                if (!$product) {
                    throw new \Exception("Product not found: {$item['product_id']}");
                }

                // Check stock availability
                if ($product->quantity_in_stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for {$product->name}. Available: {$product->quantity_in_stock}");
                }

                $itemSubtotal = $item['quantity'] * $product->price;
                
                // Calculate tax
                $taxAmount = 0;
                if ($product->tax_percentage) {
                    $taxAmount = ($itemSubtotal * $product->tax_percentage) / 100;
                } elseif ($product->tax_fixed) {
                    $taxAmount = $product->tax_fixed * $item['quantity'];
                }
                
                $itemTotal = $itemSubtotal + $taxAmount;
                
                $subtotal += $itemSubtotal;
                $totalTax += $taxAmount;

                $invoiceItems[] = [
                    'product_id' => $product->id,
                    'type' => 'product',
                    'item_name' => $product->name,
                    'description' => $product->description,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'tax_amount' => $taxAmount,
                    'tax_percentage' => $product->tax_percentage,
                    'tax_fixed' => $product->tax_fixed,
                    'discount_amount' => 0,
                    'total' => $itemTotal,
                ];
            }

            $totalAmount = $subtotal + $totalTax;

            // Generate invoice number
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            // Build order notes with shipping info
            $shippingInfo = "SHOP ORDER\n";
            $shippingInfo .= "Shipping Address: {$validated['shipping_address']}\n";
            if (!empty($validated['shipping_city'])) {
                $shippingInfo .= "City: {$validated['shipping_city']}\n";
            }
            if (!empty($validated['shipping_state'])) {
                $shippingInfo .= "State: {$validated['shipping_state']}\n";
            }
            if (!empty($validated['shipping_postal_code'])) {
                $shippingInfo .= "Postal Code: {$validated['shipping_postal_code']}\n";
            }
            $shippingInfo .= "Phone: {$validated['shipping_phone']}\n";
            if (!empty($validated['notes'])) {
                $shippingInfo .= "Customer Notes: {$validated['notes']}\n";
            }

            // Update user address if not set
            if (empty($user->address)) {
                $user->update([
                    'address' => $validated['shipping_address'],
                    'city' => $validated['shipping_city'] ?? null,
                    'state' => $validated['shipping_state'] ?? null,
                    'postal_code' => $validated['shipping_postal_code'] ?? null,
                ]);
            }

            // Create invoice for the shop order
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'user_id' => $user->id,
                'pet_id' => null,
                'appointment_id' => null,
                'created_by' => $user->id, // Self-service order
                'invoice_date' => now(),
                'due_date' => now()->addDays(30),
                'subtotal' => $subtotal,
                'tax_amount' => $totalTax,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'status' => 'pending',
                'notes' => $shippingInfo,
            ]);

            // Create invoice items and update stock
            foreach ($invoiceItems as $item) {
                $invoice->items()->create($item);
                
                // Deduct stock
                $product = Product::find($item['product_id']);
                if ($product) {
                    $product->decrement('quantity_in_stock', $item['quantity']);
                    
                    // Create stock movement record
                    \App\Models\StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'out',
                        'quantity' => $item['quantity'],
                        'reference_type' => 'App\Models\Invoice',
                        'reference_id' => $invoice->id,
                        'notes' => 'Shop order - ' . $invoiceNumber,
                        'created_by' => $user->id,
                        'quantity_before' => $product->quantity_in_stock + $item['quantity'],
                        'quantity_after' => $product->quantity_in_stock,
                    ]);
                }
            }

            $invoice->load(['items.product', 'user']);

            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $invoice,
            ], 200);
        });
    }

    /**
     * Get a specific order
     */
    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $user = $request->user();
        
        // Only allow users to see their own orders
        if ($invoice->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $invoice->load(['items.product', 'payments']);

        return response()->json($invoice);
    }
}
