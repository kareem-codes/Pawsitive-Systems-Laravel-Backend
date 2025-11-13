<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StockController extends Controller
{
    /**
     * Get low stock products
     */
    public function lowStock(): JsonResponse
    {
        $products = Product::lowStock()
            ->with('stockMovements')
            ->get();

        return response()->json([
            'message' => 'Low stock products retrieved successfully',
            'data' => $products,
            'count' => $products->count()
        ]);
    }

    /**
     * Get stock movement history for a product
     */
    public function history(Product $product): JsonResponse
    {
        $movements = $product->stockMovements()
            ->with('createdBy:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'message' => 'Stock movement history retrieved successfully',
            'data' => $movements
        ]);
    }

    /**
     * Add stock to a product
     */
    public function addStock(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer'
        ]);

        $movement = $product->addStock(
            $validated['quantity'],
            $validated['notes'] ?? null,
            auth()->id(),
            $validated['reference_type'] ?? null,
            $validated['reference_id'] ?? null
        );

        return response()->json([
            'message' => 'Stock added successfully',
            'data' => [
                'product' => $product->fresh(),
                'movement' => $movement
            ]
        ], 201);
    }

    /**
     * Remove stock from a product
     */
    public function removeStock(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:' . $product->quantity_in_stock,
            'notes' => 'nullable|string|max:500',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer'
        ]);

        $movement = $product->removeStock(
            $validated['quantity'],
            $validated['notes'] ?? null,
            auth()->id(),
            $validated['reference_type'] ?? null,
            $validated['reference_id'] ?? null
        );

        return response()->json([
            'message' => 'Stock removed successfully',
            'data' => [
                'product' => $product->fresh(),
                'movement' => $movement
            ]
        ]);
    }

    /**
     * Adjust stock quantity
     */
    public function adjustStock(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
            'notes' => 'required|string|max:500'
        ]);

        $movement = $product->adjustStock(
            $validated['quantity'],
            $validated['notes'],
            auth()->id()
        );

        return response()->json([
            'message' => 'Stock adjusted successfully',
            'data' => [
                'product' => $product->fresh(),
                'movement' => $movement
            ]
        ]);
    }

    /**
     * Mark stock as damaged
     */
    public function markDamaged(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:' . $product->quantity_in_stock,
            'notes' => 'required|string|max:500'
        ]);

        $movement = $product->markDamaged(
            $validated['quantity'],
            $validated['notes'],
            auth()->id()
        );

        return response()->json([
            'message' => 'Stock marked as damaged',
            'data' => [
                'product' => $product->fresh(),
                'movement' => $movement
            ]
        ]);
    }

    /**
     * Mark stock as expired
     */
    public function markExpired(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:' . $product->quantity_in_stock,
            'notes' => 'required|string|max:500'
        ]);

        $movement = $product->markExpired(
            $validated['quantity'],
            $validated['notes'],
            auth()->id()
        );

        return response()->json([
            'message' => 'Stock marked as expired',
            'data' => [
                'product' => $product->fresh(),
                'movement' => $movement
            ]
        ]);
    }

    /**
     * Get all stock movements
     */
    public function allMovements(Request $request): JsonResponse
    {
        $query = StockMovement::with(['product:id,name,sku', 'createdBy:id,name'])
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by product
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $movements = $query->paginate(50);

        return response()->json([
            'message' => 'Stock movements retrieved successfully',
            'data' => $movements
        ]);
    }
}
