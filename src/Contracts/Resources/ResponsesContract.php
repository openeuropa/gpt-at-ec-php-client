<?php

declare(strict_types=1);

namespace Openeuropa\GptAtEcPhpClient\Contracts\Resources;

use OpenAI\Responses\Responses\CreateResponse;

/**
 * Subset of OpenAI responses resource.
 *
 * GPT@EC exposes the Responses API for a subset of its models only.
 * Only the create() method is exposed here.
 */
interface ResponsesContract
{

    /**
     * Creates a model response.
     *
     * @param array<string, mixed> $parameters
     *
     * @see https://platform.openai.com/docs/api-reference/responses/create
     */
    public function create(array $parameters): CreateResponse;

}
