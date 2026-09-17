# sveda-php-sdk

Framework-agnostic PHP SDK for the [Sveda AI](https://github.com/neresson/sveda) sidecar HTTP API.

Packagist: `sveda-ai/php-sdk`

## Install

```bash
composer require sveda-ai/php-sdk
```

## Usage

```php
use Sveda\Client\Factory;

$client = Factory::factory()
    ->withBaseUri('https://sveda.example.com')
    ->withHostApiKey($hostKey)
    ->make();

$session = $client->embed()->createToken([
    'visitor_id' => 'user-1',
    'host_mcp_url' => 'https://app.test/mcp/sveda',
    'host_mcp_token' => $mcpToken,
]);

$client = Factory::factory()
    ->withBaseUri('https://sveda.example.com')
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
