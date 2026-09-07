<?php

declare(strict_types=1);

namespace Openeuropa\GptAtEcPhpClient\Resources;

use OpenAI\Resources\Concerns\Streamable;
use OpenAI\Resources\Concerns\Transportable;
use OpenAI\Responses\Responses\CreateResponse;
use OpenAI\Responses\StreamResponse;
use OpenAI\ValueObjects\Transporter\Payload;
use Openeuropa\GptAtEcPhpClient\Contracts\Resources\ResponsesContract;
use Openeuropa\GptAtEcPhpClient\Responses\Responses\Concerns\NormalizesCreateResponse;
use Openeuropa\GptAtEcPhpClient\Responses\Responses\CreateStreamedResponseFactory;

final class Responses implements ResponsesContract
{

    use NormalizesCreateResponse;
    use Streamable;
    use Transportable;

    /**
     * {@inheritDoc}
     */
    public function create(array $parameters): CreateResponse
    {
        $this->ensureNotStreamed($parameters);

        $payload = Payload::create('responses', $parameters);

        $response = $this->transporter->requestObject($payload);

        return CreateResponse::from(self::normalizeCreateResponse($response->data()), $response->meta());
    }

    /**
     * {@inheritDoc}
     */
    public function createStreamed(array $parameters): StreamResponse
    {
        $parameters = $this->setStreamParameter($parameters);

        $payload = Payload::create('responses', $parameters);

        $response = $this->transporter->requestStream($payload);

        return new StreamResponse(CreateStreamedResponseFactory::class, $response);
    }

}
