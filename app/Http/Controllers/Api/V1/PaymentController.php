<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Invoice;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PaymentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);
        
        $query = Payment::with(['invoice', 'receivedBy']);

        // Filter by invoice
        if ($request->has('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }

        // Owners can only see payments for their invoices
        if ($request->user()->isOwner()) {
            $query->whereHas('invoice', function($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });
        }

        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('payment_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('payment_date', '<=', $request->to_date);
        }

        $payments = $query->latest('payment_date')->paginate($request->per_page ?? 15);

        return response()->json($payments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $this->authorize('create', Payment::class);
        
        return DB::transaction(function () use ($request) {
            $payment = Payment::create($request->validated());

            // Update invoice paid amount and status
            $invoice = Invoice::find($payment->invoice_id);
            $invoice->paid_amount += $payment->amount;

            // Update invoice status
            if ($invoice->paid_amount >= $invoice->total_amount) {
                $invoice->status = 'paid';
            } elseif ($invoice->paid_amount > 0) {
                $invoice->status = 'partially_paid';
            }

            $invoice->save();
            $payment->load(['invoice', 'receivedBy']);

            return response()->json([
                'message' => 'Payment recorded successfully',
                'payment' => $payment,
            ], 201);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment): JsonResponse
    {
        $this->authorize('view', $payment);
        
        $payment->load(['invoice', 'receivedBy']);

        return response()->json($payment);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePaymentRequest $request, Payment $payment): JsonResponse
    {
        $this->authorize('update', $payment);
        
        return DB::transaction(function () use ($request, $payment) {
            $oldAmount = $payment->amount;
            $payment->update($request->validated());

            // If amount changed, update invoice
            if ($request->has('amount')) {
                $invoice = Invoice::find($payment->invoice_id);
                $invoice->paid_amount = $invoice->paid_amount - $oldAmount + $payment->amount;

                // Update invoice status
                if ($invoice->paid_amount >= $invoice->total_amount) {
                    $invoice->status = 'paid';
                } elseif ($invoice->paid_amount > 0) {
                    $invoice->status = 'partially_paid';
                } else {
                    $invoice->status = 'pending';
                }

                $invoice->save();
            }

            $payment->load(['invoice', 'receivedBy']);

            return response()->json([
                'message' => 'Payment updated successfully',
                'payment' => $payment,
            ]);
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment): JsonResponse
    {
        $this->authorize('delete', $payment);
        
        return DB::transaction(function () use ($payment) {
            // Update invoice paid amount
            $invoice = Invoice::find($payment->invoice_id);
            $invoice->paid_amount -= $payment->amount;

            // Update invoice status
            if ($invoice->paid_amount >= $invoice->total_amount) {
                $invoice->status = 'paid';
            } elseif ($invoice->paid_amount > 0) {
                $invoice->status = 'partially_paid';
            } else {
                $invoice->status = 'pending';
            }

            $invoice->save();
            $payment->delete();

            return response()->json([
                'message' => 'Payment deleted successfully',
            ]);
        });
    }
}
