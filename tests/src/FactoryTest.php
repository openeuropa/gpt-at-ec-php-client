<?php

declare(strict_types=1);

namespace Openeuropa\Tests\GptAtEcPhpClient;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use Openeuropa\GptAtEcPhpClient\Factory;
use Openeuropa\Tests\GptAtEcPhpClient\Traits\FixturesTrait;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class FactoryTest extends TestCase
{

    use FixturesTrait;

    public function testWithHttpClient(): void
    {
        $mock_http_client = $this->createMock(ClientInterface::class);
        $mock_http_client
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn(
                new Response(
                    body: json_encode($this->getFixture('responses/models/list')),
                ),
            );

        $client = (new Factory())
            ->withHttpClient($mock_http_client)
            ->make();
        $client->models()->list();
    }

    public function testWithApiKey(): void
    {
        $mock_http_client = $this->createMock(ClientInterface::class);
        $mock_http_client
            ->expects($this->once())
            ->method('sendRequest')
            ->with(
                $this->callback(function (RequestInterface $request): bool {
                    $this->assertSame(['Bearer foo'], $request->getHeader('Authorization'));

                    return true;
                })
            )
            ->willReturn(
                new Response(
                    body: json_encode($this->getFixture('responses/models/list')),
                ),
            );

        $client = (new Factory())
            ->withHttpClient($mock_http_client)
            ->withApiKey('foo')
            ->make();
        $client->models()->list();
    }

    public function testWithBaseUri(): void
    {
        $mock_http_client = $this->createMock(ClientInterface::class);
        $mock_http_client
            ->expects($this->once())
            ->method('sendRequest')
            ->with(
                $this->callback(function (RequestInterface $request): bool {
                    $this->assertSame('https://wwww.example.com/path/to/api/models', $request->getUri()->__toString());

                    return true;
                })
            )
            ->willReturn(
                new Response(
                    body: json_encode($this->getFixture('responses/models/list')),
                ),
            );

        $client = (new Factory())
            ->withHttpClient($mock_http_client)
            ->withBaseUri('https://wwww.example.com/path/to/api')
            ->make();
        $client->models()->list();
    }

    public function testWithHttpHeader(): void
    {
        $mock_http_client = $this->createMock(ClientInterface::class);
        $mock_http_client
            ->expects($this->once())
            ->method('sendRequest')
            ->with(
                $this->callback(function (RequestInterface $request): bool {
                    $this->assertSame(['bar'], $request->getHeader('foo'));
                    $this->assertSame(['qux'], $request->getHeader('baz'));

                    return true;
                })
            )
            ->willReturn(
                new Response(
                    body: json_encode($this->getFixture('responses/models/list')),
                ),
            );

        $client = (new Factory())
            ->withHttpClient($mock_http_client)
            ->withHttpHeader('foo', 'bar')
            ->withHttpHeader('baz', 'qux')
            ->make();
        $client->models()->list();
    }

    public function testWithQueryParam(): void
    {
        $mock_http_client = $this->createMock(ClientInterface::class);
        $mock_http_client
            ->expects($this->once())
            ->method('sendRequest')
            ->with(
                $this->callback(function (RequestInterface $request): bool {
                    $this->assertSame('foo=bar&baz=qux', $request->getUri()->getQuery());

                    return true;
                })
            )
            ->willReturn(
                new Response(
                    body: json_encode($this->getFixture('responses/models/list')),
                ),
            );

        $client = (new Factory())
            ->withHttpClient($mock_http_client)
            ->withQueryParam('foo', 'bar')
            ->withQueryParam('baz', 'qux')
            ->make();
        $client->models()->list();
    }

    public function testWithStreamHandler(): void
    {
        $handler_called = false;
        $client = (new Factory())
            ->withStreamHandler(function (RequestInterface $request) use (&$handler_called): ResponseInterface {
                $handler_called = true;

                return new Response(
                    body: new Stream(fopen(__DIR__ . '/../fixtures/responses/chat/streamed.txt', 'r')),
                );
            })
            ->make();
        $client->chat()->createStreamed([
            'model' => 'llama-3.3-70b-instruct',
            'messages' => [
                'role' => 'user',
                'content' => 'Hello!',
            ],
        ]);
        $this->assertTrue($handler_called);
    }

    public function testStreamHandlerWithGuzzleClient(): void
    {
        $mock_http_client = $this->createPartialMock(GuzzleClient::class, ['send']);
        $mock_http_client
            ->expects($this->once())
            ->method('send')
            ->with(
                $this->isInstanceOf(RequestInterface::class),
                $this->equalTo(['stream' => true]),
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

    public function testUnsupportedClientWithNoStreamHandler(): void
    {
        // When no stream handler is passed, only the following two clients are supported:
        // - \GuzzleHttp\Client.
        // - \Symfony\Component\HttpClient\Psr18Client.
        $mock_http_client = $this->createMock(ClientInterface::class);

        $client = (new Factory())
            ->withHttpClient($mock_http_client)
            ->make();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('To use stream requests you must provide an stream handler closure via the GPT@EC factory.');

        $client->chat()->createStreamed([
            'model' => 'llama-3.3-70b-instruct',
            'messages' => [
                'role' => 'user',
                'content' => 'Hello!',
            ],
        ]);
    }

}