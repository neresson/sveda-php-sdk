<?php

namespace Sveda\Client\Streaming;

final readonly class StreamEvent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $type,
        public array $payload,
    ) {}

    public function __get(string $name): mixed
    {
        return $this->payload[$name] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->payload;
    }
}
