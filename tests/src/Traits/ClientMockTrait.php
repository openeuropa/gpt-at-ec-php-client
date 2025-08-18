<?php

declare(strict_types=1);

namespace Openeuropa\Tests\GptAtEcPhpClient\Traits;

use OpenAI\Contracts\TransporterContract;
use OpenAI\ValueObjects\ApiKey;
use OpenAI\ValueObjects\Transporter\BaseUri;
use OpenAI\ValueObjects\Transporter\Headers;
use OpenAI\ValueObjects\Transporter\Payload;
use OpenAI\ValueObjects\Transporter\QueryParams;
use OpenAI\ValueObjects\Transporter\Response;
use Openeuropa\GptAtEcPhpClient\Client;
use Psr\Http\Message\ResponseInterface;

trait ClientMockTrait
{

    protected function getClientMock(
        string $method,
        string $resource,
        array $params,
        Response|ResponseInterface|string $response,
        string $methodName = 'requestObject',
        bool $validateParams = true
    ) {
        $transporter = $this->createMock(TransporterContract::class);

        $transporter
            ->expects($this->once())
            ->method($methodName)
            ->with(
                $this->callback(function (Payload $payload) use ($validateParams, $method, $resource, $params): bool {
                    $baseUri = BaseUri::from('api.tech.ec.europa.eu/ecgpt/v1');
                    $headers = Headers::withAuthorization(ApiKey::from('foo'));
                    $queryParams = QueryParams::create();

                    $request = $payload->toRequest($baseUri, $headers, $queryParams);

                    if ($validateParams) {
                        if (in_array($method, ['GET', 'DELETE'], true)) {
                            if ($request->getUri()->getQuery() !== http_build_query($params)) {
                                return false;
                            }
                        }
                        elseif ($request->getBody()->getContents() !== json_encode($params)) {
                            return false;
                        }
                    }

                    return $request->getMethod() === $method && $request->getUri()->getPath() === "/ecgpt/v1/{$resource}";
                })
            )
            ->willReturn($response);

        return new Client($transporter);
    }

}