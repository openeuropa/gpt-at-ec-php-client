<?php

declare(strict_types=1);

namespace Openeuropa\Tests\GptAtEcPhpClient\Resources;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use GuzzleHttp\Psr7\Stream;
use OpenAI\Responses\Chat\CreateResponseChoice;
use OpenAI\Responses\Chat\CreateResponseUsage;
use OpenAI\Responses\Chat\CreateStreamedResponse;
use OpenAI\Responses\Chat\CreateStreamedResponseChoice;
use OpenAI\ValueObjects\Transporter\Response;
use Openeuropa\Tests\GptAtEcPhpClient\Traits\ClientMockTrait;
use Openeuropa\Tests\GptAtEcPhpClient\Traits\FixturesTrait;
use PHPUnit\Framework\TestCase;

class ChatTest extends TestCase
{

    use ClientMockTrait;
    use FixturesTrait;

    public function testCreate(): void {
        $client = $this->getClientMock(
            'POST',
            'chat/completions',
            [
                'model' => 'gpt-4o',
                'messages' => [
                    'role' => 'user',
                    'content' => 'Hello!',
                ],
            ],
            Response::from($this->getFixture('responses/chat/create'), []),
        );

        $result = $client->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                'role' => 'user',
                'content' => 'Hello!',
            ],
        ]);


        $this->assertSame('chatcmpl-0123456789abcdef0123456789ab', $result->id);
        $this->assertSame('chat.completion', $result->object);
        $this->assertSame(1755523764, $result->created);
        $this->assertSame('gpt-4o-2000-01-01', $result->model);

        $this->assertIsArray($result->choices);
        $this->assertCount(1, $result->choices);
        foreach ($result->choices as $choice) {
            $this->assertInstanceOf(CreateResponseChoice::class, $choice);
        }

        $first = $result->choices[0];
        $this->assertSame('assistant', $first->message->role);
        $this->assertSame('Hello! How can I assist you today?', $first->message->content);
        $this->assertSame(0, $first->index);
        $this->assertNull($first->logprobs);
        $this->assertSame('stop', $first->finishReason);

        $this->assertInstanceOf(CreateResponseUsage::class, $result->usage);
        $this->assertSame(9, $result->usage->promptTokens);
        $this->assertSame(10, $result->usage->completionTokens);
        $this->assertSame(19, $result->usage->totalTokens);
    }

    public function testCreateStreamed(): void {
        $client = $this->getClientMock(
            'POST',
            'chat/completions',
            [
                'model' => 'llama-3.3-70b-instruct',
                'messages' => [
                    'role' => 'user',
                    'content' => 'Hello!',
                ],
                'stream' => true,
            ],
            new GuzzleResponse(
                body: new Stream(fopen(__DIR__ . '/../../fixtures/responses/chat/streamed.txt', 'r')),
            ),
            'requestStream',
        );

        $result = $client->chat()->createStreamed([
            'model' => 'llama-3.3-70b-instruct',
            'messages' => [
                'role' => 'user',
                'content' => 'Hello!',
            ],
        ]);

        // Fetch the first response chunk.
        $current = $result->getIterator()->current();
        $this->assertInstanceOf(CreateStreamedResponse::class, $current);
        $this->assertSame('chatcmpl-0123456789abcdef0123456789ab', $current->id);
        $this->assertSame('chat.completion.chunk', $current->object);
        $this->assertSame(1755530359, $current->created);
        $this->assertSame('meta-llama/Llama-3.3-70B-Instruct', $current->model);

        $this->assertIsArray($current->choices);
        $this->assertCount(1, $current->choices);

        $this->assertInstanceOf(CreateResponseUsage::class, $current->usage);
        $this->assertSame(3, $current->usage->promptTokens);
        $this->assertSame(0, $current->usage->completionTokens);
        $this->assertSame(3, $current->usage->totalTokens);

        $first_choice = $current->choices[0];
        $this->assertInstanceOf(CreateStreamedResponseChoice::class, $first_choice);
        // In GPT@EC, the first chunk returns the assistant but no content.
        $this->assertSame('assistant', $first_choice->delta->role);
        $this->assertSame('', $first_choice->delta->content);
        $this->assertSame(0, $first_choice->index);
        $this->assertNull($first_choice->finishReason);

        // Go to the next response chunk.
        $current = $result->getIterator()->current();
        $this->assertInstanceOf(CreateStreamedResponse::class, $current);
        $this->assertSame('chatcmpl-0123456789abcdef0123456789ab', $current->id);
        $this->assertSame('chat.completion.chunk', $current->object);
        $this->assertSame(1755530359, $current->created);
        $this->assertSame('meta-llama/Llama-3.3-70B-Instruct', $current->model);

        $this->assertIsArray($current->choices);
        $this->assertCount(1, $current->choices);

        $this->assertInstanceOf(CreateResponseUsage::class, $current->usage);
        $this->assertSame(0, $current->usage->promptTokens);
        $this->assertSame(2, $current->usage->completionTokens);
        $this->assertSame(2, $current->usage->totalTokens);

        $first_choice = $current->choices[0];
        $this->assertInstanceOf(CreateStreamedResponseChoice::class, $first_choice);
        $this->assertSame('Hello', $first_choice->delta->content);
        $this->assertSame(0, $first_choice->index);
        $this->assertNull($first_choice->delta->role);
        $this->assertNull($first_choice->finishReason);

        foreach ($result->getIterator() as $chunk) {
            $this->assertInstanceOf(CreateStreamedResponse::class, $chunk);
        }
    }

    public function testCreateAllParameters(): void {
        $payload = [
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant.',
                ],
                [
                    'role' => 'user',
                    'content' => 'Hello!',
                ],
            ],
            'top_p' => 0.5,
            'temperature' => 0.9,
            'max_tokens' => 50,
        ];

        $client = $this->getClientMock(
            'POST',
            'chat/completions',
            $payload,
            Response::from($this->getFixture('responses/chat/create'), []),
        );

        $client->chat()->create($payload);
    }

}