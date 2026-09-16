# veda-ai/client

Framework-agnostic PHP SDK for the [Veda AI](https://github.com/neresson/veda) sidecar HTTP API.

## Install

Until the package is on Packagist, require it from GitHub:

```bash
composer config repositories.veda-client vcs https://github.com/neresson/veda-client.git
composer require veda-ai/client:dev-main
```

Or in `composer.json`:

```json
{
  "repositories": [
    { "type": "vcs", "url": "https://github.com/neresson/veda-client.git" }
  ],
  "require": {
    "veda-ai/client": "dev-main"
  }
}
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
