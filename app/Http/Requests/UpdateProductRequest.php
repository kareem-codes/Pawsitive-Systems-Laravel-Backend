<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
        $productId = $this->route('product') ? $this->route('product')->id : null;
        
        return [
            'name' => 'sometimes|string|max:255',
            'sku' => 'sometimes|string|max:255|unique:products,sku,' . $productId,
            'description' => 'nullable|string',
            'category' => 'sometimes|in:food,medicine,accessories,toys,grooming,other',
            'price' => 'sometimes|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'quantity_in_stock' => 'sometimes|integer|min:0',
            'reorder_threshold' => 'nullable|integer|min:0',
            'barcode' => 'nullable|string|max:255|unique:products,barcode,' . $productId,
            'expiry_date' => 'nullable|date',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'tax_fixed' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ];
    }
}
