<?php

declare(strict_types=1);

namespace Openeuropa\GptAtEcPhpClient\Contracts\Resources;

use OpenAI\Responses\Models\ListResponse;

/**
 * Subset of OpenAI models resource.
 */
interface ModelsContract
{

    /**
     * Lists the currently available models, and provides basic information about each one such as the owner and availability.
     *
     * @see https://platform.openai.com/docs/api-reference/models/list
     */
    public function list(): ListResponse;

}