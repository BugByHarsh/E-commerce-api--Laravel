<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\OrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderController extends BaseController
{
    /**
     * Get all orders for authenticated user
     */
    public function index(Request $request)
    {
        $query = auth()->user()->orders()->with(['user', 'items.product']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $orders = $query->latest()->paginate(min($request->integer('per_page', 10), 100));

        return $this->sendPaginatedResponse(OrderResource::collection($orders), 'Orders retrieved successfully');
    }

    /**
     * Get single order details
     */
    public function show(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            return $this->sendError('Unauthorized', [], 403);
        }

        return $this->sendResponse(new OrderResource($order->load(['items.product', 'user'])), 'Order details');
    }

    /**
     * Place a new order
     */
    public function store(OrderRequest $request)
    {
        DB::beginTransaction();

        try {
            $totalAmount = 0;
            $orderItems = [];
            $requestedItems = collect($request->validated('items'))
                ->groupBy('product_id')
                ->map(fn (Collection $items, int $productId) => [
                    'product_id' => $productId,
                    'quantity' => $items->sum('quantity'),
                ])
                ->values();

            // Process each item: validate stock, calculate prices
            foreach ($requestedItems as $item) {
                $product = Product::query()
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->find($item['product_id']);

                if (! $product) {
                    DB::rollBack();

                    return $this->sendError('Product not found', [], 404);
                }

                if ($product->stock_quantity < $item['quantity']) {
                    DB::rollBack();

                    return $this->sendError(
                        "Insufficient stock for {$product->name}",
                        ['available' => $product->stock_quantity, 'requested' => $item['quantity']],
                        400
                    );
                }

                $unitPrice = $product->discount_price ?? $product->price;
                $discount = $product->discount_price ? ($product->price - $product->discount_price) : 0;
                $totalPrice = $unitPrice * $item['quantity'];
                $totalAmount += $totalPrice;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'discount_applied' => $discount,
                    'total_price' => $totalPrice,
                ];
            }

            // Create order
            $order = Order::create([
                'user_id' => auth()->id(),
                'total_amount' => $totalAmount,
                'shipping_address' => $request->shipping_address,
                'billing_address' => $request->billing_address,
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_method === 'cod' ? 'pending' : 'pending',
                'notes' => $request->notes,
                'status' => 'pending',
            ]);

            // Create order items and deduct stock
            foreach ($orderItems as $item) {
                $order->items()->create($item);
                Product::where('id', $item['product_id'])->decrement('stock_quantity', $item['quantity']);
            }

            DB::commit();

            return $this->sendResponse(
                new OrderResource($order->load(['items.product', 'user'])),
                'Order placed successfully',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->sendError('Order placement failed', [$e->getMessage()], 500);
        }
    }

    /**
     * Cancel an order (only pending orders)
     */
    public function cancel(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            return $this->sendError('Unauthorized', [], 403);
        }

        if ($order->status !== 'pending') {
            return $this->sendError('Only pending orders can be cancelled', [], 400);
        }

        DB::beginTransaction();

        try {
            // Restore stock
            foreach ($order->items as $item) {
                Product::where('id', $item->product_id)->increment('stock_quantity', $item->quantity);
            }

            $order->update(['status' => 'cancelled']);

            DB::commit();

            return $this->sendResponse(new OrderResource($order), 'Order cancelled successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->sendError('Cancellation failed', [$e->getMessage()], 500);
        }
    }

    /**
     * Update order status (admin only - optional)
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled',
        ]);

        $order->update(['status' => $request->status]);

        return $this->sendResponse(new OrderResource($order), 'Order status updated');
    }
}
