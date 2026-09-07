<?php

declare(strict_types=1);

namespace Openeuropa\GptAtEcPhpClient\Contracts\Resources;

use OpenAI\Responses\Responses\CreateResponse;
use OpenAI\Responses\Responses\CreateStreamedResponse;
use OpenAI\Responses\StreamResponse;

/**
 * Subset of OpenAI responses resource.
 *
 * GPT@EC exposes the Responses API for a subset of its models only.
 * Only the create() and createStreamed() methods are exposed here.
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

    /**
     * Creates a model response, streaming the events as they are generated.
     *
     * @param array<string, mixed> $parameters
     *
     * @return StreamResponse<CreateStreamedResponse>
     *
     * @see https://platform.openai.com/docs/api-reference/responses-streaming
     */
    public function createStreamed(array $parameters): StreamResponse;

}
