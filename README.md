# sveda-php-sdk

Framework-agnostic PHP SDK for the [Sveda](https://sveda.dev) sidecar HTTP API.

Docs: [sveda.dev/docs/hosts/php](https://sveda.dev/docs/hosts/php)

Packagist: `sveda-ai/php-sdk`

This package is a thin transport client: embed tokens, streaming chat, histories, documents. **AI tools are not defined here** — tools live in your application and are exposed to the sidecar over MCP. On Laravel use [`sveda-ai/laravel-sdk`](https://github.com/neresson/sveda-laravel-sdk), which wires the MCP server for you; anywhere else, point the sidecar at any MCP server you run.

## Install

```bash
composer require sveda-ai/php-sdk
```

## Usage

```php
use Sveda\Client\Factory;

// Server-side client (calls the sidecar with the host key).
$client = Factory::factory()
    ->withBaseUri('https://sveda.example.com')
    ->withHostApiKey($hostKey)
    ->make();

// 1. Mint an embed session for a visitor. Pass your MCP endpoint so the
//    sidecar can discover and call your tools during the chat.
$session = $client->embed()->createToken([
    'visitor_id' => 'user-1',
    'host_mcp_url' => 'https://app.test/mcp/sveda',
    'host_mcp_token' => $mcpToken,
]);

// 2. Chat as that visitor using the embed token.
$visitor = Factory::factory()
    ->withBaseUri('https://sveda.example.com')
    ->withEmbedToken($session->token)
    ->make();

$message = $visitor->chat()->create([
    'messages' => [['role' => 'user', 'content' => 'Hello']],
    'chatId' => 'chat-1',
]);
```

## Streaming

`createStreamed()` returns a generator of `StreamEvent` objects parsed from the SSE stream:

```php
foreach ($visitor->chat()->createStreamed([
    'messages' => [['role' => 'user', 'content' => 'Hello']],
    'chatId' => 'chat-1',
]) as $event) {
    echo $event->type."\n"; // message.start, text.delta, tool.call, tool.result, context.usage, message.end, ...
}
```

To proxy the stream to a browser, forward each event as SSE (or buffer deltas and flush) — the events map 1:1 to the sidecar stream contract.

Other resources: `histories()` (list/get chats), `documents()` (text extraction), `embed()->config()` (public sidecar config).

## Custom transport

Any PSR-ish transport works — implement `Sveda\Client\Contracts\Transporter` and pass it via `->withTransporter(...)`. Guzzle is the default; timeouts and extra headers are configurable on the factory.

## License

MIT
