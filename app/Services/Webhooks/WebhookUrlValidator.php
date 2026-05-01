<?php

namespace App\Services\Webhooks;

use Illuminate\Validation\ValidationException;

class WebhookUrlValidator
{
    public function validate(string $url): void
    {
        $parts = parse_url($url);

        if (($parts['scheme'] ?? null) !== 'https' || empty($parts['host'])) {
            throw ValidationException::withMessages(['url' => 'Webhook endpoints must use HTTPS URLs.']);
        }

        $host = $parts['host'];

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            throw ValidationException::withMessages(['url' => 'Webhook endpoints cannot target local addresses.']);
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);

        if ($ip && ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw ValidationException::withMessages(['url' => 'Webhook endpoints cannot target private or reserved networks.']);
        }
    }
}
