<?php

declare(strict_types=1);

namespace Openeuropa\Tests\GptAtEcPhpClient\Resources;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Psr7\Stream;
use OpenAI\Contracts\TransporterContract;
use OpenAI\Exceptions\InvalidArgumentException;
use OpenAI\Responses\Responses\CreateStreamedResponse;
use OpenAI\Responses\Responses\Format\JsonSchemaFormat;
use OpenAI\Responses\Responses\Format\TextFormat;
use OpenAI\Responses\Responses\Output\OutputMessage;
use OpenAI\Responses\Responses\Streaming\OutputTextDelta;
use OpenAI\Responses\Responses\Streaming\Response as StreamingResponse;
use OpenAI\ValueObjects\Transporter\Response;
use Openeuropa\GptAtEcPhpClient\Client;
use Openeuropa\GptAtEcPhpClient\Resources\Responses;
use Openeuropa\Tests\GptAtEcPhpClient\Traits\ClientMockTrait;
use Openeuropa\Tests\GptAtEcPhpClient\Traits\FixturesTrait;
use PHPUnit\Framework\TestCase;

class ResponsesTest extends TestCase
{

    use ClientMockTrait;
    use FixturesTrait;

    private const array SCHEMA = [
        'type' => 'object',
        'properties' => [
            'zzz_marker' => ['type' => 'string'],
            'field_body' => ['type' => 'string'],
        ],
        'required' => ['zzz_marker', 'field_body'],
        'additionalProperties' => false,
    ];

    /**
     * Asserts the wire payload of a structured output request and the
     * hydration of the GPT@EC answer, including its "schema_" key.
     */
    public function testCreateWithJsonSchema(): void {
        $payload = [
            'model' => 'gpt-5.1',
            'input' => [
                ['role' => 'system', 'content' => 'Return ONLY JSON.'],
                ['role' => 'user', 'content' => 'Test'],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'probe',
                    'strict' => true,
                    'schema' => self::SCHEMA,
                ],
            ],
        ];

        $client = $this->getClientMock(
            'POST',
            'responses',
            $payload,
            Response::from($this->getFixture('responses/responses/create'), []),
        );

        $result = $client->responses()->create($payload);

        $this->assertSame('resp_0123456789abcdef0123456789abcdef0123456789abcdef', $result->id);
        $this->assertSame('response', $result->object);
        $this->assertSame('completed', $result->status);
        // GPT@EC returns a float; the resource casts it to an integer.
        $this->assertSame(1755523764, $result->createdAt);
        $this->assertSame('gpt-5.1-PTU-API', $result->model);

        $this->assertCount(1, $result->output);
        $this->assertInstanceOf(OutputMessage::class, $result->output[0]);
        $this->assertSame('{"zzz_marker":"x","field_body":"Test"}', $result->output[0]->content[0]->text);
        $this->assertSame(['zzz_marker' => 'x', 'field_body' => 'Test'], json_decode($result->outputText, true));

        // GPT@EC echoes "schema_"; the resource normalizes it to "schema".
        $this->assertInstanceOf(JsonSchemaFormat::class, $result->text->format);
        $this->assertSame('probe', $result->text->format->name);
        $this->assertTrue($result->text->format->strict);
        $this->assertSame(self::SCHEMA, $result->text->format->schema);

        $this->assertSame(80, $result->usage->inputTokens);
        $this->assertSame(24, $result->usage->outputTokens);
        $this->assertSame(104, $result->usage->totalTokens);
    }

    /**
     * A plain text answer has no schema and must be passed through untouched.
     */
    public function testCreateWithoutFormat(): void {
        $payload = [
            'model' => 'gpt-5.1',
            'input' => 'Hello!',
        ];

        $fixture = $this->getFixture('responses/responses/create');
        $fixture['text'] = ['format' => ['type' => 'text']];
        $fixture['created_at'] = 1755523764;

        $client = $this->getClientMock(
            'POST',
            'responses',
            $payload,
            Response::from($fixture, []),
        );

        $result = $client->responses()->create($payload);

        $this->assertInstanceOf(TextFormat::class, $result->text->format);
    }

    /**
     * A spec-compliant "schema" key must win over a stray "schema_" one.
     */
    public function testCreateWithCompliantSchemaKey(): void {
        $payload = [
            'model' => 'gpt-5.1',
            'input' => 'Hello!',
        ];

        $fixture = $this->getFixture('responses/responses/create');
        $fixture['text']['format']['schema'] = ['type' => 'object'];

        $client = $this->getClientMock(
            'POST',
            'responses',
            $payload,
            Response::from($fixture, []),
        );

        $result = $client->responses()->create($payload);

        $this->assertInstanceOf(JsonSchemaFormat::class, $result->text->format);
        $this->assertSame(['type' => 'object'], $result->text->format->schema);
    }

    public function testAvailableEndpoints(): void {
        $reflection = new \ReflectionClass(Responses::class);

        // Get public methods *declared in this class*, not inherited
        $methods = array_map(
            static fn ($method) => $method->getName(),
            $reflection->getMethods(\ReflectionMethod::IS_PUBLIC)
        );

        $this->assertEqualsCanonicalizing(
            [
                '__construct',
                'create',
                'createStreamed',
            ],
            $methods,
        );
    }

    /**
     * The "stream" parameter must go through createStreamed().
     */
    public function testCreateRejectsStreamParameter(): void {
        $transporter = $this->createMock(TransporterContract::class);
        $transporter->expects($this->never())->method('requestObject');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stream option is not supported. Please use the createStreamed() method instead.');

        (new Client($transporter))->responses()->create([
            'model' => 'gpt-5.1',
            'input' => 'Hello!',
            'stream' => true,
        ]);
    }

    /**
     * Only "stream" set to true is rejected; false is forwarded as-is.
     */
    public function testCreateForwardsDisabledStreamParameter(): void {
        $payload = [
            'model' => 'gpt-5.1',
            'input' => 'Hello!',
            'stream' => false,
        ];

        $fixture = $this->getFixture('responses/responses/create');
        $fixture['text'] = ['format' => ['type' => 'text']];

        $client = $this->getClientMock(
            'POST',
            'responses',
            $payload,
            Response::from($fixture, []),
        );

        $result = $client->responses()->create($payload);

        $this->assertSame('completed', $result->status);
    }

    /**
     * Streams a structured output response and hydrates every event,
     * including the lifecycle ones that embed the full response object.
     */
    public function testCreateStreamed(): void {
        $format = [
            'type' => 'json_schema',
            'name' => 'news_item',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string'],
                    'body' => ['type' => 'string'],
                ],
                'required' => ['title', 'body'],
                'additionalProperties' => false,
            ],
        ];

        $client = $this->getClientMock(
            'POST',
            'responses',
            [
                'model' => 'gpt-5.1',
                'input' => 'Give me a title and a body for a news item about the weather. Keep both short.',
                'text' => ['format' => $format],
                'stream' => true,
            ],
            new GuzzleResponse(
                body: new Stream(fopen(__DIR__ . '/../../fixtures/responses/responses/streamed.txt', 'r')),
            ),
            'requestStream',
        );

        $result = $client->responses()->createStreamed([
            'model' => 'gpt-5.1',
            'input' => 'Give me a title and a body for a news item about the weather. Keep both short.',
            'text' => ['format' => $format],
        ]);

        $events = [];
        foreach ($result as $event) {
            $this->assertInstanceOf(CreateStreamedResponse::class, $event);
            $events[] = $event;
        }
        $this->assertCount(70, $events);

        // The first event embeds the response object with the float
        // "created_at" and the "schema_" key, both normalized.
        $first = $events[0];
        $this->assertSame('response.created', $first->event);
        $this->assertInstanceOf(StreamingResponse::class, $first->response);
        $this->assertSame(0, $first->response->sequenceNumber);
        $this->assertSame('resp_0123456789abcdef0123456789abcdef0123456789abcdef', $first->response->response->id);
        $this->assertSame(1755523764, $first->response->response->createdAt);
        $this->assertSame('in_progress', $first->response->response->status);
        $this->assertInstanceOf(JsonSchemaFormat::class, $first->response->response->text->format);
        $this->assertSame($format['schema'], $first->response->response->text->format->schema);

        $inProgress = $events[1];
        $this->assertSame('response.in_progress', $inProgress->event);
        $this->assertInstanceOf(StreamingResponse::class, $inProgress->response);
        $this->assertSame(1, $inProgress->response->sequenceNumber);
        $this->assertSame(1755523764, $inProgress->response->response->createdAt);
        $this->assertSame('in_progress', $inProgress->response->response->status);
        $this->assertSame('response.output_item.added', $events[2]->event);
        $this->assertSame('response.content_part.added', $events[3]->event);

        $delta = $events[4];
        $this->assertSame('response.output_text.delta', $delta->event);
        $this->assertInstanceOf(OutputTextDelta::class, $delta->response);
        $this->assertSame('{"', $delta->response->delta);

        $last = end($events);
        $this->assertSame('response.completed', $last->event);
        $this->assertInstanceOf(StreamingResponse::class, $last->response);
        $this->assertSame(69, $last->response->sequenceNumber);
        $this->assertSame('completed', $last->response->response->status);
        $this->assertSame(1755523764, $last->response->response->createdAt);
        $this->assertSame($format['schema'], $last->response->response->text->format->schema);
        $this->assertSame(
            '{"title":"Stormy Conditions Sweep Across the UK","body":"Heavy rain and strong winds are forecast across much of the UK today, with localised flooding possible in low-lying areas. Travel disruption is expected, and motorists are advised to allow extra time for journeys and check for updates before setting off."}',
            $last->response->response->outputText,
        );
        $this->assertSame(67, $last->response->response->usage->inputTokens);
        $this->assertSame(76, $last->response->response->usage->outputTokens);
        $this->assertSame(143, $last->response->response->usage->totalTokens);
    }

    /**
     * A failed response goes through the same normalization as a completed one.
     */
    public function testCreateStreamedFailed(): void {
        $response = $this->getFixture('responses/responses/create');
        $response['status'] = 'failed';
        $response['output'] = [];
        $response['error'] = ['code' => 'server_error', 'message' => 'Something went wrong.'];
        $event = ['type' => 'response.failed', 'sequence_number' => 3, 'response' => $response];

        $client = $this->getClientMock(
            'POST',
            'responses',
            [
                'model' => 'gpt-5.1',
                'input' => 'Hello!',
                'stream' => true,
            ],
            new GuzzleResponse(body: Utils::streamFor('data: ' . json_encode($event) . "\n\n")),
            'requestStream',
        );

        $result = $client->responses()->createStreamed([
            'model' => 'gpt-5.1',
            'input' => 'Hello!',
        ]);

        $events = iterator_to_array($result, false);
        $this->assertCount(1, $events);

        $failed = $events[0];
        $this->assertSame('response.failed', $failed->event);
        $this->assertInstanceOf(StreamingResponse::class, $failed->response);
        $this->assertSame(3, $failed->response->sequenceNumber);
        $this->assertSame('failed', $failed->response->response->status);
        $this->assertSame(1755523764, $failed->response->response->createdAt);
        $this->assertSame('server_error', $failed->response->response->error->code);
        $this->assertSame('Something went wrong.', $failed->response->response->error->message);
        $this->assertInstanceOf(JsonSchemaFormat::class, $failed->response->response->text->format);
    }

}
