<?php

declare(strict_types=1);

namespace Openeuropa\Tests\GptAtEcPhpClient\Transporters;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use OpenAI\Enums\Transporter\ContentType;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Exceptions\TransporterException;
use OpenAI\Exceptions\UnserializableResponse;
use OpenAI\ValueObjects\ApiKey;
use OpenAI\ValueObjects\Transporter\BaseUri;
use OpenAI\ValueObjects\Transporter\Headers;
use OpenAI\ValueObjects\Transporter\Payload;
use OpenAI\ValueObjects\Transporter\QueryParams;
use Openeuropa\GptAtEcPhpClient\Transporters\HttpTransporter;
use Openeuropa\Tests\GptAtEcPhpClient\Mock\MockHttpClientInterface;
use Openeuropa\Tests\GptAtEcPhpClient\Traits\FixturesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class HttpTransporterTest extends TestCase
{
    use FixturesTrait;

    private MockHttpClientInterface $client;

    private HttpTransporter $httpTransporter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(MockHttpClientInterface::class);
        $this->httpTransporter = new HttpTransporter(
            $this->client,
            BaseUri::from('api.url.eu/v1/test'),
            Headers::withAuthorization(ApiKey::from('foo'))->withContentType(ContentType::JSON),
            QueryParams::create()->withParam('foo', 'bar'),
            fn(RequestInterface $request): ResponseInterface => $this->client->sendAsyncRequest($request),
        );
    }

    public function testRequestObject(): void
    {
        $payload = Payload::list('models');

        $response = new Response(
            headers: ['Content-Type' => 'application/json; charset=utf-8'],
            body: json_encode([
                [
                    'text' => 'Hey!',
                    'index' => 0,
                    'logprobs' => null,
                    'finish_reason' => 'length',
                ],
            ]),
        );

        $this->client->expects($this->once())
            ->method('sendRequest')
            ->with(
                $this->callback(function (RequestInterface $request): bool {
                    $this->assertSame('GET', $request->getMethod());
                    $this->assertSame('https', $request->getUri()->getScheme());
                    $this->assertSame('api.url.eu', $request->getUri()->getHost());
                    $this->assertSame('/v1/test/models', $request->getUri()->getPath());

                    return true;
                })
            )
            ->willReturn($response);

        $result = $this->httpTransporter->requestObject($payload);

        $this->assertSame([
            [
                'text' => 'Hey!',
                'index' => 0,
                'logprobs' => null,
                'finish_reason' => 'length',
            ],
        ], $result->data());
    }

    #[DataProvider('requestObjectErrorsDataProvider')]
    public function testRequestObjectErrors(
        int $response_status_code,
        array $response_body,
        string $expected_exception_message,
        string|int|null $expected_exception_code,
    ): void {
        $payload = Payload::list('foo');

        $response = new Response(
            $response_status_code,
            ['Content-Type' => 'application/json; charset=utf-8'],
            json_encode($response_body),
        );

        $this->client
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        try {
            $this->httpTransporter->requestObject($payload);
            $this->fail('Expected ErrorException to be thrown.');
        }
        catch (ErrorException $e) {
            $this->assertSame(
                $expected_exception_message,
                $e->getMessage()
            );
            $this->assertSame($expected_exception_code, $e->getErrorCode());
            $this->assertSame($response_status_code, $e->getStatusCode());
            // Error type is not supported.
            $this->assertNull($e->getErrorType());
        }
    }

    public static function requestObjectErrorsDataProvider(): \Generator {
        yield 'full scenario' => [
            500,
            [
                'code' => '900900',
                'message' => 'Unclassified Authentication Failure',
                'description' => 'Access failure for API: /ecgpt/v1, version: v1 status: (900900) - Unclassified Authentication Failure',
            ],
            'Access failure for API: /ecgpt/v1, version: v1 status: (900900) - Unclassified Authentication Failure',
            '900900',
        ];

        yield 'null message' => [
            500,
            [
                'code' => '900900',
                'message' => null,
                'description' => 'Access failure for API: /ecgpt/v1, version: v1 status: (900900) - Unclassified Authentication Failure',
            ],
            'Access failure for API: /ecgpt/v1, version: v1 status: (900900) - Unclassified Authentication Failure',
            '900900',
        ];

        yield 'message with null description' => [
            401,
            [
                'code' => '1234',
                'message' => 'Failure message',
                'description' => null,
            ],
            '1234: Failure message',
            '1234',
        ];

        // empty() is used as description could be an array.
        yield 'message with "0" description' => [
            401,
            [
                'code' => '1234',
                'message' => 'Failure message',
                'description' => '0',
            ],
            '1234: Failure message',
            '1234',
        ];

        yield 'null error code' => [
            400,
            [
                'code' => null,
                'message' => 'Failure message',
                'description' => 'Failure description',
            ],
            'Failure description',
            null,
        ];

        yield 'null error code, no description' => [
            400,
            [
                'code' => null,
                'message' => 'Failure message',
                'description' => null,
            ],
            'Failure message',
            null,
        ];

        yield 'integer error code' => [
            400,
            [
                'code' => 9489,
                'message' => 'Failure message',
                'description' => 'Failure description',
            ],
            'Failure description',
            9489,
        ];

        yield 'error code, message but no description' => [
            400,
            [
                'code' => 'bar',
                'message' => 'Failure message',
            ],
            'bar: Failure message',
            'bar',
        ];

        yield 'no error code, no message' => [
            400,
            [
                'description' => 'Failure description',
            ],
            'Failure description',
            null,
        ];

        yield 'array description' => [
            400,
            [
                'code' => 1234,
                'description' => [
                    'foo' => 'bar',
                    'xyz',
                    'baz' => 'Lorem ipsum',
                ],
                'message' => 'Failure message',
            ],
            "bar\nxyz\nLorem ipsum",
            1234,
        ];

        yield 'description present, detail ignored' => [
            404,
            [
                'code' => 12345,
                'description' => 'Failure description',
                'message' => 'Failure message',
                'detail' => ['foo' => 'bar'],
            ],
            'Failure description',
            12345,
        ];

        yield 'message present, detail ignored' => [
            404,
            [
                'code' => 12345,
                'message' => 'Failure message',
                'detail' => ['foo' => 'bar'],
            ],
            '12345: Failure message',
            12345,
        ];

        yield 'detail and code' => [
            404,
            [
                'code' => 12345,
                'detail' => ['foo' => 'bar'],
            ],
            json_encode(['foo' => 'bar']),
            12345,
        ];

        yield 'only detail' => [
            404,
            [
                'detail' => ['foo' => 'bar'],
            ],
            json_encode(['foo' => 'bar']),
            null,
        ];

        yield 'string detail' => [
            400,
            [
                'detail' => 'String detail',
            ],
            '"String detail"',
            null,
        ];
    }

    public function testRequestObjectClientError(): void
    {
        $payload = Payload::list('models');

        $baseUri = BaseUri::from('api.url.eu/v1');
        $headers = Headers::withAuthorization(ApiKey::from('foo'));
        $queryParams = QueryParams::create();

        $this->client->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(
                new ConnectException(
                    'Could not resolve host.',
                    $payload->toRequest($baseUri, $headers, $queryParams)
                )
            );

        try {
            $this->httpTransporter->requestObject($payload);
            $this->fail('Expected TransporterException to be thrown.');
        } catch (TransporterException $e) {
            $this->assertSame('Could not resolve host.', $e->getMessage());
            $this->assertSame(0, $e->getCode());
            $this->assertInstanceOf(ConnectException::class, $e->getPrevious());
        }
    }

    public function testRequestObjectClientErrorInResponse(): void
    {
        $payload = Payload::list('models');

        $baseUri = BaseUri::from('api.url.eu/v1');
        $headers = Headers::withAuthorization(ApiKey::from('foo'));
        $queryParams = QueryParams::create();

        $this->client->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(
                new ClientException(
                    message: 'Could not resolve host.',
                    request: $payload->toRequest($baseUri, $headers, $queryParams),
                    response: new Response(401, ['Content-Type' => 'application/json; charset=utf-8'], json_encode([
                        'code' => '900900',
                        'message' => 'Unclassified Authentication Failure',
                        'description' => 'Access failure for API: /ecgpt/v1, version: v1 status: (900900) - Unclassified Authentication Failure',
                    ]))
                )
            );

        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Access failure for API: /ecgpt/v1, version: v1 status: (900900) - Unclassified Authentication Failure');

        $this->httpTransporter->requestObject($payload);
    }

    public function testRequestObjectSerialisationErrors(): void
    {
        $payload = Payload::list('models');

        $response = new Response(200, ['Content-Type' => 'application/json; charset=utf-8'], 'err');

        $this->client
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $this->expectException(UnserializableResponse::class);
        $this->expectExceptionMessage('Syntax error');

        $this->httpTransporter->requestObject($payload);
    }

    public function testRequestObjectPlainTextResponse(): void
    {
        $payload = Payload::create('foo', []);

        $response = new Response(
            200,
            ['Content-Type' => 'text/plain; charset=utf-8'],
            'Hello, how are you?'
        );

        $this->client
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $result = $this->httpTransporter->requestObject($payload);

        $this->assertSame('Hello, how are you?', $result->data());
    }

    public function testRequestContent(): void
    {
        $payload = Payload::list('foo');

        $response = new Response(200, [], 'Response body');

        $this->client->expects($this->once())
            ->method('sendRequest')
            ->with(
                $this->callback(function (RequestInterface $request): bool {
                    $this->assertSame('GET', $request->getMethod());
                    $this->assertSame('https', $request->getUri()->getScheme());
                    $this->assertSame('api.url.eu', $request->getUri()->getHost());
                    $this->assertSame('/v1/test/foo', $request->getUri()->getPath());

                    return true;
                })
            )
            ->willReturn($response);

        $result = $this->httpTransporter->requestContent($payload);
        $this->assertSame('Response body', $result);
    }

    public function testRequestContentClientErrors(): void
    {
        $payload = Payload::list('models');

        $baseUri = BaseUri::from('api.url.eu/v1');
        $headers = Headers::withAuthorization(ApiKey::from('foo'));
        $queryParams = QueryParams::create();

        $this->client->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(
                new ConnectException(
                    'Could not resolve host.',
                    $payload->toRequest($baseUri, $headers, $queryParams)
                )
            );

        try {
            $this->httpTransporter->requestContent($payload);
            $this->fail('Expected TransporterException to be thrown.');
        } catch (TransporterException $e) {
            $this->assertSame('Could not resolve host.', $e->getMessage());
            $this->assertSame(0, $e->getCode());
            $this->assertInstanceOf(ConnectException::class, $e->getPrevious());
        }
    }

    public function _test_request_content_server_errors(): void
    {
        $payload = Payload::list('models');

        $response = new Response(401, ['Content-Type' => 'application/json; charset=utf-8'], json_encode([
            'error' => [
                'message' => 'Incorrect API key provided: foo. You can find your API key at https://platform.openai.com.',
                'type' => 'invalid_request_error',
                'param' => null,
                'code' => 'invalid_api_key',
            ],
        ]));

        $this->client->expects($this->once())->method('sendRequest')->willReturn($response);

        try {
            $this->httpTransporter->requestContent($payload);
            $this->fail('Expected ErrorException to be thrown.');
        } catch (ErrorException $e) {
            $this->assertSame(
                'Incorrect API key provided: foo. You can find your API key at https://platform.openai.com.',
                $e->getMessage()
            );
            $this->assertSame(
                'Incorrect API key provided: foo. You can find your API key at https://platform.openai.com.',
                $e->getErrorMessage()
            );
            $this->assertSame('invalid_api_key', $e->getErrorCode());
            $this->assertSame('invalid_request_error', $e->getErrorType());
        }
    }

    public function testRequestStream(): void
    {
        $payload = Payload::create('chat', []);

        $response = new Response(200, [], json_encode(['foo']));

        $this->client
            ->expects($this->once())
            ->method('sendAsyncRequest')
            ->with(
                $this->callback(function (RequestInterface $request): bool {
                    $this->assertSame('POST', $request->getMethod());
                    $this->assertSame('https', $request->getUri()->getScheme());
                    $this->assertSame('api.url.eu', $request->getUri()->getHost());
                    $this->assertSame('/v1/test/chat', $request->getUri()->getPath());

                    return true;
                })
            )
            ->willReturn($response);

        $result = $this->httpTransporter->requestStream($payload);

        $this->assertFalse($result->getBody()->eof());
    }

    public function testRequestStreamServerErrors(): void
    {
        $payload = Payload::create('chat', []);

        $response = new Response(
            401,
            ['Content-Type' => 'application/json; charset=utf-8'],
            json_encode([
                'code' => '900900',
                'message' => 'Unclassified Authentication Failure',
                'description' => 'Access failure for API: /ecgpt/v1, version: v1 status: (900900) - Unclassified Authentication Failure',
            ])
        );

        $this->client
            ->expects($this->once())
            ->method('sendAsyncRequest')
            ->willReturn($response);

        try {
            $this->httpTransporter->requestStream($payload);
            $this->fail('Expected ErrorException to be thrown.');
        } catch (ErrorException $e) {
            $this->assertSame(
                'Access failure for API: /ecgpt/v1, version: v1 status: (900900) - Unclassified Authentication Failure',
                $e->getMessage()
            );
            $this->assertSame('900900', $e->getErrorCode());
            $this->assertSame(401, $e->getStatusCode());
            // Error type is not supported.
            $this->assertNull($e->getErrorType());
        }
    }

    public function testRequestObjectUnsupportedContentTypeOnError(): void {
        $payload = Payload::list('foo');

        $response = new Response(
            400,
            ['Content-Type' => 'application/xml; charset=utf-8'],
            json_encode([
                'code' => '900900',
                'message' => 'Unclassified Authentication Failure',
                'description' => 'Access failure for API: /ecgpt/v1, version: v1 status: (900900) - Unclassified Authentication Failure',
            ]),
        );

        $this->client
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        // Errors are not thrown with non-JSON content types, only decoded.
        $return = $this->httpTransporter->requestObject($payload);
        $this->assertSame([
            'code' => '900900',
            'message' => 'Unclassified Authentication Failure',
            'description' => 'Access failure for API: /ecgpt/v1, version: v1 status: (900900) - Unclassified Authentication Failure',
        ], $return->data());
    }

    public function testRequestObjectSerialisationErrorsOnResponseError(): void {
        $payload = Payload::list('models');

        $response = new Response(
            400,
            ['Content-Type' => 'application/json; charset=utf-8'],
            'err',
        );

        $this->client
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $this->expectException(UnserializableResponse::class);
        $this->expectExceptionMessage('Syntax error');

        $this->httpTransporter->requestObject($payload);
    }
}