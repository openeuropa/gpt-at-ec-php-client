<?php

declare(strict_types=1);

namespace Openeuropa\Tests\GptAtEcPhpClient\Resources;

use OpenAI\ValueObjects\Transporter\Response;
use Openeuropa\Tests\GptAtEcPhpClient\Traits\ClientMockTrait;
use Openeuropa\Tests\GptAtEcPhpClient\Traits\FixturesTrait;
use PHPUnit\Framework\TestCase;

class QuotaConsumptionTest extends TestCase
{

    use ClientMockTrait;
    use FixturesTrait;

    public function testRetrieve(): void
    {
        $client = $this->getClientMock(
            'GET',
            'quota-consumption/gpt-4o',
            [],
            Response::from($this->getFixture('responses/quota-consumption/gpt-4o'), []),
        );

        $result = $client->quotaConsumption()->retrieve('gpt-4o');

        $this->assertSame(1234, $result->consumedPromptTokens);
        $this->assertSame(5678, $result->consumedCompletionTokens);
        $this->assertSame(6912, $result->consumedTotalTokens);
        $this->assertSame(100000, $result->quota);
        $this->assertSame([
            'consumed_prompt_tokens' => 1234,
            'consumed_completion_tokens' => 5678,
            'consumed_total_tokens' => 6912,
            'quota' => 100000,
        ], $result->toArray());
    }

}