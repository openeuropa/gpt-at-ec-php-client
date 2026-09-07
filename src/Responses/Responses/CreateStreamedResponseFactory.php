<?php

declare(strict_types=1);

namespace Openeuropa\GptAtEcPhpClient\Responses\Responses;

use OpenAI\Responses\Responses\CreateStreamedResponse;
use Openeuropa\GptAtEcPhpClient\Responses\Responses\Concerns\NormalizesCreateResponse;

/**
 * Builds OpenAI streamed response events out of GPT@EC stream data.
 *
 * Events embedding the full response object need the same normalization as
 * the non-streamed one before hydration.
 */
final class CreateStreamedResponseFactory
{

    use NormalizesCreateResponse;

    /**
     * @param array<string, mixed> $attributes
     */
    public static function from(array $attributes): CreateStreamedResponse
    {
        if (isset($attributes['response']) && is_array($attributes['response'])) {
            $attributes['response'] = self::normalizeCreateResponse($attributes['response']);
        }

        return CreateStreamedResponse::from($attributes);
    }

}
