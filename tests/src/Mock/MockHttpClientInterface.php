<?php

declare(strict_types=1);

namespace Openeuropa\Tests\GptAtEcPhpClient\Mock;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * An interface o be used in tests.
 *
 * It adds an extra method to be able to test stream requests.
 */
interface MockHttpClientInterface extends ClientInterface
{

    public function sendAsyncRequest(RequestInterface $request): ResponseInterface;

}