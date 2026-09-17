<?php

namespace Sveda\Client\Resources;

use Sveda\Client\Exceptions\ErrorException;
use Sveda\Client\Exceptions\UnserializableResponse;
use Sveda\Client\Resources\Concerns\Transportable;

final class Documents
{
    use Transportable;

    /**
     * @param  array<int, array{path: string, filename?: string, mime?: string}>  $files
     * @return array<string, mixed>
     */
    public function extract(array $files): array
    {
        $multipart = [];

        foreach ($files as $index => $file) {
            $multipart[] = [
                'name' => 'files['.$index.']',
                'contents' => fopen($file['path'], 'r'),
                'filename' => $file['filename'] ?? basename($file['path']),
                'headers' => [
                    'Content-Type' => $file['mime'] ?? 'application/octet-stream',
                ],
            ];
        }

        $response = $this->transporter->requestMultipart('POST', '/sveda/documents/extract', $multipart);
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($status < 200 || $status >= 300) {
            $decoded = json_decode($body, true);

            throw new ErrorException(
                is_array($decoded) && isset($decoded['message']) && is_string($decoded['message'])
                    ? $decoded['message']
                    : 'Sveda document extract failed with status '.$status,
                $status,
                is_array($decoded) ? $decoded : null,
            );
        }

        $decoded = json_decode($body, true);
        if (! is_array($decoded)) {
            throw new UnserializableResponse('Unable to decode Sveda document extract response.');
        }

        return $decoded;
    }
}
