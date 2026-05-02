<?php

namespace App\Http\Controllers;

use App\Services\Csat\CsatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CsatController extends Controller
{
    public function show(string $token, CsatService $csat): Response
    {
        $survey = $csat->findOpenByToken($token);

        abort_unless($survey, 404);

        return Inertia::render('Csat/Show', [
            'token' => $token,
            'ticket' => [
                'id' => $survey->ticket?->public_id,
                'subject' => $survey->ticket?->subject,
            ],
        ]);
    }

    public function submit(Request $request, string $token, CsatService $csat): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $csat->submit($token, (int) $validated['rating'], $validated['comment'] ?? null);

        return redirect()->route('csat.thank-you');
    }

    public function thankYou(): Response
    {
        return Inertia::render('Csat/ThankYou');
    }
}
