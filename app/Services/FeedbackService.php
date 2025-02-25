<?php

namespace App\Services;

use App\DTOs\FeedbackDTO;
use App\Models\Feedback;
use Illuminate\Support\Facades\Auth;

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
}
