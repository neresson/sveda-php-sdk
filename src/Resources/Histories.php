<?php

namespace Sveda\Client\Resources;

use Sveda\Client\Resources\Concerns\Transportable;

final class Histories
{
    use Transportable;

    /**
     * @return array<string, mixed>
     */
    public function list(): array
    {
        return $this->transporter->requestJson('GET', '/sveda/chat-histories');
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $chatId): array
    {
        return $this->transporter->requestJson(
            'GET',
            '/sveda/chat-histories/'.rawurlencode($chatId),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rename(string $chatId, string $title): array
    {
        return $this->transporter->requestJson(
            'PATCH',
            '/sveda/chat-histories/'.rawurlencode($chatId),
            ['title' => $title],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $chatId): array
    {
        return $this->transporter->requestJson(
            'DELETE',
            '/sveda/chat-histories/'.rawurlencode($chatId),
        );
    }
}
