<?php

namespace App\Services;

use App\DTOs\FeedbackDTO;
use App\Models\Feedback;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FeedbackService
{
    public function create(FeedbackDTO $feedbackDTO)
    {
        return Feedback::create([
            'product_id' => $feedbackDTO->product_id,
            'user_id' => $feedbackDTO->user_id,
            'comment' => $feedbackDTO->comment,
            'rating' => $feedbackDTO->rating,
        ]);
    }

    public function getFBByProduct(string $productId)
    {
        return Feedback::with('user:id,name')
        ->where('product_id', $productId)
        ->orderBy('created_at', 'desc')
        ->get();
    }


    public function getBatchAverageRatings(array $productIds)
    {
        $ratings = Feedback::select(
            'product_id',
            DB::raw('AVG(rating) as avg_rating')
        )
        ->whereIn('product_id', $productIds)
        ->groupBy('product_id')
        ->get()
        ->pluck('avg_rating', 'product_id')
        ->mapWithKeys(function ($rating, $productId) {
            return [$productId => (float) $rating];
        })->all();

        return collect($productIds)->mapWithKeys(function ($productId) use ($ratings) {
            return [$productId => $ratings[$productId] ?? 0];
        })->all();
    }
}
