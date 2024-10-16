<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\DTOs\ProductDTO;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->where('user_id', Auth::id())->get();

        $products->each(function($product) {
            $product->category->makeHidden('user_id');
        });

        return response()->json($products);
    }


    public function store(Request $request)
    {
        $userId = $request->user()->id;

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'category_id' => 'required|integer|exists:categories,id',

        ]);
        $existingProduct = Product::where('name', $request->name)
        // ->where('category_id', $request->category_id)
        ->first();
        if ($existingProduct) {

            return response()->json([
                'message' => 'Product already exists ',
            ], 409);
        }

        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'category_id' => $request->category_id,
           'user_id' => $userId,
        ]);
        return response()->json($product, 201);
    }


    public function show($id)
    {
        $product = Product::with('category')->findOrFail($id);
        return response()->json($product);
    }
    public function update(Request $request, $id)
    {


        $userId = $request->user()->id;
        $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'stock' => 'nullable|integer',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $product = Product::findOrFail($id);
        // $product->update($request->all());
        if ($product->user_id !== $userId) {
            return response()->json(['message' => 'Unauthorized: You do not own this product'], 403);
        }


        if ($request->filled('name')) {
            $product->name = $request->name;
        }
        if ($request->filled('description')) {
            $product->description = $request->description;
        }
        if ($request->filled('price')) {
            $product->price = $request->price;
        }
        if ($request->filled('stock')) {
            $product->stock = $request->stock;
        }
        if ($request->filled('category_id')) {
            $product->category_id = $request->category_id;
        }

        $product->save();

        return response()->json(new ProductDTO($product->id, $product->name, $product->description, $product->price, $product->stock, $product->category_id, $product->user_id));
    }

    public function destroy(Request $request,$id)
    {
        $userId = $request->user()->id;
        $product = Product::findOrFail($id);
        if ($product->user_id !== $userId) {
            return response()->json(['message' => 'Unauthorized: You do not own this product'], 403);
        }
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully.']);
    }
}
