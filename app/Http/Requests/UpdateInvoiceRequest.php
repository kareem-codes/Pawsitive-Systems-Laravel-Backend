<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
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
        $invoice = $this->route('invoice');
        $invoiceId = $invoice instanceof \App\Models\Invoice ? $invoice->id : $invoice;
        
        return [
            'invoice_number' => 'sometimes|string|max:255|unique:invoices,invoice_number,' . $invoiceId,
            'user_id' => 'sometimes|exists:users,id',
            'pet_id' => 'nullable|exists:pets,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'invoice_date' => 'sometimes|date',
            'due_date' => 'nullable|date',
            'status' => 'sometimes|in:draft,pending,paid,partially_paid,overdue,cancelled',
            'notes' => 'nullable|string',
        ];
    }
}
