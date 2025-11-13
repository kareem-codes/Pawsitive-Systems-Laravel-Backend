<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $paymentId = $this->route('payment')->id;
        
        return [
            'invoice_id' => 'sometimes|exists:invoices,id',
            'payment_number' => 'sometimes|string|max:255|unique:payments,payment_number,' . $paymentId,
            'amount' => 'sometimes|numeric|min:0.01',
            'payment_method' => 'sometimes|in:cash,credit_card,debit_card,bank_transfer,other',
            'payment_date' => 'sometimes|date',
            'transaction_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }
}
