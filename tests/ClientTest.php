<?php

namespace Veda\Client\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Veda\Client\Factory;

final class ClientTest extends TestCase
{
    #[Test]
    public function it_issues_embed_tokens_with_host_credentials(): void
    {
        $transporter = new MockTransporter([
            'token' => 'veda_embed_test',
            'visitor_id' => 'visitor-1',
            'expires_in' => 3600,
        ]);

        $client = Factory::factory()
            ->withBaseUri('https://veda.test')
            ->withHostApiKey('host-secret')
            ->withTransporter($transporter)
            ->make();

        $response = $client->embed()->createToken([
            'visitor_id' => 'visitor-1',
            'host_mcp_url' => 'https://app.test/mcp/veda',
            'host_mcp_token' => 'mcp-token',
        ]);

        $this->assertSame('veda_embed_test', $response->token);
        $this->assertSame('visitor-1', $response->visitorId);
        $this->assertSame(3600, $response->expiresIn);
        $this->assertSame('POST', $transporter->requests[0]['method']);
        $this->assertSame('/veda/embed/token', $transporter->requests[0]['uri']);
        $this->assertSame('visitor-1', $transporter->requests[0]['payload']['visitor_id']);
        $this->assertSame('https://app.test/mcp/veda', $transporter->requests[0]['payload']['host_mcp_url']);
    }

    #[Test]
    public function it_streams_chat_events(): void
    {
        $transporter = new MockTransporter(
            jsonResponse: [],
            streamBody: "data: {\"type\":\"message.start\"}\n\ndata: {\"type\":\"text.delta\",\"delta\":\"Hi\"}\n\ndata: [DONE]\n\n",
        );

        $client = Factory::factory()
            ->withBaseUri('https://veda.test')
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
        $this->assertSame('/veda/stream', $transporter->requests[0]['uri']);
    }

    #[Test]
    public function it_fetches_message_and_histories(): void
    {
        $transporter = new MockTransporter(function (string $method, string $uri): array {
            return match (true) {
                $method === 'POST' && $uri === '/veda/message' => [
                    'explanation' => 'Hello',
                    'tokens_used' => 12,
                    'chat_id' => 'chat-1',
                ],
                $method === 'GET' && $uri === '/veda/chat-histories' => [
                    'histories' => [],
                ],
                default => [],
            };
        });

        $client = Factory::factory()
            ->withBaseUri('https://veda.test')
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
