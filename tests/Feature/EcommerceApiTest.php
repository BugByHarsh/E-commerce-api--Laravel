<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('lists products with filters and pagination metadata', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $phones = Category::create([
        'name' => 'Phones',
        'slug' => 'phones',
    ]);

    Product::create([
        'name' => 'iPhone 15',
        'slug' => 'iphone-15',
        'sku' => 'IPH-15',
        'description' => 'Apple smartphone',
        'price' => 1200,
        'discount_price' => 1100,
        'stock_quantity' => 8,
        'category_id' => $phones->id,
        'status' => 'active',
    ]);

    Product::create([
        'name' => 'Office Laptop',
        'slug' => 'office-laptop',
        'sku' => 'LAP-01',
        'description' => 'Daily work laptop',
        'price' => 900,
        'stock_quantity' => 20,
        'category_id' => $phones->id,
        'status' => 'active',
    ]);

    $this->getJson("/api/products?search=iphone&category={$phones->id}&min_price=1000&max_price=1500")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.name', 'iPhone 15')
        ->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            'links' => ['first', 'last', 'prev', 'next'],
        ]);
});

it('places orders atomically and prevents unavailable stock', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $category = Category::create([
        'name' => 'Accessories',
        'slug' => 'accessories',
    ]);

    $product = Product::create([
        'name' => 'USB-C Cable',
        'slug' => 'usb-c-cable',
        'sku' => 'CAB-01',
        'description' => 'Fast charging cable',
        'price' => 25,
        'discount_price' => 20,
        'stock_quantity' => 3,
        'category_id' => $category->id,
        'status' => 'active',
    ]);

    $payload = [
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
        'shipping_address' => '123 Main Street',
        'billing_address' => '123 Main Street',
        'payment_method' => 'cod',
    ];

    $this->postJson('/api/orders', $payload)
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.total_amount', 40);

    expect($product->refresh()->stock_quantity)->toBe(1);

    $this->postJson('/api/orders', $payload)
        ->assertStatus(400)
        ->assertJsonPath('success', false);

    expect($product->refresh()->stock_quantity)->toBe(1);
});
