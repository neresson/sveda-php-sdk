<?php

namespace Sveda\Client\Contracts;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

interface Transporter
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    public function requestJson(string $method, string $uri, array $payload = [], array $headers = []): array;

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    public function requestStream(string $method, string $uri, array $payload = [], array $headers = []): StreamInterface;

    public function requestMultipart(string $method, string $uri, array $multipart, array $headers = []): ResponseInterface;
}
