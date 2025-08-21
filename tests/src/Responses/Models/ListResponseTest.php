<?php

declare(strict_types=1);

namespace Openeuropa\Tests\GptAtEcPhpClient\Responses\Models;

use Openeuropa\GptAtEcPhpClient\Responses\Models\ListResponse;
use Openeuropa\GptAtEcPhpClient\Responses\Models\RetrieveResponse;
use Openeuropa\Tests\GptAtEcPhpClient\Traits\FixturesTrait;
use PHPUnit\Framework\TestCase;

class ListResponseTest extends TestCase
{

    use FixturesTrait;

    public function testFrom(): void
    {
        $response = ListResponse::from($this->getFixture('responses/models/list'));
        $this->assertSame('list', $response->object);

        $this->assertIsArray($response->data);
        $this->assertCount(3, $response->data);
        $this->assertContainsOnlyInstancesOf(RetrieveResponse::class, $response->data);
    }

    public function testArrayAccess(): void
    {
        $response = ListResponse::from($this->getFixture('responses/models/list'));
        $this->assertSame('list', $response['object']);

        $this->assertIsArray($response['data']);
        $this->assertCount(3, $response['data']);
    }

    public function testToArray(): void
    {
        $data = $this->getFixture('responses/models/list');
        // Avoid $data possibly being modified in the ::from method.
        $expected = $data;
        $this->assertEqualsCanonicalizing($expected, ListResponse::from($data)->toArray());
    }

}