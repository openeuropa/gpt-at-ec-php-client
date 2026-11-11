<?php

declare(strict_types=1);

namespace Openeuropa\GptAtEcPhpClient\Responses\Responses;

use OpenAI\Responses\Responses\CreateStreamedResponse;
use Openeuropa\GptAtEcPhpClient\Responses\Responses\Concerns\NormalizesCreateResponse;

/**
 * Builds OpenAI streamed response events out of GPT@EC stream data.
 *
 * Events embedding the full response object need the same normalization as
 * the non-streamed one before hydration. Events embedding a single output
 * item need the same normalization as the items of that object.
 */
final class CreateStreamedResponseFactory
{

    use NormalizesCreateResponse;

    /**
     * The status to set on an output item that has none, keyed by event type.
     */
    private const array OUTPUT_ITEM_STATUS = [
        'response.output_item.added' => 'in_progress',
        'response.output_item.done' => 'completed',
    ];

    /**
     * @param array<string, mixed> $attributes
     */
    public static function from(array $attributes): CreateStreamedResponse
    {
        if (isset($attributes['response']) && is_array($attributes['response'])) {
            $attributes['response'] = self::normalizeCreateResponse($attributes['response']);
        }

        $type = $attributes['type'] ?? null;
        if (isset(self::OUTPUT_ITEM_STATUS[$type], $attributes['item']) && is_array($attributes['item'])) {
            $attributes['item'] = self::normalizeOutputItem($attributes['item'], self::OUTPUT_ITEM_STATUS[$type]);
        }

        return CreateStreamedResponse::from($attributes);
    }

}
