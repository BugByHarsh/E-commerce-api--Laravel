<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryController extends BaseController
{
    public function index(CategoryRequest $request)
    {
        $categories = Category::query()
            ->with(['parent', 'children'])
            ->when($request->boolean('active_only'), fn ($query) => $query->where('status', true))
            ->latest()
            ->paginate(min($request->integer('per_page', 15), 100));

        return $this->sendPaginatedResponse(
            CategoryResource::collection($categories),
            'Categories retrieved successfully'
        );
    }

    public function store(CategoryRequest $request)
    {
        $category = Category::create($request->validated());

        return $this->sendResponse(
            new CategoryResource($category->load(['parent', 'children'])),
            'Category created successfully',
            201
        );
    }

    public function show(Category $category)
    {
        return $this->sendResponse(
            new CategoryResource($category->load(['parent', 'children'])),
            'Category details retrieved successfully'
        );
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return $this->sendResponse(
            new CategoryResource($category->load(['parent', 'children'])),
            'Category updated successfully'
        );
    }

    public function destroy(Category $category)
    {
        // Move child categories to parent (or null)
        Category::where('parent_id', $category->id)->update(['parent_id' => $category->parent_id]);

        $category->delete();

        return $this->sendResponse(null, 'Category deleted successfully');
    }
}
