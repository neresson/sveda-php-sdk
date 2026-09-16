<?php

namespace Veda\Client\Resources;

use Veda\Client\Resources\Concerns\Transportable;
use Veda\Client\Responses\MessageResponse;
use Veda\Client\Streaming\StreamEvent;
use Veda\Client\Streaming\StreamParser;

final class Chat
{
    use Transportable;

    /**
     * @param  array<string, mixed>  $params
     */
    public function create(array $params): MessageResponse
    {
        $response = $this->transporter->requestJson('POST', '/veda/message', $params);

        return MessageResponse::fromArray($response);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return \Generator<int, StreamEvent>
     */
    public function createStreamed(array $params): \Generator
    {
        $stream = $this->transporter->requestStream('POST', '/veda/stream', $params);
        $parser = new StreamParser;

        return $parser->iterate($stream);
    }
}
