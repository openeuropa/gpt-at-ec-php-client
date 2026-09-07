<?php

declare(strict_types=1);

namespace Openeuropa\GptAtEcPhpClient\Contracts;

use OpenAI\Contracts\Resources\ChatContract;
use Openeuropa\GptAtEcPhpClient\Contracts\Resources\ModelsContract;
use Openeuropa\GptAtEcPhpClient\Contracts\Resources\QuotaConsumptionContract;
use Openeuropa\GptAtEcPhpClient\Contracts\Resources\ResponsesContract;

interface ClientContract
{

    /**
     * Given a chat conversation, the model will return a chat completion response.
     *
     * GPT@EC applies a whitelist of forwarded parameters per model family on
     * this endpoint; parameters not in the whitelist are silently ignored.
     * "response_format" is not whitelisted for any model, so structured output
     * is not available here: use responses() instead.
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

    /**
     * Creates a model response through the Responses API.
     *
     * All request parameters are forwarded to the model, including
     * "text.format" for structured output. Only some models support it.
     *
     * @see https://platform.openai.com/docs/api-reference/responses
     */
    public function responses(): ResponsesContract;

}