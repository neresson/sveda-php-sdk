<?php

namespace Veda\Client\Streaming;

use Psr\Http\Message\StreamInterface;

final class StreamParser
{
    public const SSE_DONE_LINE = 'data: [DONE]';

    /**
     * @var list<string>
     */
    private const STREAM_EVENTS = [
        'message.start',
        'text.delta',
        'reasoning.delta',
        'tool.call',
        'tool.result',
        'tool.progress',
        'context.usage',
        'chat.title',
        'max_steps',
        'message.end',
        'error',
    ];

    /**
     * @return \Generator<int, StreamEvent>
     */
    public function iterate(StreamInterface|string $body): \Generator
    {
        $buffer = '';

        if (is_string($body)) {
            foreach ($this->iterateLines($body) as $line) {
                $event = $this->parseLine($line);
                if ($event !== null) {
                    yield $event;
                }
            }

            return;
        }

        while (! $body->eof()) {
            $buffer .= $body->read(8192);

            while (($newlineIndex = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $newlineIndex);
                $buffer = substr($buffer, $newlineIndex + 1);

                $event = $this->parseLine($line);
                if ($event !== null) {
                    yield $event;
                }
            }
        }

        $tail = trim($buffer);
        if ($tail !== '') {
            $event = $this->parseLine($tail);
            if ($event !== null) {
                yield $event;
            }
        }
    }

    public function parseLine(string $line): ?StreamEvent
    {
        $trimmed = trim($line);
        if (! str_starts_with($trimmed, 'data:')) {
            return null;
        }

        $payload = trim(substr($trimmed, 5));
        if ($payload === '' || $payload === '[DONE]') {
            return null;
        }

        try {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($decoded)) {
            return null;
        }

        $type = $decoded['type'] ?? null;
        if (! is_string($type) || ! in_array($type, self::STREAM_EVENTS, true)) {
            return null;
        }

        return new StreamEvent($type, $decoded);
    }

    /**
     * @return \Generator<int, string>
     */
    private function iterateLines(string $content): \Generator
    {
        foreach (preg_split("/\r\n|\n|\r/", $content) ?: [] as $line) {
            yield $line;
        }
    }
}
