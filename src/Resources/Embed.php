<?php

namespace Sveda\Client\Resources;

use Sveda\Client\Resources\Concerns\Transportable;
use Sveda\Client\Responses\EmbedTokenResponse;

final class Embed
{
    use Transportable;

    /**
     * @param  array{visitor_id?: string, host_mcp_url?: string, host_mcp_token?: string, policy?: string, grants?: array<string, mixed>}  $params
     */
    public function createToken(array $params = []): EmbedTokenResponse
    {
        $payload = [];

        if (isset($params['visitor_id']) && $params['visitor_id'] !== '') {
            $payload['visitor_id'] = $params['visitor_id'];
        }

        if (isset($params['host_mcp_url'], $params['host_mcp_token'])
            && $params['host_mcp_url'] !== ''
            && $params['host_mcp_token'] !== '') {
            $payload['host_mcp_url'] = $params['host_mcp_url'];
            $payload['host_mcp_token'] = $params['host_mcp_token'];
        }

        if (isset($params['policy']) && $params['policy'] !== '') {
            $payload['policy'] = $params['policy'];
        }

        if (isset($params['grants']) && is_array($params['grants'])) {
            $payload['grants'] = $params['grants'];
        }

        $response = $this->transporter->requestJson('POST', '/sveda/embed/token', $payload);

        return EmbedTokenResponse::fromArray($response);
    }

    /**
     * @return array<string, mixed>
     */
    public function config(): array
    {
        return $this->transporter->requestJson('GET', '/sveda/embed/config');
    }
}
