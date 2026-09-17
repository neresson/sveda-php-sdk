<?php

namespace Sveda\Client\Responses;

final readonly class MessageResponse
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
    ) {}

    public function explanation(): string
    {
        return (string) ($this->payload['explanation'] ?? '');
    }

    public function tokensUsed(): int
    {
        return (int) ($this->payload['tokens_used'] ?? 0);
    }

    public function chatId(): string
    {
        return (string) ($this->payload['chat_id'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self($payload);
    }
}
