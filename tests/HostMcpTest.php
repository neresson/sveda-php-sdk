<?php

namespace Sveda\Client\Tests;

use PHPUnit\Framework\TestCase;
use Sveda\Client\Host\Constants;
use Sveda\Client\Host\Contracts\HostTool;
use Sveda\Client\Host\HostCallContext;
use Sveda\Client\Host\HostManager;
use Sveda\Client\Host\McpHandler;

final class EchoHostTool implements HostTool
{
    public function name(): string
    {
        return 'echo_message';
    }

    public function description(): string
    {
        return 'Echo a message back.';
    }

    public function schema(): array
    {
        return [
            'message' => ['type' => 'string', 'description' => 'Message to echo', 'required' => true],
        ];
    }

    public function mode(): string
    {
        return self::MODE_READ;
    }

    public function domain(): string
    {
        return 'demo';
    }

    public function handle(array $arguments, ?HostCallContext $context = null): array
    {
        return [
            'success' => true,
            'data' => ['message' => (string) ($arguments['message'] ?? '')],
        ];
    }
}

final class HostMcpTest extends TestCase
{
    public function test_describe_matches_mcp_tools_list(): void
    {
        $host = new HostManager;
        $host->resolveToolsUsing(fn () => [new EchoHostTool]);
        $user = ['id' => 'user-1'];

        $manifest = $host->describe($user);
        $this->assertSame(Constants::HOST_MANIFEST_SCHEMA, $manifest['schema']);

        $listed = McpHandler::handle(
            $host,
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list', 'params' => ['per_page' => 250]],
            $user,
        );
        $byName = [];
        foreach ($listed['body']['result']['tools'] as $tool) {
            $byName[$tool['name']] = $tool;
        }

        foreach ($manifest['tools'] as $tool) {
            $this->assertSame($byName[$tool['name']]['description'], $tool['description']);
            $this->assertSame($byName[$tool['name']]['_meta'], $tool['_meta']);
        }
    }

    public function test_tools_call_executes_handler(): void
    {
        $host = new HostManager;
        $host->resolveToolsUsing(fn () => [new EchoHostTool]);

        $response = McpHandler::handle(
            $host,
            [
                'jsonrpc' => '2.0',
                'id' => 2,
                'method' => 'tools/call',
                'params' => ['name' => 'echo_message', 'arguments' => ['message' => 'hello']],
            ],
            ['id' => 'user-1'],
        );

        $text = $response['body']['result']['content'][0]['text'];
        $decoded = json_decode($text, true);
        $this->assertSame('hello', $decoded['data']['message'] ?? null);
    }
}
