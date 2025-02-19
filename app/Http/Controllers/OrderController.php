<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\DTOs\OrderDTO;
use App\DTOs\OrderItemDTO;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['user', 'items.product'])->get();

        $orderitem = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'user' => $order->user->name ?? null,
                'total_amount' => $order->total_amount,
                'status' => $order->status,
                'created_at' => $order->created_at,
                'items' => $order->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product->name ?? 'Unknown',
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'total' => $item->total,
                    ];
                }),
            ];
        });

        return response()->json($orderitem);
    }

    public function store(OrderDTO $orderDTO)
    {
        $checkStockResponse = $this->checkStock($orderDTO);
        if ($checkStockResponse->getStatusCode() !== 200) {
            return $checkStockResponse;
        }
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
            if ($product) {
                $itemTotal = $orderItem->quantity * $product->price;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $priceitem,
                ]);
                $totalAmount += $itemTotal;
            }
        }
        $order->update(['total_amount' => $totalAmount]);
        Log::info('Order created', ['order_id' => $order->id]);
        // $order->update(['status' => 'Paid']);
        return response()->json([
            'success' => true,
            'message' => 'Đơn hàng đã được tạo thành công',
            'order_id' => $order->id,
            'total_amount' => $totalAmount
        ]);
    }

    public function payOrder($orderId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        if ($order->status !== 'unpaid') {
            return response()->json(['error' => 'Order is not eligible for payment'], 400);
        }


        $paymentSuccessful = true;

        if ($paymentSuccessful) {
            $order->update(['status' => 'paid']);

            return response()->json([
                'success' => true,
                'message' => 'Payment successful. Order marked as paid.',
                'order_id' => $order->id,
            ]);
        }

        return response()->json(['error' => 'Payment failed'], 500);
    }


    public function update(OrderDTO $orderDTO, $orderId)
    {
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        // $order->status = $orderDTO->status ?? $order->status;
        $order->status = in_array($orderDTO->status, ['paid', 'unpaid', 'cancelled']) ? (string) $orderDTO->status : $order->status;

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
            if ($product && $product->quantity < $item['quantity']) {
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

    public function handle()
    {
        $expiredOrders = Order::where('status', 'pending')
            ->where('created_at', '<', now()->subHours(24)) // Xóa sau 24 giờ
            ->delete();

        $this->info("Đã xóa {$expiredOrders} đơn hàng pending quá hạn.");
    }
    public function deleteUnpaidOrders()
    {

        $thresholdTime = Carbon::now()->subHours(24);


        $ordersToDelete = Order::where('status', 'unpaid')
            ->where('created_at', '<', $thresholdTime)
            ->get();

        foreach ($ordersToDelete as $order) {

            $order->items()->delete();


            $order->delete();
        }

        return response()->json([
            'success' => true,
            'message' => count($ordersToDelete) . ' unpaid orders have been deleted.',
        ]);
    }

    public function getOrderHistory()
    {
        $userId = auth()->id();
        Log::debug('User ID: ' . $userId);
        $perPage = request()->query('perPage', 10);

        // $orders = Order::where('user_id', $userId)->get();
        $orders = Order::where('user_id', $userId)->paginate($perPage);
        Log::debug('Total Orders: ' . $orders->count());
        $orderHistory = [];

        foreach ($orders as $order) {
            $items = [];

            // Lấy danh sách các items từ order và tạo DTO cho từng item
            foreach ($order->items as $item) {
                $product = Product::find($item->product_id);
                $product = $item->product;
                $quantity = is_numeric($item->quantity) ? (int)$item->quantity : 0;
                $items[] = new OrderItemDTO(
                    $product->id,
                    $quantity,
                    $item->price,
                    $product->name,

                );
                Log::debug('Item Quantity: ' . json_encode($item->quantity));

                // $items[] = new OrderItemDTO(
                //     $product->id,
                //     $product->name,

                //     $item->price,
                //     (int)$item->quantity,
                //     $item->total
                // );
            }


            // Thêm thông tin order vào mảng history
            $orderHistory[] = [

                'order_id' => $order->id,
                'total_amount' => $order->total_amount,
                'status' => $order->status,
                'items' => $items,
                'created_at' => $order->created_at->toDateString(),
            ];
        }
        Log::debug('Order History: ', $orderHistory);

        return response()->json([
            'data' => $orderHistory,
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'total' => $orders->total(),
        ], 200);
    }
    public function adminUpdate(OrderDTO $orderDTO, $orderId)
    {
        // Tìm đơn hàng theo orderId
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Cập nhật trạng thái đơn hàng (nếu có)
        if (isset($orderDTO->status)) {
            $order->status = $orderDTO->status;
        }

        // Nhóm các sản phẩm theo product_id và tính tổng số lượng
        $groupedItems = collect($orderDTO->items)->groupBy('product_id')->map(function ($items) {
            return [
                'product_id' => $items->first()['product_id'],
                'quantity' => $items->sum('quantity'),
            ];
        })->values();

        // Lấy danh sách product_id mới từ DTO
        $newProductIds = collect($orderDTO->items)->pluck('product_id')->toArray();

        // Lấy danh sách sản phẩm hiện tại trong đơn hàng, key bằng product_id
        $orderItemIds = $order->items->keyBy('product_id');

        // Tính toán tổng số tiền của đơn hàng
        $totalAmount = 0;

        // Duyệt qua từng sản phẩm trong DTO để cập nhật hoặc thêm mới
        foreach ($orderDTO->items as $item) {
            $product = Product::find($item['product_id']);

            // Kiểm tra sản phẩm có tồn tại không
            if (!$product) {
                return response()->json(['error' => 'Product not found: ' . $item['product_id']], 404);
            }

            // Tính tổng tiền cho sản phẩm hiện tại
            $itemTotal = $item['quantity'] * $product->price;
            $totalAmount += $itemTotal;

            // Nếu sản phẩm đã tồn tại trong đơn hàng, cập nhật thông tin
            if (isset($orderItemIds[$item['product_id']])) {
                $orderItem = $orderItemIds[$item['product_id']];
                $orderItem->update([
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'total' => $itemTotal,
                ]);
            } else {
                // Nếu sản phẩm chưa tồn tại, thêm mới vào đơn hàng
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

        // Xóa các sản phẩm không còn trong đơn hàng
        $order->items()->whereNotIn('product_id', $newProductIds)->delete();

        // Cập nhật tổng số tiền của đơn hàng
        $order->total_amount = $totalAmount;

        // Lưu thay đổi vào database
        $order->save();

        // Trả về thông tin đơn hàng đã cập nhật
        return response()->json($order);
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
