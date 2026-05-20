<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|string|min:5',
            'billing_address' => 'required|string|min:5',
            'payment_method' => 'required|in:cod,card,bank_transfer',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages()
    {
        return [
            'items.required' => 'At least one product is required',
            'items.*.product_id.exists' => 'Product not found',
            'shipping_address.required' => 'Shipping address is required',
            'billing_address.required' => 'Billing address is required',
            'payment_method.in' => 'Invalid payment method',
        ];
    }
}
