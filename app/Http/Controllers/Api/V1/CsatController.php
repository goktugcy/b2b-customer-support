<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Csat\CsatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CsatController extends Controller
{
    public function submit(Request $request, string $token, CsatService $csat): JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $survey = $csat->submit($token, (int) $validated['rating'], $validated['comment'] ?? null);

        return response()->json([
            'data' => [
                'id' => $survey->public_id,
                'rating' => $survey->rating,
                'responded_at' => $survey->responded_at?->toISOString(),
            ],
        ]);
    }
}
