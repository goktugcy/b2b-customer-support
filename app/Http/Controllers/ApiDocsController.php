<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApiDocsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAccess($request);

        return view('api-docs', [
            'specUrl' => route('api-docs.openapi'),
            'userName' => $request->user()->name,
        ]);
    }

    public function openapi(Request $request): BinaryFileResponse
    {
        $this->authorizeAccess($request);

        $path = base_path('docs/openapi.yaml');

        abort_unless(File::exists($path), Response::HTTP_NOT_FOUND);

        return response()->file($path, [
            'Content-Type' => 'application/yaml; charset=UTF-8',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    private function authorizeAccess(Request $request): void
    {
        $user = $request->user();

        abort_unless($user, Response::HTTP_FORBIDDEN);

        if ($user->isProviderUser()) {
            return;
        }

        abort_unless($user->company?->hasApiDocsAccess(), Response::HTTP_FORBIDDEN);
    }
}
