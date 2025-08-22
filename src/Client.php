<?php

declare(strict_types=1);

namespace Openeuropa\GptAtEcPhpClient;

use OpenAI\Contracts\Resources\ChatContract;
use OpenAI\Contracts\TransporterContract;
use OpenAI\Resources\Chat;
use Openeuropa\GptAtEcPhpClient\Contracts\ClientContract;
use Openeuropa\GptAtEcPhpClient\Contracts\Resources\ModelsContract;
use Openeuropa\GptAtEcPhpClient\Contracts\Resources\QuotaConsumptionContract;
use Openeuropa\GptAtEcPhpClient\Resources\Models;
use Openeuropa\GptAtEcPhpClient\Resources\QuotaConsumption;

class Client implements ClientContract
{

    /**
     * Creates a Client instance with the given API token.
     */
    public function __construct(private readonly TransporterContract $transporter)
    {}

    public function chat(): ChatContract
    {
        return new Chat($this->transporter);
    }

    public function models(): ModelsContract
    {
        return new Models($this->transporter);
    }

    public function quotaConsumption(): QuotaConsumptionContract
    {
        return new QuotaConsumption($this->transporter);
    }

}