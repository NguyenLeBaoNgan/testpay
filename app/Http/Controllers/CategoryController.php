<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\DTOs\CategoryDTO;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    // public function index()
    // {
    //     $categories = Category::where('user_id', Auth::id())->get();
    //     return response()->json($categories);
    // }

    public function index()
    {
        Auth::id();

        return Category::all();
    }

    public function store(CategoryDTO $categoryDTO)
    {
        $user = Auth::user();
        Log::info('STORE METHOD - User:', ['user' => $user]);

        if (!$user) {
            return response()->json(['error' => 'Unauthorized - User Not Found'], 401);
        }

        $existingCategory = Category::where('name', $categoryDTO->name)->first();
        if ($existingCategory) {
            return response()->json([
                'error' => 'Category name already exists',
                'category' => $existingCategory
            ], 409);
        }

        $category = new Category([
            'name' => $categoryDTO->name,
            'user_id' => $user->id,
        ]);

        $category->save();
        return response()->json($category, 201);
    }





    public function show($id)
    {

        $category = Category::findOrFail($id);
        return response()->json($category);
    }

    public function update(CategoryDTO $categoryDTO, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }

        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $category->update([
            'name' => $categoryDTO->name,
            'user_id' => $userId,
        ]);

        return response()->json($category, 200);
    }


    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['error' => 'Category not found'], 404);
        }

        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully'], 200);
    }
    // public function searchCategory($name)
    // {
    //     $category = Category::where('name', 'like', '%' . $name . '%')->get();
    //     return response()->json($category);
    // }
}
