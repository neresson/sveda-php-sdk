<?php

namespace Sveda\Client\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sveda\Client\Factory;

final class ClientTest extends TestCase
{
    #[Test]
    public function it_issues_embed_tokens_with_host_credentials(): void
    {
        $transporter = new MockTransporter([
            'token' => 'sveda_embed_test',
            'visitor_id' => 'visitor-1',
            'expires_in' => 3600,
        ]);

        $client = Factory::factory()
            ->withBaseUri('https://sveda.test')
            ->withHostApiKey('host-secret')
            ->withTransporter($transporter)
            ->make();

        $response = $client->embed()->createToken([
            'visitor_id' => 'visitor-1',
            'host_mcp_url' => 'https://app.test/mcp/sveda',
            'host_mcp_token' => 'mcp-token',
        ]);

        $this->assertSame('sveda_embed_test', $response->token);
        $this->assertSame('visitor-1', $response->visitorId);
        $this->assertSame(3600, $response->expiresIn);
        $this->assertSame('POST', $transporter->requests[0]['method']);
        $this->assertSame('/sveda/embed/token', $transporter->requests[0]['uri']);
        $this->assertSame('visitor-1', $transporter->requests[0]['payload']['visitor_id']);
        $this->assertSame('https://app.test/mcp/sveda', $transporter->requests[0]['payload']['host_mcp_url']);
    }

    #[Test]
    public function it_forwards_policy_and_grants_on_create_token(): void
    {
        $transporter = new MockTransporter([
            'token' => 'sveda_embed_test',
            'visitor_id' => 'visitor-1',
            'expires_in' => 3600,
        ]);

        $client = Factory::factory()
            ->withBaseUri('https://sveda.test')
            ->withHostApiKey('host-secret')
            ->withTransporter($transporter)
            ->make();

        $client->embed()->createToken([
            'visitor_id' => 'visitor-1',
            'host_mcp_url' => 'https://app.test/mcp/sveda',
            'host_mcp_token' => 'mcp-token',
            'policy' => 'reader',
            'grants' => ['tools' => ['echo_message']],
        ]);

        $payload = $transporter->requests[0]['payload'];
        $this->assertSame('reader', $payload['policy']);
        $this->assertSame(['tools' => ['echo_message']], $payload['grants']);
    }

    #[Test]
    public function it_streams_chat_events(): void
    {
        $transporter = new MockTransporter(
            jsonResponse: [],
            streamBody: "data: {\"type\":\"message.start\"}\n\ndata: {\"type\":\"text.delta\",\"delta\":\"Hi\"}\n\ndata: [DONE]\n\n",
        );

        $client = Factory::factory()
            ->withBaseUri('https://sveda.test')
            ->withEmbedToken('embed-token')
            ->withTransporter($transporter)
            ->make();

        $events = iterator_to_array($client->chat()->createStreamed([
            'messages' => [['role' => 'user', 'content' => 'Hello']],
            'chatId' => 'chat-1',
        ]));

        $this->assertCount(2, $events);
        $this->assertSame('message.start', $events[0]->type);
        $this->assertSame('text.delta', $events[1]->type);
        $this->assertSame('POST', $transporter->requests[0]['method']);
        $this->assertSame('/sveda/stream', $transporter->requests[0]['uri']);
    }

    #[Test]
    public function it_fetches_message_and_histories(): void
    {
        $transporter = new MockTransporter(function (string $method, string $uri): array {
            return match (true) {
                $method === 'POST' && $uri === '/sveda/message' => [
                    'explanation' => 'Hello',
                    'tokens_used' => 12,
                    'chat_id' => 'chat-1',
                ],
                $method === 'GET' && $uri === '/sveda/chat-histories' => [
                    'histories' => [],
                ],
                default => [],
            };
        });

        $client = Factory::factory()
            ->withBaseUri('https://sveda.test')
            ->withEmbedToken('embed-token')
            ->withTransporter($transporter)
            ->make();

        $message = $client->chat()->create([
            'messages' => [['role' => 'user', 'content' => 'Hello']],
            'chatId' => 'chat-1',
        ]);

        $this->assertSame('Hello', $message->explanation());
        $this->assertSame(12, $message->tokensUsed());
        $this->assertSame('chat-1', $message->chatId());

        $histories = $client->histories()->list();
        $this->assertArrayHasKey('histories', $histories);
    }
}
