<?php

declare(strict_types=1);

namespace Openeuropa\Tests\GptAtEcPhpClient\Responses;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GuzzleHttp\Psr7\Utils;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Responses\Meta\MetaInformation;
use Openeuropa\GptAtEcPhpClient\Responses\StreamResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class StreamResponseTest extends TestCase
{

    /**
     * Skips the known events without content and passes on unknown ones.
     */
    public function testSkippedEvents(): void {
        $types = [
            'ping',
            'response.created',
            'keepalive',
            'response.reasoning_part.added',
            'response.reasoning_text.delta',
            'response.reasoning_part.done',
            'response.keep_alive',
            'response.some_future_event',
        ];
        $body = '';
        foreach ($types as $type) {
            $body .= 'data: ' . json_encode(['type' => $type]) . "\n\n";
        }

        $events = iterator_to_array($this->getStream($body), false);

        $this->assertSame(
            ['response.created', 'response.reasoning_text.delta', 'response.some_future_event'],
            array_column($events, 'type'),
        );
        $this->assertContainsOnlyInstancesOf(MetaInformation::class, array_column($events, '__meta'));
    }

    /**
     * An "event:" line is passed on with the data line that follows it.
     */
    public function testEventLine(): void {
        $body = "event: response.created\ndata: {\"type\":\"response.created\"}\n\n"
            . ": comment\ndata: {\"type\":\"response.in_progress\"}\n\n";

        $events = iterator_to_array($this->getStream($body), false);

        $this->assertCount(2, $events);
        $this->assertSame('response.created', $events[0]['__event']);
        $this->assertArrayNotHasKey('__event', $events[1]);
    }

    /**
     * Reading stops at the "[DONE]" marker.
     */
    public function testDoneMarker(): void {
        $body = "data: {\"type\":\"response.created\"}\n\ndata: [DONE]\n\ndata: {\"type\":\"response.completed\"}\n\n";

        $events = iterator_to_array($this->getStream($body), false);

        $this->assertSame(['response.created'], array_column($events, 'type'));
    }

    /**
     * Throws an error event as an ErrorException.
     */
    public function testError(): void {
        $body = 'data: ' . json_encode(['error' => ['message' => 'Rate limit reached.', 'type' => 'requests', 'code' => '429']]) . "\n\n";

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Rate limit reached.');

        iterator_to_array($this->getStream($body), false);
    }

    /**
     * A stream that is not at its end but has nothing to read gives no event.
     */
    public function testEmptyRead(): void {
        $calls = 0;
        $body = $this->createStub(StreamInterface::class);
        $body->method('eof')->willReturnCallback(static function () use (&$calls): bool {
            return ++$calls > 2;
        });
        $body->method('read')->willReturn('');

        $stream = new StreamResponse(self::eventClass(), new GuzzleResponse(body: $body));

        $this->assertSame([], iterator_to_array($stream, false));
    }

    public function testMeta(): void {
        $stream = new StreamResponse(self::eventClass(), new GuzzleResponse(headers: ['x-request-id' => 'req_123'], body: ''));

        $this->assertSame('req_123', $stream->meta()->requestId);
    }

    /**
     * Returns a stream over the given body that yields each event as an array.
     */
    private function getStream(string $body): StreamResponse {
        return new StreamResponse(self::eventClass(), new GuzzleResponse(body: Utils::streamFor($body)));
    }

    /**
     * Returns a class whose from() method returns the event as it gets it.
     */
    private static function eventClass(): string {
        $class = new class {

            public static function from(array $attributes): array {
                return $attributes;
            }

        };

        return $class::class;
    }

}
