<?php

declare(strict_types=1);

namespace Openeuropa\Tests\GptAtEcPhpClient\Responses\Models;

use Openeuropa\GptAtEcPhpClient\Responses\Models\RetrieveResponse;
use PHPUnit\Framework\TestCase;

class RetrieveResponseTest extends TestCase
{

    private array $responseData = [
        'id' => 'mistral-small-24b-instruct-2501',
        'object' => 'model',
        'created' => 1745486833029,
        'owned_by' => 'mistralai',
        'name' => 'Mistral-Small-24B-Instruct-2501',
        'description' => 'Mistral Small 3 ( 2501 ) sets a new benchmark in the "small" Large Language Models category.',
        'sensitivity_level' => [
            'PA',
            'SNC',
            'CU',
        ],
        'default_for' => [
            'SNC',
        ],
    ];

    public function testFrom(): void
    {
        $response = RetrieveResponse::from($this->responseData);

        $this->assertSame('mistral-small-24b-instruct-2501', $response->id);
        $this->assertSame('Mistral-Small-24B-Instruct-2501', $response->name);
        $this->assertSame('Mistral Small 3 ( 2501 ) sets a new benchmark in the "small" Large Language Models category.', $response->description);
        $this->assertSame([
            'PA',
            'SNC',
            'CU',
        ], $response->sensitivityLevel);
        $this->assertSame(['SNC'], $response->defaultFor);
        $this->assertSame('model', $response->object);
        $this->assertSame(1745486833029, $response->created);
        $this->assertSame('mistralai', $response->ownedBy);
    }

    public function testFromMinimumSet(): void
    {
        $data = $this->responseData;
        unset($data['created']);
        unset($data['owned_by']);

        $response = RetrieveResponse::from($data);

        $this->assertSame('mistral-small-24b-instruct-2501', $response->id);
        $this->assertSame('Mistral-Small-24B-Instruct-2501', $response->name);
        $this->assertSame('Mistral Small 3 ( 2501 ) sets a new benchmark in the "small" Large Language Models category.', $response->description);
        $this->assertSame([
            'PA',
            'SNC',
            'CU',
        ], $response->sensitivityLevel);
        $this->assertSame(['SNC'], $response->defaultFor);
        $this->assertSame('model', $response->object);
        $this->assertNull($response->created);
        $this->assertNull($response->ownedBy);
    }

    public function testArrayAccess(): void
    {
        $response = RetrieveResponse::from($this->responseData);

        // Testing a subset of properties is enough.
        $this->assertSame('mistral-small-24b-instruct-2501', $response['id']);
        $this->assertSame('Mistral-Small-24B-Instruct-2501', $response['name']);
    }

    public function testToArray(): void
    {
        // Avoid responseData possibly being modified in the ::from method.
        $expected = $this->responseData;
        $this->assertEqualsCanonicalizing($expected, RetrieveResponse::from($this->responseData)->toArray());
    }

}