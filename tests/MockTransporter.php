<?php

namespace Veda\Client\Tests;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Veda\Client\Contracts\Transporter;

final class MockTransporter implements Transporter
{
    /**
     * @var array<int, array{method: string, uri: string, payload: array<string, mixed>, headers: array<string, string>}>
     */
    public array $requests = [];

    /**
     * @param  array<string, mixed>|callable  $jsonResponse
     */
    public function __construct(
        private mixed $jsonResponse = [],
        private ?string $streamBody = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    public function requestJson(string $method, string $uri, array $payload = [], array $headers = []): array
    {
        $this->requests[] = compact('method', 'uri', 'payload', 'headers');

        return is_callable($this->jsonResponse)
            ? ($this->jsonResponse)($method, $uri, $payload, $headers)
            : $this->jsonResponse;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    public function requestStream(string $method, string $uri, array $payload = [], array $headers = []): StreamInterface
    {
        $this->requests[] = compact('method', 'uri', 'payload', 'headers');

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $this->streamBody ?? '');
        rewind($stream);

        return new \GuzzleHttp\Psr7\Stream($stream);
    }

    /**
     * @param  array<int, array<string, mixed>>  $multipart
     * @param  array<string, string>  $headers
     */
    public function requestMultipart(string $method, string $uri, array $multipart, array $headers = []): ResponseInterface
    {
        throw new \RuntimeException('Not implemented in mock.');
    }
}
