<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CodeReviewRequest;
use App\Models\CodeReview;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;

class CodeReviewController extends Controller
{
    public function __construct(
        private GeminiService $analysisService
    ) {}

    /**
     * Submit code for review.
     */
    public function store(CodeReviewRequest $request): JsonResponse
    {
        $validated  = $request->validated();
        $startTime  = microtime(true);

        try {
            // Analyze code with Gemini AI
            $analysis = $this->analysisService->analyze(
                $validated['code'],
                $validated['language']
            );

            $analysisTime = (int) ((microtime(true) - $startTime) * 1000);

            // Store in database
            $review = CodeReview::create([
                'code_snippet'  => $validated['code'],
                'language'      => $validated['language'],
                'quality_score' => $analysis['quality_score'],
                'issues'        => $analysis['issues'],
                'ip_hash'       => hash('sha256', $request->ip()),
            ]);

            return response()->json([
                'success' => true,
                'review'  => [
                    'id'               => $review->id,
                    'quality_score'    => $analysis['quality_score'],
                    'issues'           => $analysis['issues'],
                    'analysis_time_ms' => $analysisTime,
                ],
            ]);

        } catch (\Exception $e) {
            $message = $e->getMessage();

            // Detect Gemini overload / rate-limit and return a friendly 503
            if (str_contains($message, '503') || str_contains($message, 'UNAVAILABLE') || str_contains($message, 'high demand')) {
                return response()->json([
                    'success' => false,
                    'error'   => 'The AI service is temporarily busy. Please wait a few seconds and try again.',
                ], 503);
            }

            return response()->json([
                'success' => false,
                'error'   => $message,
            ], 500);
        }
    }

    /**
     * Get global statistics.
     */
    public function stats(): JsonResponse
    {
        $reviews = CodeReview::all();

        if ($reviews->isEmpty()) {
            return response()->json([
                'success' => true,
                'stats'   => [
                    'total_reviews'         => 0,
                    'average_quality_score' => 0,
                    'language_breakdown'    => [],
                ],
            ]);
        }

        $languageCounts = $reviews->groupBy('language')->map->count()->toArray();
        $avgScore       = $reviews->avg('quality_score');

        return response()->json([
            'success' => true,
            'stats'   => [
                'total_reviews'         => $reviews->count(),
                'average_quality_score' => round($avgScore, 1),
                'language_breakdown'    => $languageCounts,
            ],
        ]);
    }
}
