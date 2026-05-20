<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends BaseController
{
    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'images']);

        // Search by name or SKU
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('sku', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        // Filter by category. Supports both category and category_id query keys.
        $categoryId = $request->integer('category') ?: $request->integer('category_id');
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // Filter by price range
        if ($request->has('min_price') && $request->min_price) {
            $query->where(function ($q) use ($request) {
                $q->where('price', '>=', $request->min_price)
                    ->orWhere('discount_price', '>=', $request->min_price);
            });
        }

        if ($request->has('max_price') && $request->max_price) {
            $query->where(function ($q) use ($request) {
                $q->where('price', '<=', $request->max_price)
                    ->orWhere('discount_price', '<=', $request->max_price);
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by stock
        if ($request->has('in_stock') && $request->in_stock) {
            $query->where('stock_quantity', '>', 0);
        }

        if ($request->has('low_stock') && $request->low_stock) {
            $query->where('stock_quantity', '<=', 10);
        }

        // Sorting
        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'price_asc':
                    $query->orderByRaw('COALESCE(discount_price, price) asc');
                    break;
                case 'price_desc':
                    $query->orderByRaw('COALESCE(discount_price, price) desc');
                    break;
                case 'name_asc':
                    $query->orderBy('name', 'asc');
                    break;
                case 'name_desc':
                    $query->orderBy('name', 'desc');
                    break;
                case 'latest':
                    $query->latest();
                    break;
                case 'oldest':
                    $query->oldest();
                    break;
                default:
                    $query->latest();
            }
        } else {
            $query->latest();
        }

        // Pagination
        $perPage = min($request->integer('per_page', 15), 100);
        $products = $query->paginate($perPage);

        return $this->sendPaginatedResponse(
            ProductResource::collection($products),
            'Products retrieved successfully'
        );
    }

    /**
     * Store a newly created product
     */
    public function store(ProductRequest $request)
    {
        DB::beginTransaction();

        try {
            // Create product
            $product = Product::create($request->validated());

            // Handle image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('products', 'public');
                    $product->images()->create([
                        'image_path' => $path,
                        'is_primary' => $index === 0,
                        'sort_order' => $index,
                    ]);
                }
            }

            DB::commit();

            return $this->sendResponse(
                new ProductResource($product->load(['category', 'images'])),
                'Product created successfully',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->sendError('Failed to create product', [$e->getMessage()], 500);
        }
    }

    /**
     * Display the specified product
     */
    public function show(Product $product)
    {
        $product->load(['category', 'images', 'primaryImage']);

        return $this->sendResponse(
            new ProductResource($product),
            'Product details retrieved successfully'
        );
    }

    /**
     * Update the specified product
     */
    public function update(ProductRequest $request, Product $product)
    {
        DB::beginTransaction();

        try {
            $product->update($request->validated());

            // Handle new images
            if ($request->hasFile('images')) {
                $currentImageCount = $product->images()->count();
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('products', 'public');
                    $product->images()->create([
                        'image_path' => $path,
                        'is_primary' => false,
                        'sort_order' => $currentImageCount + $index,
                    ]);
                }
            }

            // Update primary image if specified
            if ($request->has('primary_image_id')) {
                $product->images()->update(['is_primary' => false]);
                $product->images()->where('id', $request->primary_image_id)->update(['is_primary' => true]);
            }

            DB::commit();

            return $this->sendResponse(
                new ProductResource($product->load(['category', 'images'])),
                'Product updated successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->sendError('Failed to update product', [$e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy(Product $product)
    {
        DB::beginTransaction();

        try {
            // Delete associated images from storage
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->image_path);
                $image->delete();
            }

            $product->delete();

            DB::commit();

            return $this->sendResponse(null, 'Product deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->sendError('Failed to delete product', [$e->getMessage()], 500);
        }
    }

    /**
     * Remove specific image from product
     */
    public function removeImage(Request $request, Product $product)
    {
        $request->validate([
            'image_id' => 'required|exists:product_images,id',
        ]);

        $image = $product->images()->find($request->image_id);

        if (! $image) {
            return $this->sendError('Image not found', [], 404);
        }

        // Check if this is primary image and there are other images
        if ($image->is_primary && $product->images()->count() > 1) {
            // Set another image as primary
            $newPrimary = $product->images()->where('id', '!=', $image->id)->first();
            if ($newPrimary) {
                $newPrimary->update(['is_primary' => true]);
            }
        }

        // Delete file and record
        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return $this->sendResponse(null, 'Image removed successfully');
    }

    /**
     * Bulk update stock
     */
    public function bulkUpdateStock(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.stock_quantity' => 'required|integer|min:0',
        ]);

        foreach ($request->products as $productData) {
            Product::where('id', $productData['id'])
                ->update(['stock_quantity' => $productData['stock_quantity']]);
        }

        return $this->sendResponse(null, 'Stock updated successfully');
    }
}
