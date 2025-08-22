<?php

declare(strict_types=1);

namespace Openeuropa\Tests\GptAtEcPhpClient\Mock;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MockSymfonyPsr18CompatibleClient implements ClientInterface
{

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        throw new \RuntimeException('This class is a mock and should not be used.');
    }

}