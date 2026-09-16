<?php

namespace Veda\Client\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Veda\Client\Streaming\StreamParser;

final class StreamParserTest extends TestCase
{
    #[Test]
    public function it_parses_stream_events_and_stops_on_done(): void
    {
        $content = implode("\n", [
            ': connected',
            '',
            'data: {"type":"message.start"}',
            '',
            'data: {"type":"text.delta","delta":"Hello"}',
            '',
            'data: {"type":"message.end","finishReason":"stop"}',
            '',
            'data: [DONE]',
            '',
        ]);

        $parser = new StreamParser;
        $events = iterator_to_array($parser->iterate($content));

        $this->assertCount(3, $events);
        $this->assertSame('message.start', $events[0]->type);
        $this->assertSame('text.delta', $events[1]->type);
        $this->assertSame('Hello', $events[1]->delta);
        $this->assertSame('message.end', $events[2]->type);
    }

    #[Test]
    public function it_ignores_invalid_lines(): void
    {
        $content = "event: ping\ndata: not-json\ndata: {\"type\":\"unknown.event\"}\n";

        $parser = new StreamParser;
        $events = iterator_to_array($parser->iterate($content));

        $this->assertSame([], $events);
    }
}
