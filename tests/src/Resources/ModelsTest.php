<?php

declare(strict_types=1);

namespace Openeuropa\Tests\GptAtEcPhpClient\Resources;

use OpenAI\ValueObjects\Transporter\Response;
use Openeuropa\GptAtEcPhpClient\Resources\Models;
use Openeuropa\GptAtEcPhpClient\Responses\Models\RetrieveResponse;
use Openeuropa\Tests\GptAtEcPhpClient\Traits\ClientMockTrait;
use Openeuropa\Tests\GptAtEcPhpClient\Traits\FixturesTrait;
use PHPUnit\Framework\TestCase;

class ModelsTest extends TestCase
{
    use ClientMockTrait;
    use FixturesTrait;

    public function testList(): void
    {
        $client = $this->getClientMock(
            'GET',
            'models',
            [],
            Response::from($this->getFixture('responses/models/list'), []),
        );

        $result = $client->models()->list();

        $this->assertSame('list', $result->object);

        $this->assertIsArray($result->data);
        $this->assertCount(3, $result->data);

        foreach ($result->data as $item) {
            $this->assertInstanceOf(RetrieveResponse::class, $item);
        }

        $this->assertSame('llama-3.3-70b-instruct', $result->data[1]->id);
        $this->assertSame('LLama 3.3 70b instruct', $result->data[1]->name);
        $this->assertSame(
            'Very powerful open-weights model on par with the capabilities of GPT-4o for many types of tasks.',
            $result->data[1]->description,
        );
        $this->assertSame([
            'PA',
            'SNC',
            'CU',
        ], $result->data[1]->sensitivityLevel);
        $this->assertSame([
            'SNC',
        ], $result->data[1]->defaultFor);
        $this->assertSame('model', $result->data[1]->object);
        $this->assertSame(1744201138356, $result->data[1]->created);
        $this->assertSame('meta', $result->data[1]->ownedBy);

        $this->assertSame('mistral-small-24b-instruct-2501', $result->data[0]->id);
        $this->assertSame('gpt-4o', $result->data[2]->id);
    }

    public function testAvailableEndpoints(): void {
        $reflection = new \ReflectionClass(Models::class);

        // Get public methods *declared in this class*, not inherited
        $methods = array_map(
            static fn ($method) => $method->getName(),
            $reflection->getMethods(\ReflectionMethod::IS_PUBLIC)
        );

        $this->assertEqualsCanonicalizing(
            [
                '__construct',
                'list'
            ],
            $methods,
        );
    }

}
