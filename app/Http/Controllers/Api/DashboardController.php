<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends BaseController
{
    public function index()
    {
        $data = [
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('status', 'completed')->sum('total_amount'),
            'low_stock_products' => Product::where('stock_quantity', '<=', 10)
                ->select('id', 'name', 'stock_quantity')
                ->get(),
            'active_categories' => Category::where('status', true)->count(),
            'latest_orders' => Order::with('user')
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->user->name,
                        'total_amount' => $order->total_amount,
                        'status' => $order->status,
                        'created_at' => $order->created_at->toDateTimeString(),
                    ];
                }),
        ];

        return $this->sendResponse($data, 'Dashboard data retrieved successfully');
    }
}
