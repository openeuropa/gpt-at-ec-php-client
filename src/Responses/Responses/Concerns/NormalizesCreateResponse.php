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
     *
     * Both make the OpenAI response hydration fail.
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

        return $data;
    }

}
