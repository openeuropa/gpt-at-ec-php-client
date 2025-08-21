<?php

declare(strict_types=1);

namespace Openeuropa\Tests\GptAtEcPhpClient\Responses\QuotaConsumption;

use Openeuropa\GptAtEcPhpClient\Responses\QuotaConsumption\RetrieveResponse;
use Openeuropa\Tests\GptAtEcPhpClient\Traits\FixturesTrait;
use PHPUnit\Framework\TestCase;

class RetrieveResponseTest extends TestCase
{

    use FixturesTrait;

    public function testFrom(): void
    {
        $response = RetrieveResponse::from($this->getFixture('responses/quota-consumption/gpt-4o'));

        $this->assertSame(1234, $response->consumedPromptTokens);
        $this->assertSame(5678, $response->consumedCompletionTokens);
        $this->assertSame(6912, $response->consumedTotalTokens);
        $this->assertSame(100000, $response->quota);
    }

    public function testArrayAccess(): void
    {
        $response = RetrieveResponse::from($this->getFixture('responses/quota-consumption/gpt-4o'));

        $this->assertSame(1234, $response['consumed_prompt_tokens']);
        $this->assertSame(5678, $response['consumed_completion_tokens']);
        $this->assertSame(6912, $response['consumed_total_tokens']);
        $this->assertSame(100000, $response['quota']);
    }

    public function testToArray(): void
    {
        $data = $this->getFixture('responses/quota-consumption/gpt-4o');
        // Avoid $data possibly being modified in the ::from method.
        $expected = $data;
        $this->assertEqualsCanonicalizing($expected, RetrieveResponse::from($data)->toArray());
    }

}