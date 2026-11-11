<?php

declare(strict_types=1);

namespace Openeuropa\GptAtEcPhpClient\Responses\Responses\Concerns;

trait NormalizesCreateResponse
{

    /**
     * Normalizes GPT@EC deviations from the OpenAI "responses" payload.
     *
     * - "created_at" is returned as a float (e.g. 1788534395.0) while OpenAI
     *   returns an integer timestamp.
     * - When a "json_schema" text format is requested, the API echoes the
     *   schema back under "text.format.schema_" instead of "text.format.schema".
     * - Some models (e.g. gpt-oss-120b) return "function_call" output items
     *   without a "status". See normalizeOutputItem().
     *
     * All of them make the OpenAI response hydration fail.
     *
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>
     */
    private static function normalizeCreateResponse(array $data): array
    {
        if (isset($data['created_at']) && is_float($data['created_at'])) {
            $data['created_at'] = (int) $data['created_at'];
        }

        if (isset($data['text']['format']['schema_']) && !isset($data['text']['format']['schema'])) {
            $data['text']['format']['schema'] = $data['text']['format']['schema_'];
            unset($data['text']['format']['schema_']);
        }

        if (isset($data['output']) && is_array($data['output'])) {
            foreach ($data['output'] as $key => $item) {
                if (is_array($item)) {
                    // A returned response is finished, so are its items.
                    $data['output'][$key] = self::normalizeOutputItem($item, 'completed');
                }
            }
        }

        return $data;
    }

    /**
     * Sets the status of a "function_call" item if the item has no status.
     *
     * openai-php cannot read a "function_call" item without a status. The
     * function does not change items of other types.
     *
     * @param array<array-key, mixed> $item
     * @param 'in_progress'|'completed' $status
     *
     * @return array<array-key, mixed>
     */
    private static function normalizeOutputItem(array $item, string $status): array
    {
        if (($item['type'] ?? null) === 'function_call' && !isset($item['status'])) {
            $item['status'] = $status;
        }

        return $item;
    }

}
