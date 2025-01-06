<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\DTOs\OrderDTO;
use App\DTOs\OrderItemDTO;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function store(OrderDTO $orderDTO)
    {
        // $totalAmount = 0;
        // $userId = auth()->id();
        // foreach ($orderDTO->items as $item) {
        //     $product = Product::find($item['product_id']);

        //     if (!$product) {
        //         return response()->json(['error' => 'Product not found'], 404);
        //     }

        //     if ($product->quantity < $item['quantity']) {
        //         if ($product->quantity == 0) {
        //             return response()->json(['error' => 'Product name ' . $product->name . ' is out of stock'], 400);
        //         } else {
        //             return response()->json([
        //                 'error' => 'Insufficient stock for product name ' . $product->name,
        //                 'available_quantity' => $product->quantity
        //             ], 400);
        //         }
        //     }


        //     $product->quantity -= $item['quantity'];
        //     $product->save();

        //     Log::info("Product ID: " . $item['product_id'] . ", Price: " . $product->price);
        //     Log::info("Quantity: " . $item['quantity']);

        //     $itemTotal = $item['quantity'] * $product->price;
        //     $totalAmount += $itemTotal;
        // }
        // Log::info("Total Amount: " . $totalAmount);
        // $order = Order::create([
        //     'user_id' => $userId,
        //     'total_amount' => $totalAmount,
        //     'status' => "pending",
        // ]);
        // Log::info("Order Created: " . json_encode($order));

        // foreach ($orderDTO->items as $item) {
        //     $product = Product::find($item['product_id']);

        //     OrderItem::create([
        //         'id' => (string) Str::ulid(),
        //         'order_id' => $order->id,
        //         'product_id' => $product->id,
        //         'quantity' => $item['quantity'],
        //         'price' => $product->price,
        //         'total' => $item['quantity'] * $product->price,
        //     ]);
        // }
        // return response()->json($order, 201);

        $totalAmount = 0;
        $userId = auth()->id();

        $order = Order::create([
            // 'id' => (string) Str::ulid(),
            'user_id' => $userId,
            'total_amount' => $totalAmount,
            'status' => 'Unpaid',
        ]);
        foreach ($orderDTO->items as $item) {
            $orderItem = OrderItemDTO::fromArray($item);

            $product = Product::find($item['product_id']);
            $priceitem = $product->price;
            if($product){
                $itemTotal = $orderItem->quantity * $product->price;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $priceitem,
                ]);
                $totalAmount += $itemTotal ;
            }




        }
        $order->update(['total_amount' => $totalAmount]);

        $order->update(['status' => 'Paid']);
        return response()->json([
            'success' => true,
            'message' => 'Đơn hàng đã được tạo thành công',
            'order_id' => $order->id,
            'total_amount' => $totalAmount
        ]);
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

    public function checkStock(OrderDTO $orderDTO)
    {
        $error = [];
        foreach ($orderDTO->items as $item) {
            $product = Product::find($item['product_id']);
            if (!$product) {
                $error[] = 'Product not found';
            }
            if ($product->quantity < $item['quantity']) {
                $error[] = [
                    'error' => 'Insufficient stock for product name ' . $product->name,
                    'available_quantity' => $product->quantity,
                    'required_quantity' => $item['quantity'],
                ];
            }
        }
        if (count($error) > 0) {
            return response()->json($error, 400);
        }
        return response()->json(['message' => 'Stock available'], 200);
    }


    // public function syncCart(OrderDTO $orderDTO)
    // {
    //     $cart = [];
    //     foreach ($orderDTO->items as $item) {
    //         $product = Product::find($item['product_id']);
    //         if (!$product) {
    //             return response()->json(['error' => 'Product not found'], 404);
    //         }
    //         $cart[] = [
    //             'product_id' => $product->id,
    //             'quantity' => $item['quantity'],
    //             'price' => $product->price,
    //             'total' => $item['quantity'] * $product->price,
    //         ];
    //     }
    //     return response()->json($cart);
    // }
}
