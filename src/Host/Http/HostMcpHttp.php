<?php

namespace Sveda\Client\Host\Http;

use Sveda\Client\Host\HostManager;
use Sveda\Client\Host\McpHandler;

final class HostMcpHttp
{
    /**
     * @param  array<string, mixed>  $server
     */
    public static function readBearerToken(array $server = []): string
    {
        $server = $server !== [] ? $server : $_SERVER;
        $header = $server['HTTP_AUTHORIZATION'] ?? $server['Authorization'] ?? '';
        $prefix = 'Bearer ';

        if (! is_string($header) || ! str_starts_with($header, $prefix)) {
            return '';
        }

        return trim(substr($header, strlen($prefix)));
    }

    /**
     * @param  array<string, mixed>|null  $headers
     * @return array{status: int, body: array<string, mixed>|null, session_id?: string}
     */
    public static function handlePost(
        HostManager $host,
        string $rawBody,
        mixed $user,
        ?array $headers = null,
    ): array {
        $decoded = json_decode($rawBody, true);
        $body = is_array($decoded) ? $decoded : [];

        return McpHandler::handle($host, $body, $user, $headers ?? self::requestHeaders());
    }

    /**
     * @param  array{status: int, body: array<string, mixed>|null, session_id?: string}  $result
     */
    public static function emit(array $result): void
    {
        http_response_code($result['status']);
        header('Content-Type: application/json; charset=utf-8');
        if (isset($result['session_id'])) {
            header('MCP-Protocol-Version: 2025-11-25');
            header('MCP-Session-Id: '.$result['session_id']);
        }
        if ($result['body'] !== null) {
            echo json_encode($result['body'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }
    }

    /**
     * @return array<string, string>
     */
    public static function requestHeaders(): array
    {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();

            return is_array($headers) ? $headers : [];
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (! is_string($key) || ! str_starts_with($key, 'HTTP_')) {
                continue;
            }
            $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
            $headers[$name] = is_string($value) ? $value : '';
        }

        return $headers;
    }
}
