<?php

declare(strict_types=1);

namespace Openeuropa\GptAtEcPhpClient\Contracts\Resources;

use Openeuropa\GptAtEcPhpClient\Responses\Models\ListResponse;

/**
 * Subset of OpenAI models resource.
 */
interface ModelsContract
{

    public function list(): ListResponse;

}