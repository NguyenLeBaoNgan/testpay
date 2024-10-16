<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\DTOs\CategoryDTO;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::where('user_id', Auth::id())->get();
        return response()->json($categories);
    }


    public function store(Request $request)
    {
        $userId = Auth::id();
        $request->validate([
            'name' => 'required|string|max:255',
            // 'user_id' => $userId,
        ]);

        $existingCategory = Category::where('name', $request->name)
            ->where('user_id', $userId)
            ->first();

        if ($existingCategory) {
            return response()->json([
                'message' => 'Category already exists for this user.',
            ], 409);
        }

        $category = Category::create(
            [
                'name' => $request->name,
                'user_id' => $userId,
            ]
        );
        Log::info('Before validation', ['request_data' => $request->all()]);

        return response()->json(new CategoryDTO($category->id, $category->name, $category->user_id), 201);
    }


    public function show($id)
    {

        $category = Category::findOrFail($id);
        return response()->json($category);
    }

    public function update(Request $request, $id)
    {
        $userId = $request->user()->id;
        $category = Category::findOrFail($id);
        if ($category->user_id !== $userId) {
            return response()->json(['message' => 'Unauthorized: You do not own this Category'], 403);
        }
        $request->validate([
            'name' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer',
        ]);

        $category->update($request->only('name', 'user_id'));
        return response()->json(new CategoryDTO($category->id, $category->name, $category->user_id));
    }

    public function destroy(Request $request, $id)
    {
        $userId = $request->user()->id;
        $category = Category::findOrFail($id);
        if ($category->user_id !== $userId) {
            return response()->json(['message' => 'Unauthorized: You do not own this Category'], 403);
        }
        $category->delete();
        return response()->json(['message' => 'Category deleted successfully.'], 200);
    }
}
