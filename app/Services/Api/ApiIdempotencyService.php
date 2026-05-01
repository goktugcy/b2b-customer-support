<?php

namespace App\Services\Api;

use App\Models\ApiClient;
use App\Models\ApiIdempotencyKey;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApiIdempotencyService
{
    /**
     * @param  Closure(): JsonResponse  $callback
     */
    public function handle(Request $request, ApiClient $client, Closure $callback): JsonResponse
    {
        $key = $request->header('Idempotency-Key');

        if (! $key) {
            return $callback();
        }

        return DB::transaction(function () use ($request, $client, $callback, $key): JsonResponse {
            ApiIdempotencyKey::query()->upsert([[
                'company_id' => $client->company_id,
                'api_client_id' => $client->id,
                'key' => $key,
                'method' => $request->method(),
                'path' => $request->path(),
                'request_hash' => $this->requestHash($request),
                'expires_at' => now()->addDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ]], ['api_client_id', 'key'], ['updated_at']);

            $record = ApiIdempotencyKey::query()
                ->where('api_client_id', $client->id)
                ->where('key', $key)
                ->lockForUpdate()
                ->firstOrFail();

            if ($record->request_hash !== $this->requestHash($request)) {
                throw ValidationException::withMessages([
                    'idempotency_key' => 'This idempotency key was already used with a different request body.',
                ]);
            }

            if ($record->response_status !== null) {
                return response()->json($record->response_body, $record->response_status);
            }

            $response = $callback();

            $record->update([
                'response_status' => $response->getStatusCode(),
                'response_body' => json_decode((string) $response->getContent(), true),
                'expires_at' => now()->addDay(),
            ]);

            return $response;
        });
    }

    public function findReplay(Request $request, ApiClient $client): ?JsonResponse
    {
        $key = $request->header('Idempotency-Key');

        if (! $key) {
            return null;
        }

        $record = ApiIdempotencyKey::query()
            ->where('api_client_id', $client->id)
            ->where('key', $key)
            ->first();

        if (! $record) {
            return null;
        }

        if ($record->request_hash !== $this->requestHash($request)) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'This idempotency key was already used with a different request body.',
            ]);
        }

        if ($record->response_status !== null) {
            return response()->json($record->response_body, $record->response_status);
        }

        return null;
    }

    public function store(Request $request, ApiClient $client, JsonResponse $response): void
    {
        $key = $request->header('Idempotency-Key');

        if (! $key) {
            return;
        }

        ApiIdempotencyKey::updateOrCreate([
            'api_client_id' => $client->id,
            'key' => $key,
        ], [
            'company_id' => $client->company_id,
            'method' => $request->method(),
            'path' => $request->path(),
            'request_hash' => $this->requestHash($request),
            'response_status' => $response->getStatusCode(),
            'response_body' => json_decode((string) $response->getContent(), true),
            'expires_at' => now()->addDay(),
        ]);
    }

    private function requestHash(Request $request): string
    {
        return hash('sha256', $request->method().'|'.$request->path().'|'.json_encode($request->all()));
    }
}
