<?php

declare(strict_types=1);

namespace Openeuropa\Tests\GptAtEcPhpClient\Resources;

use OpenAI\Responses\Responses\Format\JsonSchemaFormat;
use OpenAI\Responses\Responses\Format\TextFormat;
use OpenAI\Responses\Responses\Output\OutputMessage;
use OpenAI\ValueObjects\Transporter\Response;
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
            ],
            $methods,
        );
    }

}
