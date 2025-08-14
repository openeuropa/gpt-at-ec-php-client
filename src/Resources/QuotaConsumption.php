<?php

declare(strict_types=1);

namespace Openeuropa\GptAtEcPhpClient\Resources;

use OpenAI\Resources\Concerns\Transportable;
use OpenAI\ValueObjects\Transporter\Payload;
use Openeuropa\GptAtEcPhpClient\Contracts\Resources\QuotaConsumptionContract;
use Openeuropa\GptAtEcPhpClient\Responses\QuotaConsumption\RetrieveResponse;

final class QuotaConsumption implements QuotaConsumptionContract
{

    use Transportable;

    public function retrieve(string $model): RetrieveResponse
    {
        $payload = Payload::retrieve('quota-consumption', $model);

        $response = $this->transporter->requestObject($payload);

        return RetrieveResponse::from($response->data());
    }


}