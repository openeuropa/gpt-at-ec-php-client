<?php

declare(strict_types=1);

namespace Openeuropa\GptAtEcPhpClient\Responses\Models;

use OpenAI\Contracts\ResponseContract;
use OpenAI\Responses\Concerns\ArrayAccessible;

final class ListResponse implements ResponseContract
{

    use ArrayAccessible;

    private function __construct(
        public readonly string $object,
        public readonly array $data,
    ) {
    }

    public static function from(array $attributes): self
    {
        $data = array_map(fn(array $result): RetrieveResponse => RetrieveResponse::from(
            $result,
        ), $attributes['data']);

        return new self(
            $attributes['object'],
            $data,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'object' => $this->object,
            'data' => array_map(
                static fn(RetrieveResponse $response): array => $response->toArray(),
                $this->data,
            ),
        ];
    }
}
