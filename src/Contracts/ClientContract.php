<?php

declare(strict_types=1);

namespace Openeuropa\GptAtEcPhpClient\Contracts;

use OpenAI\Contracts\Resources\ChatContract;
use Openeuropa\GptAtEcPhpClient\Contracts\Resources\ModelsContract;
use Openeuropa\GptAtEcPhpClient\Contracts\Resources\QuotaConsumptionContract;

interface ClientContract
{

    /**
     * Given a chat conversation, the model will return a chat completion response.
     *
     * @see https://platform.openai.com/docs/api-reference/chat
     */
    public function chat(): ChatContract;

    /**
     * List and describe the various models available in the API.
     *
     * @see https://platform.openai.com/docs/api-reference/models
     */
    public function models(): ModelsContract;

    /**
     * Returns the quota consumption for each model.
     */
    public function quotaConsumption(): QuotaConsumptionContract;

}