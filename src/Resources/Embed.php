<?php

namespace Veda\Client\Resources;

use Veda\Client\Resources\Concerns\Transportable;
use Veda\Client\Responses\EmbedTokenResponse;

final class Embed
{
    use Transportable;

    /**
     * @param  array{visitor_id?: string, host_mcp_url?: string, host_mcp_token?: string}  $params
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

        $response = $this->transporter->requestJson('POST', '/veda/embed/token', $payload);

        return EmbedTokenResponse::fromArray($response);
    }

    /**
     * @return array<string, mixed>
     */
    public function config(): array
    {
        return $this->transporter->requestJson('GET', '/veda/embed/config');
    }
}
