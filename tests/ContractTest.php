<?php

namespace Veda\Client\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Veda\Client\Streaming\StreamParser;

final class ContractTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function contract(): array
    {
        $path = dirname(__DIR__, 3).'/packages/protocol/contracts/sidecar.v1.json';
        $this->assertFileExists($path);

        $decoded = json_decode((string) file_get_contents($path), true);
        $this->assertIsArray($decoded);

        return $decoded;
    }

    #[Test]
    public function it_matches_sidecar_contract_version_and_done_line(): void
    {
        $contract = $this->contract();

        $this->assertSame('1.0', $contract['version']);
        $this->assertSame(StreamParser::SSE_DONE_LINE, $contract['sseDoneLine']);
    }

    #[Test]
    public function it_matches_embed_token_response_shape(): void
    {
        $contract = $this->contract();
        $transporter = new MockTransporter([
            'token' => 'veda_embed_test',
            'visitor_id' => 'visitor-contract',
            'expires_in' => 3600,
        ]);

        $client = \Veda\Client\Factory::factory()
            ->withBaseUri('https://veda.test')
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
}
