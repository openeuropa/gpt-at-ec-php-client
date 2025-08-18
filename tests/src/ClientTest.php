<?php

declare(strict_types=1);

namespace Openeuropa\Tests\GptAtEcPhpClient;

use Openeuropa\GptAtEcPhpClient\Client;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{

    /**
     * Tests that only the GPT@EC API endpoints are accessible through the client.
     */
    public function testOnlyApiEndpointsAreAccessible(): void {
        $reflection = new \ReflectionClass(Client::class);

        // Get public methods *declared in this class*, not inherited
        $methods = array_map(
            static fn ($method) => $method->getName(),
            $reflection->getMethods(\ReflectionMethod::IS_PUBLIC)
        );

        $this->assertEqualsCanonicalizing(
            [
                '__construct',
                'chat',
                'models',
                'quotaConsumption'
            ],
            $methods,
        );
    }

}