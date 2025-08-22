<?php

declare(strict_types=1);

namespace Openeuropa\Tests\GptAtEcPhpClient;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use Openeuropa\GptAtEcPhpClient\Factory;
use Openeuropa\Tests\GptAtEcPhpClient\Mock\MockSymfonyPsr18CompatibleClient;
use Openeuropa\Tests\GptAtEcPhpClient\Traits\FixturesTrait;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpClient\Psr18Client;

if (!class_exists(Psr18Client::class)) {
    class_alias(MockSymfonyPsr18CompatibleClient::class, Psr18Client::class);
}
else {
    throw new \Exception(
        'The Psr18Client from symfony/http-client is declared final and cannot be mocked. Said library cannot be added as dependency while this test is present.',
    );
}

class SymfonyClientTest extends TestCase
{

    use FixturesTrait;

    public function testStreamHandlerWithSymfonyClient(): void
    {
        $mock_http_client = $this->createMock(Psr18Client::class);
        $mock_http_client
            ->expects($this->once())
            ->method('sendRequest')
            ->with(
                $this->isInstanceOf(RequestInterface::class),
            )
            ->willReturn(
                new Response(
                    body: new Stream(fopen(__DIR__ . '/../fixtures/responses/chat/streamed.txt', 'r')),
                ),
            );

        $client = (new Factory())
            ->withHttpClient($mock_http_client)
            ->make();
        $client->chat()->createStreamed([
            'model' => 'llama-3.3-70b-instruct',
            'messages' => [
                'role' => 'user',
                'content' => 'Hello!',
            ],
        ]);
    }

}
