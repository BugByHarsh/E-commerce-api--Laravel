<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'stock_quantity' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'status' => 'required|in:active,inactive',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        if ($this->isMethod('POST')) {
            $rules['slug'] = 'required|string|unique:products,slug';
            $rules['sku'] = 'required|string|unique:products,sku';
        } else {
            $rules['slug'] = ['required', 'string', Rule::unique('products')->ignore($this->product)];
            $rules['sku'] = ['required', 'string', Rule::unique('products')->ignore($this->product)];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'images.*.image' => 'Each file must be an image',
            'images.*.max' => 'Image size cannot exceed 2MB',
        ];
    }

    protected function prepareForValidation()
    {
        if (! $this->has('slug') && $this->has('name')) {
            $this->merge([
                'slug' => \Str::slug($this->name),
            ]);
        }
    }
}
