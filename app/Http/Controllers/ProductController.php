<?php

namespace App\Http\Controllers;

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

        // $products->each(function ($product) {
        //     $product->category->each(function ($category) {
        //         $category->makeHidden('user_id');
        //     });
        // });

        return response()->json($products);
    }
    public function store(ProductDTO $productDTO)
    {
        Log::info('📌 Bắt đầu tạo sản phẩm', ['request' => request()->all()]);
        $categoryIds = request()->input('category_id', []);
        if (!is_array($categoryIds)) {
            $categoryIds = [$categoryIds];
        }

        $userId = Auth::id();
        if (!$userId) {
            Log::error('❌ Lỗi xác thực: Không tìm thấy user ID');
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        Log::info('✅ User xác thực thành công', ['user_id' => $userId]);

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


    public function update(ProductDTO $productDTO, $id)
    {
        $categoryIds = request()->input('category_id', []);
        if (!is_array($categoryIds)) {
            $categoryIds = [$categoryIds];
        }

        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $product = Product::findOrFail($id);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $testProduct = Product::where('name', $productDTO->name)
            ->where('id', '!=', $id)
            ->first();
        if ($testProduct) {
            return response()->json(['error' => 'Product name already exists'], 409);
        }

        $productData = [
            'name' => $productDTO->name ?? $product->name,
            'description' => $productDTO->description ?? $product->description,
            'price' => $productDTO->price ?? $product->price,
            'quantity' => $productDTO->quantity ?? $product->quantity,
        ];

        if ($productDTO->image && $productDTO->image->isValid()) {
            if ($product->image) {
                Storage::delete('public/products/' . basename($product->image));
            }
            $imageName = time() . '.' . $productDTO->image->extension();
            $productDTO->image->storeAs('public/products', $imageName);
            $productData['image'] = asset('storage/products/' . $imageName);
        }
        Log::info('Product Data: ', $productData);

        $product->update($productData);

        if (!empty($categoryIds)) {
            DB::table('category_product')->where('product_id', $product->id)->delete();
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

        return response()->json($product);
    }


    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully.']);
    }
    // public function searchProduct(ProductDTO $productDTO)
    // {
    //     Log::info('Searching data: ', (array) $productDTO);

    //     $query = Product::query();


    //     if ($productDTO->name) {
    //         Log::info('Applying filter on name: ', [$productDTO->name]);
    //         $query->where('name', 'like', "%{$productDTO->name}%");
    //     }


    //     if ($productDTO->description) {
    //         Log::info('Applying filter on description: ', [$productDTO->description]);
    //         $query->where('description', 'like', "%{$productDTO->description}%");
    //     }

    //     if ($productDTO->price) {
    //         Log::info('Applying filter on price: ', [$productDTO->price]);
    //         $query->where('price', 'like', "%{$productDTO->price}%");
    //     }

    //     if ($productDTO->category_id) {
    //         Log::info('Applying filter on category_id: ', [$productDTO->category_id]);
    //         $category = Category::where('id', $productDTO->category_id)->first();
    //         if ($category) {
    //             $query->whereHas('category', function ($q) use ($productDTO) {
    //                 $q->where('category_id', $productDTO->category_id);
    //             });
    //         } else {
    //             return response()->json(['message' => 'Category not found'], 404);
    //         }
    //     }
    //     Log::info('SQL Query: ' . $query->toSql());
    //     $perPage = 1;
    //     $products = $query->paginate($perPage);
    //     // $products = $query->get();

    //     if ($products->isEmpty()) {
    //         return response()->json(['message' => 'No products found'], 404);
    //     }

    //     return response()->json($products);
    // }
}
