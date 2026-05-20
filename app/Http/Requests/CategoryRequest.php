<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'name' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'string', 'max:255'],
            'parent_id' => 'nullable|exists:categories,id',
            'status' => 'sometimes|boolean',
        ];

        // Slug validation - unique except for update
        if ($this->isMethod('POST')) {
            $rules['slug'] = 'nullable|string|unique:categories,slug';
        } else {
            $rules['slug'] = [
                'nullable',
                'string',
                Rule::unique('categories')->ignore($this->route('category')),
            ];
        }

        // Prevent setting self as parent
        if ($this->has('parent_id') && $this->route('category')) {
            $categoryId = $this->route('category') instanceof Category
                ? $this->route('category')->id
                : $this->route('category');

            $rules['parent_id'][] = function ($attribute, $value, $fail) use ($categoryId) {
                if ($value == $categoryId) {
                    $fail('A category cannot be its own parent.');
                }
            };
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => 'Category name is required',
            'name.max' => 'Category name cannot exceed 255 characters',
            'parent_id.exists' => 'Selected parent category does not exist',
        ];
    }

    protected function prepareForValidation()
    {
        // Auto-generate slug if not provided
        if (! $this->has('slug') && $this->has('name')) {
            $this->merge([
                'slug' => Str::slug($this->name),
            ]);
        }
    }
}
