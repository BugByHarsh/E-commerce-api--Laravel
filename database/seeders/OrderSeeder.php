<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run()
    {
        $user = User::first();
        $products = Product::take(3)->get();

        if ($user && $products->count() >= 2) {
            $firstProductUnitPrice = $products[0]->discount_price ?? $products[0]->price;
            $firstProductDiscount = $products[0]->discount_price ? ($products[0]->price - $products[0]->discount_price) : 0;
            $secondProductUnitPrice = $products[1]->discount_price ?? $products[1]->price;
            $secondProductDiscount = $products[1]->discount_price ? ($products[1]->price - $products[1]->discount_price) : 0;

            // Completed order
            $order1 = Order::create([
                'order_number' => 'ORD-001',
                'user_id' => $user->id,
                'total_amount' => $firstProductUnitPrice,
                'status' => 'completed',
                'shipping_address' => '123 Main St, City',
                'billing_address' => '123 Main St, City',
                'payment_method' => 'card',
                'payment_status' => 'paid',
                'notes' => 'Please leave at door',
            ]);

            OrderItem::create([
                'order_id' => $order1->id,
                'product_id' => $products[0]->id,
                'product_name' => $products[0]->name,
                'product_sku' => $products[0]->sku,
                'quantity' => 1,
                'unit_price' => $firstProductUnitPrice,
                'discount_applied' => $firstProductDiscount,
                'total_price' => $firstProductUnitPrice,
            ]);

            // Pending order
            $order2 = Order::create([
                'order_number' => 'ORD-002',
                'user_id' => $user->id,
                'total_amount' => $secondProductUnitPrice * 2,
                'status' => 'pending',
                'shipping_address' => '456 Oak Ave, Town',
                'billing_address' => '456 Oak Ave, Town',
                'payment_method' => 'cod',
                'payment_status' => 'pending',
                'notes' => null,
            ]);

            OrderItem::create([
                'order_id' => $order2->id,
                'product_id' => $products[1]->id,
                'product_name' => $products[1]->name,
                'product_sku' => $products[1]->sku,
                'quantity' => 2,
                'unit_price' => $secondProductUnitPrice,
                'discount_applied' => $secondProductDiscount,
                'total_price' => $secondProductUnitPrice * 2,
            ]);
        }
    }
}
