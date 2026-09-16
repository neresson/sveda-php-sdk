# veda-ai/client

Framework-agnostic PHP SDK for the [Veda AI](https://github.com/neresson/veda) sidecar HTTP API.

## Install

```bash
composer require veda-ai/client
```

## Usage

```php
use Veda\Client\Factory;

$client = Factory::factory()
    ->withBaseUri('https://veda.example.com')
    ->withHostApiKey($hostKey)
    ->make();

$session = $client->embed()->createToken([
    'visitor_id' => 'user-1',
    'host_mcp_url' => 'https://app.test/mcp/veda',
    'host_mcp_token' => $mcpToken,
]);

$client = Factory::factory()
    ->withBaseUri('https://veda.example.com')
    ->withEmbedToken($session->token)
    ->make();

foreach ($client->chat()->createStreamed([
    'messages' => [['role' => 'user', 'content' => 'Hello']],
    'chatId' => 'chat-1',
]) as $event) {
    echo $event->type."\n";
}
```

## License

MIT
