<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\DTOs\ProductDTO;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use Illuminate\Support\Facades\Log;

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


    public function store(ProductDTO $productDTO)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $testproduct = Product::where('name', $productDTO->name)->first();
        if ($testproduct) {
            return response()->json(['error' => 'Product already exists'], 409);
        }

        $product = new Product([
            'name' => $productDTO->name,
            'description' => $productDTO->description,
            'price' => $productDTO->price,
            'quantity' => $productDTO->quantity,
            'category_id' => $productDTO->category_id,
            'user_id' => $userId,
        ]);

        $product->save();
        return response()->json($product, 201);
    }


    public function show($id)
    {
        $product = Product::with('category')->findOrFail($id);
        return response()->json($product);
    }
    public function update(ProductDTO $productDTO, $id)
    {
        Auth::id();
        $product = Product::findOrFail($id);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $existingProduct = Product::where('name', $productDTO->name)
            ->where('id', '!=', $id)
            ->first();

        if ($existingProduct) {
            return response()->json(['error' => 'Product name already exists'], 400);
        }
        if ($productDTO->name) {
            $product->name = $productDTO->name;
        }
        if ($productDTO->description) {
            $product->description = $productDTO->description;
        }
        if ($productDTO->price) {
            $product->price = $productDTO->price;
        }
        if ($productDTO->quantity) {
            $product->quantity = $productDTO->quantity;
        }
        if ($productDTO->category_id) {
            $product->category_id = $productDTO->category_id;
        }
        $product->save();

        return response()->json($product);
    }

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
