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
            // Completed order
            $order1 = Order::create([
                'order_number' => 'ORD-001',
                'user_id' => $user->id,
                'total_amount' => 250.00,
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
                'unit_price' => $products[0]->price,
                'discount_applied' => 0,
                'total_price' => $products[0]->price,
            ]);

            // Pending order
            $order2 = Order::create([
                'order_number' => 'ORD-002',
                'user_id' => $user->id,
                'total_amount' => 99.99,
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
                'unit_price' => $products[1]->price,
                'discount_applied' => $products[1]->discount_price ? ($products[1]->price - $products[1]->discount_price) : 0,
                'total_price' => ($products[1]->discount_price ?? $products[1]->price) * 2,
            ]);
        }
    }
}
