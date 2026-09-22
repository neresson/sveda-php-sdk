<?php

namespace Sveda\Client\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sveda\Client\Factory;

#[Group('live')]
final class LiveSmokeTest extends TestCase
{
    use SidecarContractFixture;

    private function baseUrl(): string
    {
        $value = getenv('SVEDA_BASE_URL');
        if ($value === false || trim($value) === '') {
            $this->markTestSkipped('SVEDA_BASE_URL is not set');
        }

        return rtrim(trim($value), '/');
    }

    private function hostKey(): string
    {
        $value = getenv('SVEDA_HOST_KEY');
        if ($value === false || trim($value) === '') {
            $this->markTestSkipped('SVEDA_HOST_KEY is not set');
        }

        return trim($value);
    }

    private function liveClient(): \Sveda\Client\Client
    {
        return Factory::factory()
            ->withBaseUri($this->baseUrl())
            ->withHostApiKey($this->hostKey())
            ->make();
    }

    #[Test]
    public function it_reports_health_and_ready(): void
    {
        $base = $this->baseUrl();
        $health = json_decode((string) file_get_contents($base.'/sveda/health'), true);
        $this->assertIsArray($health);
        $this->assertTrue($health['ok'] ?? false);

        $ready = json_decode((string) file_get_contents($base.'/sveda/ready'), true);
        $this->assertIsArray($ready);
        $this->assertTrue($ready['ok'] ?? false);
    }

    #[Test]
    public function it_runs_message_stream_and_history_flow(): void
    {
        $contract = $this->sidecarContract();
        $client = $this->liveClient();
        $chatId = 'sdk-compat-php-'.bin2hex(random_bytes(4));

        $token = $client->embed()->createToken(['visitor_id' => 'sdk-compat-visitor']);
        $this->assertStringStartsWith('sveda_embed_', $token->token);

        $embedClient = Factory::factory()
            ->withBaseUri($this->baseUrl())
            ->withEmbedToken($token->token)
            ->make();

        $types = [];
        foreach ($embedClient->chat()->createStreamed([
            'prompt' => 'compat stream',
            'chatId' => $chatId,
            'messages' => [['id' => 'm1', 'role' => 'user', 'content' => 'compat stream']],
        ]) as $event) {
            $types[] = $event->type;
        }
        $this->assertNotEmpty($types);
        $this->assertNotEmpty(array_intersect($types, $contract['streamEvents']));

        $message = $embedClient->chat()->create([
            'prompt' => 'compat smoke',
            'chatId' => $chatId.'-json',
            'messages' => [['id' => 'm2', 'role' => 'user', 'content' => 'compat smoke']],
        ]);
        foreach ($contract['message']['responseRequired'] as $key) {
            $this->assertNotEmpty(match ($key) {
                'explanation' => $message->explanation(),
                'tokens_used' => $message->tokensUsed(),
                'chat_id' => $message->chatId(),
                default => null,
            }, 'Missing message field: '.$key);
        }

        $histories = $embedClient->histories()->list();
        $this->assertArrayHasKey($contract['histories']['listKey'], $histories);
    }
}
