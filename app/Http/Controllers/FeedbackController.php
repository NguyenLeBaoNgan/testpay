<?php

namespace App\Http\Controllers;

use App\DTOs\FeedbackDTO;
use App\Services\FeedbackService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FeedbackController extends Controller
{
    protected FeedbackService $feedbackService;

    public function __construct(FeedbackService $feedbackService)
    {
        $this->feedbackService = $feedbackService;
    }

    public function store(Request $request)
    {
        // $data = FeedbackDTO::from($request->all());
        // $feedback = $this->feedbackService->create($data);
        // return response()->json($feedback, 201);
        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'comment' => 'required|string|max:1000',
                'rating' => 'required|integer|min:1|max:5',
            ]);

            $feedbackDTO = FeedbackDTO::from([
                'product_id' => $validated['product_id'],
                'user_id' => auth()->id(),
                'comment' => $validated['comment'],
                'rating' => $validated['rating'],
            ]);

            $feedback = $this->feedbackService->create($feedbackDTO);
            $feedback->load("user:id,name");
            return response()->json([
                'message' => 'Feedback created successfully!',
                'data' => $feedback,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function index($productId)
    {
        $feedbacks = $this->feedbackService->getFBByProduct($productId);
        return response()->json($feedbacks);
    }
}
