<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // Get category IDs
        $laptops = Category::where('name', 'Laptops')->first();
        $smartphones = Category::where('name', 'Smartphones')->first();
        $mensClothing = Category::where('name', "Men's Clothing")->first();

        // Products
        Product::create([
            'name' => 'Gaming Laptop',
            'slug' => 'gaming-laptop',
            'sku' => 'LAP001',
            'description' => 'High performance gaming laptop',
            'price' => 1500.00,
            'discount_price' => 1299.00,
            'stock_quantity' => 10,
            'category_id' => $laptops->id,
            'status' => 'active',
        ]);

        Product::create([
            'name' => 'Office Laptop',
            'slug' => 'office-laptop',
            'sku' => 'LAP002',
            'description' => 'Best for work',
            'price' => 800.00,
            'discount_price' => null,
            'stock_quantity' => 25,
            'category_id' => $laptops->id,
            'status' => 'active',
        ]);

        Product::create([
            'name' => 'Smartphone X',
            'slug' => 'smartphone-x',
            'sku' => 'PHN001',
            'description' => 'Latest model',
            'price' => 999.00,
            'discount_price' => 899.00,
            'stock_quantity' => 15,
            'category_id' => $smartphones->id,
            'status' => 'active',
        ]);

        Product::create([
            'name' => 'Casual Shirt',
            'slug' => 'casual-shirt',
            'sku' => 'CLT001',
            'description' => 'Cotton casual shirt',
            'price' => 49.99,
            'discount_price' => 39.99,
            'stock_quantity' => 50,
            'category_id' => $mensClothing->id,
            'status' => 'active',
        ]);

        // Low stock product (for testing)
        Product::create([
            'name' => 'Budget Phone',
            'slug' => 'budget-phone',
            'sku' => 'PHN002',
            'description' => 'Affordable smartphone',
            'price' => 199.99,
            'discount_price' => null,
            'stock_quantity' => 2,
            'category_id' => $smartphones->id,
            'status' => 'active',
        ]);
    }
}
