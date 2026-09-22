<?php

namespace Sveda\Client\Host;

final class McpHandler
{
    /**
     * @param  array<string, mixed>|null  $body
     * @param  array<string, mixed>|null  $headers
     * @return array{status: int, body: array<string, mixed>|null, session_id?: string}
     */
    public static function handle(
        HostManager $host,
        ?array $body,
        mixed $user,
        ?array $headers = null,
    ): array {
        $payload = $body ?? [];
        $method = (string) ($payload['method'] ?? '');
        $params = is_array($payload['params'] ?? null) ? $payload['params'] : [];
        $id = array_key_exists('id', $payload) ? $payload['id'] : null;
        $isNotification = ! array_key_exists('id', $payload);

        $normalized = self::normalizeHeaders($headers);
        $callContext = new HostCallContext(
            user: $user,
            pageContext: self::readPageContext($normalized),
            chatId: self::readChatId($normalized),
        );

        if ($method === 'notifications/initialized') {
            return ['status' => 202, 'body' => null];
        }

        if ($method === 'initialize') {
            $result = [
                'protocolVersion' => Constants::MCP_PROTOCOL_VERSION,
                'capabilities' => ['tools' => ['listChanged' => false]],
                'serverInfo' => [
                    'name' => $host->serverName !== '' ? $host->serverName : 'Host Application',
                    'version' => $host->serverVersion !== '' ? $host->serverVersion : '0.1.0',
                ],
            ];
            $instructions = trim($host->instructions);
            if ($instructions !== '') {
                $result['instructions'] = $instructions;
            }

            return [
                'status' => 200,
                'body' => self::jsonRpcResult($id, $result),
                'session_id' => 'sess-'.(int) (microtime(true) * 1000),
            ];
        }

        if ($method === 'tools/list') {
            $perPage = min(250, max(1, (int) ($params['per_page'] ?? $params['perPage'] ?? 250)));
            $tools = array_map(
                HostManifest::toolToMcpArray(...),
                $host->resolveTools($user),
            );
            $cursor = (string) ($params['cursor'] ?? '');
            $start = $cursor === '' ? 0 : max(0, (int) $cursor);
            $slice = array_slice($tools, $start, $perPage);
            $nextIndex = $start + count($slice);
            $listResult = ['tools' => $slice];
            if ($nextIndex < count($tools)) {
                $listResult['nextCursor'] = (string) $nextIndex;
            }

            return ['status' => 200, 'body' => self::jsonRpcResult($id, $listResult)];
        }

        if ($method === 'tools/call') {
            $name = (string) ($params['name'] ?? '');
            $arguments = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

            $tool = null;
            foreach ($host->resolveTools($user) as $candidate) {
                if (! method_exists($candidate, 'name') || $candidate->name() !== $name) {
                    continue;
                }
                $tool = $candidate;
                break;
            }

            if ($tool === null) {
                return [
                    'status' => 200,
                    'body' => self::jsonRpcResult($id, [
                        'content' => [['type' => 'text', 'text' => "Unknown tool: {$name}"]],
                        'isError' => true,
                    ]),
                ];
            }

            try {
                $outcome = $tool->handle($arguments, $callContext);

                return ['status' => 200, 'body' => self::jsonRpcResult($id, self::encodeToolResult($outcome))];
            } catch (\Throwable $exception) {
                $message = $exception->getMessage() !== '' ? $exception->getMessage() : 'Tool execution failed.';

                return [
                    'status' => 200,
                    'body' => self::jsonRpcResult($id, [
                        'content' => [['type' => 'text', 'text' => $message]],
                        'isError' => true,
                    ]),
                ];
            }
        }

        if ($isNotification) {
            return ['status' => 202, 'body' => null];
        }

        return [
            'status' => 200,
            'body' => self::jsonRpcError($id, -32601, "Method not found: {$method}"),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $headers
     * @return array<string, string>
     */
    private static function normalizeHeaders(?array $headers): array
    {
        $normalized = [];
        foreach ($headers ?? [] as $key => $value) {
            if (is_array($value)) {
                $normalized[strtolower((string) $key)] = isset($value[0]) ? (string) $value[0] : '';
            } else {
                $normalized[strtolower((string) $key)] = (string) $value;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, mixed>|null
     */
    private static function readPageContext(array $headers): ?array
    {
        $raw = $headers[Constants::PAGE_CONTEXT_HEADER] ?? $headers['x-sveda-page-context'] ?? '';
        if ($raw === '' || strlen($raw) > Constants::PAGE_CONTEXT_MAX_BYTES) {
            return null;
        }
        $parsed = json_decode($raw, true);

        return is_array($parsed) ? $parsed : null;
    }

    /**
     * @param  array<string, string>  $headers
     */
    private static function readChatId(array $headers): ?string
    {
        $raw = $headers[Constants::CHAT_ID_HEADER] ?? $headers['x-sveda-chat-id'] ?? '';
        $value = trim($raw);

        return $value === '' ? null : $value;
    }

    /**
     * @return array<string, mixed>
     */
    private static function jsonRpcResult(mixed $id, mixed $result): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    /**
     * @return array<string, mixed>
     */
    private static function jsonRpcError(mixed $id, int $code, string $message): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => ['code' => $code, 'message' => $message],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function encodeToolResult(mixed $result): array
    {
        if (is_string($result)) {
            $text = $result;
        } else {
            $text = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        return [
            'content' => [['type' => 'text', 'text' => $text]],
            'isError' => false,
        ];
    }
}
