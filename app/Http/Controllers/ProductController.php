<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\DTOs\ProductDTO;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    // public function index()
    // {
    //     $products = Product::with('category')->where('user_id', Auth::id())->get();

    //     $products->each(function($product) {
    //         $product->category->makeHidden('user_id');
    //     });

    //     return response()->json($products);
    // }

    public function index()
    {

        $products = Product::with('category')->get();

        $products->each(function ($product) {
            $product->category->each(function ($category) {
                $category->makeHidden('user_id');
            });
        });

        return response()->json($products);
    }
    public function store(ProductDTO $productDTO)
    {
        $categoryIds = request()->input('category_id', []);
        if (!is_array($categoryIds)) {
            $categoryIds = [$categoryIds];
        }

        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $testproduct = Product::where('name', $productDTO->name)->first();
        if ($testproduct) {
            return response()->json(['error' => 'Product already exists'], 409);
        }

        $productData = [
            'name' => $productDTO->name,
            'description' => $productDTO->description,
            'price' => $productDTO->price,
            'quantity' => $productDTO->quantity,
            // 'category_id' => $productDTO->category_id,
            'user_id' => $userId,
        ];
        if ($productDTO->image) {
            $imageName = time() . '.' . $productDTO->image->extension();
            $productDTO->image->storeAs('public/products', $imageName);
            $productData['image'] = asset('storage/products/' . $imageName);
        }
        $product = Product::create($productData);
        if (!empty($categoryIds)) {
            // $product->category()->sync($categoryIds);
            foreach ($categoryIds as $categoryId) {
                DB::table('category_product')->insert([
                    'id' => (string) Str::ulid(),
                    'category_id' => $categoryId,
                    'product_id' => $product->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        $product->load('category');
        return response()->json($product, 201);
    }


    public function show($id)
    {
        $product = Product::with('category')->findOrFail($id);
        $product->category->each(function ($category) {
            $category->makeHidden('user_id');
        });

        return response()->json($product);
    }
    // public function update(ProductDTO $productDTO, $id)
    // {
    //     // Lấy danh sách category_id từ request (nếu có)
    //     $categoryIds = request()->input('category_id', []);
    //     if (!is_array($categoryIds)) {
    //         $categoryIds = [$categoryIds];
    //     }

    //     // Kiểm tra nếu người dùng đã đăng nhập
    //     $userId = Auth::id();
    //     if (!$userId) {
    //         return response()->json(['error' => 'Unauthorized'], 401);
    //     }

    //     // Tìm sản phẩm theo id
    //     $product = Product::findOrFail($id);
    //     if (!$product) {
    //         return response()->json(['error' => 'Product not found'], 404);
    //     }

    //     // Kiểm tra xem tên sản phẩm có trùng với sản phẩm khác không
    //     $testProduct = Product::where('name', $productDTO->name)
    //         ->where('id', '!=', $id)  // Tránh trùng với sản phẩm hiện tại
    //         ->first();
    //     if ($testProduct) {
    //         return response()->json(['error' => 'Product name already exists'], 409);
    //     }

    //     // Khởi tạo mảng dữ liệu cần cập nhật
    //     $productData = [];

    //     // Cập nhật các trường nếu có giá trị hợp lệ
    //     if (!empty($productDTO->name)) {
    //         $productData['name'] = $productDTO->name;
    //     }
    //     if (!empty($productDTO->description)) {
    //         $productData['description'] = $productDTO->description;
    //     }
    //     if (isset($productDTO->price) && $productDTO->price !== '') {
    //         $productData['price'] = $productDTO->price;
    //     }
    //     if (isset($productDTO->quantity)) {
    //         $productData['quantity'] = $productDTO->quantity;
    //     }

    //     // Cập nhật hình ảnh nếu có (xử lý thay thế hình ảnh cũ)
    //     if ($productDTO->image) {
    //         // Xóa hình ảnh cũ nếu có
    //         if ($product->image) {
    //             Storage::delete('public/products/' . basename($product->image));
    //         }

    //         // Lưu hình ảnh mới
    //         $imageName = time() . '.' . $productDTO->image->extension();
    //         $productDTO->image->storeAs('public/products', $imageName);
    //         $productData['image'] = asset('storage/products/' . $imageName);
    //     }

    //     // Cập nhật sản phẩm với dữ liệu đã chọn
    //     $product->update($productData);

    //     // Cập nhật mối quan hệ với danh mục (nếu có)
    //     if (!empty($categoryIds)) {
    //         // Xóa các mối quan hệ cũ trước khi thêm mới
    //         DB::table('category_product')->where('product_id', $product->id)->delete();

    //         // Thêm các mối quan hệ mới
    //         foreach ($categoryIds as $categoryId) {
    //             DB::table('category_product')->insert([
    //                 'id' => (string) Str::ulid(),
    //                 'category_id' => $categoryId,
    //                 'product_id' => $product->id,
    //                 'created_at' => now(),
    //                 'updated_at' => now(),
    //             ]);
    //         }
    //     }

    //     // Tải lại mối quan hệ category
    //     $product->load('category');

    //     // Trả về sản phẩm đã cập nhật
    //     return response()->json($product);
    // }




    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully.']);
    }
    public function searchProduct(ProductDTO $productDTO)
    {
        Log::info('Searching data: ', (array) $productDTO);

        $query = Product::query();


        if ($productDTO->name) {
            Log::info('Applying filter on name: ', [$productDTO->name]);
            $query->where('name', 'like', "%{$productDTO->name}%");
        }


        if ($productDTO->description) {
            Log::info('Applying filter on description: ', [$productDTO->description]);
            $query->where('description', 'like', "%{$productDTO->description}%");
        }

        if ($productDTO->price) {
            Log::info('Applying filter on price: ', [$productDTO->price]);
            $query->where('price', 'like', "%{$productDTO->price}%");
        }

        if ($productDTO->category_id) {
            Log::info('Applying filter on category_id: ', [$productDTO->category_id]);
            $category = Category::where('name', 'like', "%{$productDTO->category_id}%")->first();
            if ($category) {
                $query->where('category_id', $category->id);
            } else {
                return response()->json(['message' => 'Category not found'], 404);
            }
        }

        $perPage = 1;
        $products = $query->paginate($perPage);
        // $products = $query->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No products found'], 404);
        }

        return response()->json($products);
    }
}
