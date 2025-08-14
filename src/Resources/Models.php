<?php

declare(strict_types=1);

namespace Openeuropa\GptAtEcPhpClient\Resources;

use OpenAI\Resources\Concerns\Transportable;
use OpenAI\Responses\Models\ListResponse;
use OpenAI\ValueObjects\Transporter\Payload;
use OpenAI\ValueObjects\Transporter\Response;
use Openeuropa\GptAtEcPhpClient\Contracts\Resources\ModelsContract;

final class Models implements ModelsContract
{

    use Transportable;

    /**
     * Lists the currently available models, and provides basic information about each one such as the owner and availability.
     *
     * @see https://platform.openai.com/docs/api-reference/models/list
     */
    public function list(): ListResponse
    {
        $payload = Payload::list('models');

        /** @var Response<array{object: string, data: array<int, array{id: string, object: string, created: int, owned_by: string}>}> $response */
        $response = $this->transporter->requestObject($payload);

        return ListResponse::from($response->data(), $response->meta());
    }

}