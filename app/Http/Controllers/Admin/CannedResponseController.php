<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CannedResponse;
use App\Services\CannedResponses\CannedResponseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CannedResponseController extends Controller
{
    public function index(Request $request, CannedResponseService $responses): Response
    {
        abort_unless($request->user()->can('canned_responses.manage'), 403);

        return Inertia::render('Admin/CannedResponses/Index', [
            'responses' => $responses->allForAdmin($request->user())->map(fn (CannedResponse $response): array => [
                'id' => $response->public_id,
                'title' => $response->title,
                'shortcut' => $response->shortcut,
                'body' => $response->body,
                'scope' => $response->scope,
                'status' => $response->status,
                'variables' => $response->variables ?? [],
                'owner' => $response->user?->name,
            ]),
            'scopes' => ['global', 'personal'],
            'statuses' => ['draft', 'published', 'archived'],
        ]);
    }

    public function store(Request $request, CannedResponseService $responses): RedirectResponse
    {
        abort_unless($request->user()->can('canned_responses.manage'), 403);

        $responses->store($request->user(), $this->validated($request));

        return back()->with('success', 'Canned response created.');
    }

    public function update(Request $request, CannedResponse $cannedResponse, CannedResponseService $responses): RedirectResponse
    {
        abort_unless($request->user()->can('canned_responses.manage'), 403);

        $responses->update($cannedResponse, $request->user(), $this->validated($request, partial: true));

        return back()->with('success', 'Canned response updated.');
    }

    public function destroy(Request $request, CannedResponse $cannedResponse): RedirectResponse
    {
        abort_unless($request->user()->can('canned_responses.manage'), 403);
        abort_unless($cannedResponse->scope === CannedResponse::SCOPE_GLOBAL || $cannedResponse->user_id === $request->user()->id, 403);

        $cannedResponse->delete();

        return back()->with('success', 'Canned response deleted.');
    }

    private function validated(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:160'],
            'shortcut' => ['nullable', 'string', 'max:80'],
            'body' => [$partial ? 'sometimes' : 'required', 'string', 'max:20000'],
            'scope' => [$partial ? 'sometimes' : 'required', Rule::in(['global', 'personal'])],
            'status' => [$partial ? 'sometimes' : 'required', Rule::in(['draft', 'published', 'archived'])],
            'variables' => ['nullable', 'array'],
            'variables.*' => ['string', 'max:80'],
        ]);
    }
}
