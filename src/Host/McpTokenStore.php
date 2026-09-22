<?php

namespace Sveda\Client\Host;

final class McpTokenStore
{
    /** @var array<string, array{user_id: string, ability: string, expires_at: float}> */
    private array $tokens = [];

    public function mint(
        string $userId,
        string $ability = Constants::DEFAULT_MCP_ABILITY,
        int $ttlSeconds = 3600,
    ): string {
        $ttl = max(60, $ttlSeconds);
        $token = bin2hex(random_bytes(32));
        $this->tokens[$token] = [
            'user_id' => $userId,
            'ability' => $ability,
            'expires_at' => microtime(true) + $ttl,
        ];

        return $token;
    }

    /**
     * @return array{user_id: string, ability: string}|null
     */
    public function verify(string $plainToken, string $expectedAbility = Constants::DEFAULT_MCP_ABILITY): ?array
    {
        $record = $this->tokens[$plainToken] ?? null;
        if ($record === null) {
            return null;
        }
        if ($record['expires_at'] <= microtime(true)) {
            unset($this->tokens[$plainToken]);

            return null;
        }
        if ($expectedAbility !== '' && $record['ability'] !== $expectedAbility) {
            return null;
        }

        return [
            'user_id' => $record['user_id'],
            'ability' => $record['ability'],
        ];
    }

    public function revokeForUser(string $userId): void
    {
        foreach ($this->tokens as $token => $record) {
            if ($record['user_id'] === $userId) {
                unset($this->tokens[$token]);
            }
        }
    }
}
