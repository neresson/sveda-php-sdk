# sveda-php-sdk

Framework-agnostic PHP SDK for the [Sveda](https://sveda.dev) sidecar HTTP API.

Docs: [sveda.dev/docs/hosts/php](https://sveda.dev/docs/hosts/php)

Packagist: `sveda-ai/php-sdk`

This package provides the sidecar HTTP client **and** a framework-agnostic host layer: register tools, serve MCP, and dump a `sveda.host/v1` manifest. On Laravel, [`sveda-ai/laravel-sdk`](https://github.com/neresson/sveda-laravel-sdk) adds JsonSchema, Sanctum, and Artisan helpers on top of this client.

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

## Host integration (tools + MCP)

Implement `Sveda\Client\Host\Contracts\HostTool`, register tools on `HostManager`, and expose `POST /mcp/sveda`:

```php
use Sveda\Client\Host\HostManager;
use Sveda\Client\Host\HostSession;
use Sveda\Client\Host\Http\HostMcpHttp;

$host = (new HostManager(
    baseUrl: 'https://sveda.example.com',
    hostApiKey: $hostKey,
))
    ->resolveToolsUsing(fn (?array $user = null) => [/* HostTool instances */])
    ->policyUsing(fn ($user) => 'agent');

// Mint embed session (includes host_mcp_url + host_mcp_token for the sidecar).
$session = HostSession::start($host, $user, requestOrigin: 'https://app.example.com');

// MCP endpoint (vanilla PHP front controller):
$token = HostMcpHttp::readBearerToken();
$auth = $host->authenticateBearerToken($token);
if ($auth === null) {
    http_response_code(401);
    exit('unauthorized');
}
$result = HostMcpHttp::handlePost($host, (string) file_get_contents('php://input'), $auth['user']);
HostMcpHttp::emit($result);
```

### Agent introspection

```bash
vendor/bin/sveda-tools bootstrap/sveda-host.php
```

`$host->describe($user)` returns JSON manifest `sveda.host/v1` (same tool payloads as MCP `tools/list`).

## Custom transport

Any PSR-ish transport works — implement `Sveda\Client\Contracts\Transporter` and pass it via `->withTransporter(...)`. Guzzle is the default; timeouts and extra headers are configurable on the factory.

## License

GNU Affero General Public License v3.0. See [LICENSE](LICENSE).
