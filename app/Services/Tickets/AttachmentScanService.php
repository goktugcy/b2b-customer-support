<?php

namespace App\Services\Tickets;

class AttachmentScanService
{
    /**
     * @return array{status:string,result:?string}
     */
    public function scan(string $absolutePath): array
    {
        if (! config('support.clamav.enabled')) {
            return ['status' => 'skipped', 'result' => null];
        }

        $socket = @fsockopen(
            (string) config('support.clamav.host', '127.0.0.1'),
            (int) config('support.clamav.port', 3310),
            $errorCode,
            $errorMessage,
            3,
        );

        if (! $socket) {
            return ['status' => 'error', 'result' => trim($errorMessage ?: 'ClamAV connection failed.')];
        }

        try {
            fwrite($socket, "zINSTREAM\0");

            $handle = fopen($absolutePath, 'rb');

            if (! $handle) {
                return ['status' => 'error', 'result' => 'Attachment could not be opened for scanning.'];
            }

            while (! feof($handle)) {
                $chunk = fread($handle, 8192) ?: '';
                fwrite($socket, pack('N', strlen($chunk)).$chunk);
            }

            fclose($handle);
            fwrite($socket, pack('N', 0));
            $response = trim(stream_get_contents($socket) ?: '');

            if (str_contains($response, 'FOUND')) {
                return ['status' => 'infected', 'result' => $response];
            }

            if (str_contains($response, 'OK')) {
                return ['status' => 'clean', 'result' => $response];
            }

            return ['status' => 'error', 'result' => $response ?: 'ClamAV returned an empty response.'];
        } finally {
            fclose($socket);
        }
    }
}
