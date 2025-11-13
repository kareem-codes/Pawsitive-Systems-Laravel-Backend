<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku',
            'description' => 'nullable|string',
            'category' => 'required|in:food,medicine,accessories,toys,grooming,other',
            'price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'quantity_in_stock' => 'required|integer|min:0',
            'reorder_threshold' => 'nullable|integer|min:0',
            'barcode' => 'nullable|string|max:255|unique:products,barcode',
            'expiry_date' => 'nullable|date|after:today',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'tax_fixed' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ];
    }
}
