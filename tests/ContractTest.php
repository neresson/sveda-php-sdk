<?php

namespace Sveda\Client\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sveda\Client\Streaming\StreamParser;

final class ContractTest extends TestCase
{
    use SidecarContractFixture;

    #[Test]
    public function it_matches_sidecar_contract_version_and_done_line(): void
    {
        $contract = $this->sidecarContract();

        $this->assertSame('1.0', $contract['version']);
        $this->assertSame(StreamParser::SSE_DONE_LINE, $contract['sseDoneLine']);
    }

    #[Test]
    public function it_matches_embed_token_response_shape(): void
    {
        $contract = $this->sidecarContract();
        $transporter = new MockTransporter([
            'token' => 'sveda_embed_test',
            'visitor_id' => 'visitor-contract',
            'expires_in' => 3600,
        ]);

        $client = \Sveda\Client\Factory::factory()
            ->withBaseUri('https://sveda.test')
            ->withTransporter($transporter)
            ->make();

        $response = $client->embed()->createToken(['visitor_id' => 'visitor-contract']);

        foreach ($contract['embedToken']['responseRequired'] as $key) {
            $this->assertNotNull(match ($key) {
                'token' => $response->token,
                'visitor_id' => $response->visitorId,
                'expires_in' => $response->expiresIn,
                default => null,
            }, 'Missing required embed token field: '.$key);
        }
    }

    #[Test]
    public function it_locks_routes_headers_and_stream_events(): void
    {
        $contract = $this->sidecarContract();

        $this->assertSame('/sveda', $contract['prefix']);
        $this->assertSame(
            'application/vnd.sveda.stream+json',
            $contract['accept']['svedaStream'],
        );
        $this->assertContains('Authorization', $contract['headers']['inbound']);
        $this->assertContains('X-Sveda-Embed-Token', $contract['headers']['inbound']);

        $paths = array_map(
            static fn (array $route): string => $route['method'].' '.$route['path'],
            $contract['routes'],
        );
        foreach (
            [
                'POST /sveda/stream',
                'POST /sveda/message',
                'GET /sveda/chat-histories',
                'POST /sveda/embed/token',
            ] as $required
        ) {
            $this->assertContains($required, $paths, 'Missing contract route: '.$required);
        }

        $this->assertSame(
            [
                'message.start',
                'text.delta',
                'reasoning.delta',
                'tool.call',
                'tool.result',
                'tool.progress',
                'context.usage',
                'chat.title',
                'max_steps',
                'message.end',
                'error',
            ],
            $contract['streamEvents'],
        );
    }
}
