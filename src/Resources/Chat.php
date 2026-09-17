<?php

namespace Sveda\Client\Resources;

use Sveda\Client\Resources\Concerns\Transportable;
use Sveda\Client\Responses\MessageResponse;
use Sveda\Client\Streaming\StreamEvent;
use Sveda\Client\Streaming\StreamParser;

final class Chat
{
    use Transportable;

    /**
     * @param  array<string, mixed>  $params
     */
    public function create(array $params): MessageResponse
    {
        $response = $this->transporter->requestJson('POST', '/sveda/message', $params);

        return MessageResponse::fromArray($response);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return \Generator<int, StreamEvent>
     */
    public function createStreamed(array $params): \Generator
    {
        $stream = $this->transporter->requestStream('POST', '/sveda/stream', $params);
        $parser = new StreamParser;

        return $parser->iterate($stream);
    }
}
