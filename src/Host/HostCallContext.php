<?php

namespace Sveda\Client\Host;

final class HostCallContext
{
    /**
     * @param  array<string, mixed>|null  $pageContext
     */
    public function __construct(
        public mixed $user,
        public ?array $pageContext = null,
        public ?string $chatId = null,
    ) {}
}
