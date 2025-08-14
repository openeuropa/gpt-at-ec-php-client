<?php

declare(strict_types=1);

namespace Openeuropa\GptAtEcPhpClient\Contracts\Resources;


use Openeuropa\GptAtEcPhpClient\Responses\QuotaConsumption\RetrieveResponse;

interface QuotaConsumptionContract
{

    public function retrieve(string $model): RetrieveResponse;

}