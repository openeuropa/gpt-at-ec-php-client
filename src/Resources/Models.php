<?php

declare(strict_types=1);

namespace Openeuropa\GptAtEcPhpClient\Resources;

use OpenAI\Resources\Concerns\Transportable;
use OpenAI\ValueObjects\Transporter\Payload;
use Openeuropa\GptAtEcPhpClient\Contracts\Resources\ModelsContract;
use Openeuropa\GptAtEcPhpClient\Responses\Models\ListResponse;

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

        $response = $this->transporter->requestObject($payload);

        return ListResponse::from($response->data());
    }

}