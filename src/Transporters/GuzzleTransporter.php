<?php

namespace Sveda\Client\Transporters;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Sveda\Client\Contracts\Transporter;
use Sveda\Client\Exceptions\AuthenticationException;
use Sveda\Client\Exceptions\ErrorException;
use Sveda\Client\Exceptions\TransporterException;
use Sveda\Client\Exceptions\UnserializableResponse;

final class GuzzleTransporter implements Transporter
{
    /**
     * @param  array<string, string>  $defaultHeaders
     */
    public function __construct(
        private readonly GuzzleClient $client,
        private readonly string $baseUri,
        private readonly array $defaultHeaders = [],
        private readonly int $timeout = 30,
        private readonly int $connectTimeout = 5,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    public function requestJson(string $method, string $uri, array $payload = [], array $headers = []): array
    {
        $response = $this->send($method, $uri, [
            'json' => $payload,
            'headers' => $this->mergeHeaders($headers, [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]),
        ]);

        return $this->decodeResponse($response);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    public function requestStream(string $method, string $uri, array $payload = [], array $headers = []): StreamInterface
    {
        $response = $this->send($method, $uri, [
            'json' => $payload,
            'stream' => true,
            'headers' => $this->mergeHeaders($headers, [
                'Accept' => 'application/vnd.sveda.stream+json',
                'Content-Type' => 'application/json',
            ]),
        ]);

        return $response->getBody();
    }

    /**
     * @param  array<int, array<string, mixed>>  $multipart
     * @param  array<string, string>  $headers
     */
    public function requestMultipart(string $method, string $uri, array $multipart, array $headers = []): ResponseInterface
    {
        return $this->send($method, $uri, [
            'multipart' => $multipart,
            'headers' => $this->mergeHeaders($headers, [
                'Accept' => 'application/json',
            ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function send(string $method, string $uri, array $options): ResponseInterface
    {
        try {
            return $this->client->request($method, $this->resolveUri($uri), array_merge([
                'timeout' => $this->timeout,
                'connect_timeout' => $this->connectTimeout,
                'http_errors' => false,
            ], $options));
        } catch (GuzzleException $e) {
            throw new TransporterException($e->getMessage(), null, $e);
        }
    }

    private function resolveUri(string $uri): string
    {
        if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
            return $uri;
        }

        return rtrim($this->baseUri, '/').'/'.ltrim($uri, '/');
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, string>  $required
     * @return array<string, string>
     */
    private function mergeHeaders(array $headers, array $required): array
    {
        return array_merge($this->defaultHeaders, $required, $headers);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(ResponseInterface $response): array
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($status === 401 || $status === 403) {
            throw new AuthenticationException('Sveda API authentication failed with status '.$status);
        }

        if ($status < 200 || $status >= 300) {
            $decoded = json_decode($body, true);

            throw new ErrorException(
                is_array($decoded) && isset($decoded['message']) && is_string($decoded['message'])
                    ? $decoded['message']
                    : 'Sveda API request failed with status '.$status,
                $status,
                is_array($decoded) ? $decoded : null,
            );
        }

        if ($body === '') {
            return [];
        }

        $decoded = json_decode($body, true);
        if (! is_array($decoded)) {
            throw new UnserializableResponse('Unable to decode Sveda API response as JSON.');
        }

        return $decoded;
    }
}
