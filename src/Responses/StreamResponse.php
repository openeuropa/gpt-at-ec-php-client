<?php

declare(strict_types=1);

namespace Openeuropa\GptAtEcPhpClient\Responses;

use OpenAI\Contracts\ResponseHasMetaInformationContract;
use OpenAI\Contracts\ResponseStreamContract;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Responses\Meta\MetaInformation;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Iterates the server-sent events of a streamed response.
 *
 * openai-php's StreamResponse can't be used. It is final, and it passes every
 * event outside its fixed skip list to the factory, which must return an
 * event object. An event that openai-php can't hydrate therefore throws and
 * stops the stream.
 *
 * @see \OpenAI\Responses\StreamResponse
 *
 * @template TResponse
 *
 * @implements ResponseStreamContract<TResponse>
 */
final class StreamResponse implements ResponseHasMetaInformationContract, ResponseStreamContract
{

    /**
     * The event types that are not yielded.
     *
     * The "response.reasoning_part.*" events can't be hydrated, and the
     * reasoning text they wrap comes in the "response.reasoning_text.*"
     * events. Only known types are listed, so that new events still show up.
     */
    private const array SKIPPED_TYPES = [
        'ping',
        'keepalive',
        'response.keep_alive',
        'response.reasoning_part.added',
        'response.reasoning_part.done',
    ];

    /**
     * @param class-string<TResponse> $responseClass
     *   The class whose static from() method builds each event.
     */
    public function __construct(
        private readonly string $responseClass,
        private readonly ResponseInterface $response,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): \Generator
    {
        while (!$this->response->getBody()->eof()) {
            $line = $this->readLine($this->response->getBody());

            $event = null;
            if (str_starts_with($line, 'event:')) {
                $event = trim(substr($line, strlen('event:')));
                $line = $this->readLine($this->response->getBody());
            }

            if (!str_starts_with($line, 'data:')) {
                continue;
            }

            $data = trim(substr($line, strlen('data:')));

            if ($data === '[DONE]') {
                break;
            }

            /** @var array{error?: array{message: string|array<int, string>, type: string, code: string}, type?: string} $response */
            $response = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

            if (isset($response['error'])) {
                throw new ErrorException($response['error'], $this->response);
            }

            if (isset($response['type']) && in_array($response['type'], self::SKIPPED_TYPES, true)) {
                continue;
            }

            if ($event !== null) {
                $response['__event'] = $event;
            }
            $response['__meta'] = $this->meta();

            yield $this->responseClass::from($response);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function meta(): MetaInformation
    {
        return MetaInformation::from($this->response->getHeaders());
    }

    /**
     * Reads one line from the stream.
     */
    private function readLine(StreamInterface $stream): string
    {
        $buffer = '';

        while (!$stream->eof()) {
            if ('' === ($byte = $stream->read(1))) {
                return $buffer;
            }
            $buffer .= $byte;
            if ($byte === "\n") {
                break;
            }
        }

        return $buffer;
    }

}
