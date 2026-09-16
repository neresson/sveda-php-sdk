<?php

namespace Veda\Client\Responses;

final readonly class EmbedTokenResponse
{
    /**
     * @param  array<string, mixed>|null  $appearance
     */
    public function __construct(
        public string $token,
        public string $visitorId,
        public int $expiresIn,
        public ?array $appearance = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            token: (string) ($payload['token'] ?? ''),
            visitorId: (string) ($payload['visitor_id'] ?? ''),
            expiresIn: max(60, (int) ($payload['expires_in'] ?? 3600)),
            appearance: isset($payload['appearance']) && is_array($payload['appearance'])
                ? $payload['appearance']
                : null,
        );
    }
}
