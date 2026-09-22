<?php

namespace Sveda\Client\Host;

final class HostSession
{
    /**
     * @return array{origin: string, token: string, expires_in: int, appearance: array<string, mixed>|null}
     */
    public static function start(
        HostManager $host,
        mixed $user,
        ?string $requestOrigin = null,
    ): array {
        return $host->startSession($user, $requestOrigin);
    }
}
