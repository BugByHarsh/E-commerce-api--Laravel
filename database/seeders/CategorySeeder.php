<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run()
    {
        // Root categories (no parent)
        $electronics = Category::create(['name' => 'Electronics']);
        $fashion = Category::create(['name' => 'Fashion']);

        // Child categories
        Category::create(['name' => 'Laptops', 'parent_id' => $electronics->id]);
        Category::create(['name' => 'Smartphones', 'parent_id' => $electronics->id]);
        Category::create(['name' => "Men's Clothing", 'parent_id' => $fashion->id]);
        Category::create(['name' => "Women's Clothing", 'parent_id' => $fashion->id]);
    }
}
