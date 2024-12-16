<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\DTOs\OrderDTO;
use App\Models\OrderItem;
use App\DTOs\OrderItemDTO;
use App\Models\Product;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function store(OrderDTO $orderDTO)
    {
        $totalAmount = 0;
        $userId = auth()->id();
        foreach ($orderDTO->items as $item) {
            $product = Product::find($item['product_id']);

            if (!$product) {
                return response()->json(['error' => 'Product not found'], 404);
            }

            if ($product->quantity < $item['quantity']) {
                if ($product->quantity == 0) {
                    return response()->json(['error' => 'Product name ' . $product->name . ' is out of stock'], 400);
                } else {
                    return response()->json([
                        'error' => 'Insufficient stock for product name ' . $product->name,
                        'available_quantity' => $product->quantity
                    ], 400);
                }
            }

        
            $product->quantity -= $item['quantity'];
            $product->save();

            Log::info("Product ID: " . $item['product_id'] . ", Price: " . $product->price);
            Log::info("Quantity: " . $item['quantity']);

            $itemTotal = $item['quantity'] * $product->price;
            $totalAmount += $itemTotal;
        }
        Log::info("Total Amount: " . $totalAmount);
        $order = Order::create([
            'user_id' => $userId,
            'total_amount' => $totalAmount,
            'status' => "pending",
        ]);
        Log::info("Order Created: " . json_encode($order));

        foreach ($orderDTO->items as $item) {
            $product = Product::find($item['product_id']);

            OrderItem::create([
                'id' => (string) Str::ulid(),
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price' => $product->price,
                'total' => $item['quantity'] * $product->price,
            ]);
        }
        return response()->json($order, 201);
    }



    public function update(OrderDTO $orderDTO, $orderId)
    {
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $order->status = $orderDTO->status ?? $order->status;
        $groupedItems = collect($orderDTO->items)->groupBy('product_id')->map(function ($items) {
            return [
                'product_id' => $items->first()['product_id'],
                'quantity' => $items->sum('quantity'),
            ];
        })->values();


        $newProductIds = collect($orderDTO->items)->pluck('product_id')->toArray();
        $orderItemIds = $order->items->keyBy('product_id');


        $totalAmount = 0;
        foreach ($orderDTO->items as $item) {
            $product = Product::find($item['product_id']);

            if (!$product) {
                return response()->json(['error' => 'Product not found'], 404);
            }

            $itemTotal = $item['quantity'] * $product->price;
            $totalAmount += $itemTotal;

            if (isset($orderItemIds[$item['product_id']])) {
                $orderItem = $orderItemIds[$item['product_id']];
                $orderItem->update(
                    // ['order_id' => $order->id, 'product_id' => $product->id],
                    [
                        'quantity' => $item['quantity'],
                        'price' => $product->price,
                        'total' => $itemTotal,
                    ]
                );
            } else {
                OrderItem::create([
                    'id' => (string) Str::ulid(),
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'total' => $itemTotal,
                ]);
            }
        }
        $order->items()->whereNotIn('product_id', $newProductIds)->delete();

        $order->total_amount = $totalAmount;
        $order->save();
        return response()->json($order);
    }

    public function show($orderId)
    {
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }
        return response()->json($order);
    }

    public function destroy($orderId)
    {
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }
        $order->delete();
        return response()->json(['message' => 'Order deleted successfully']);
    }
}
