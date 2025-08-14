<?php

declare(strict_types=1);

namespace Openeuropa\GptAtEcPhpClient\Responses\Models;

use OpenAI\Contracts\ResponseContract;
use OpenAI\Responses\Concerns\ArrayAccessible;

final class RetrieveResponse implements ResponseContract
{
    use ArrayAccessible;

    private function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $description,
        public readonly array $sensitivityLevel,
        public readonly array $defaultFor,
        public readonly string $object,
        public readonly ?int $created,
        public readonly ?string $ownedBy,
    ) {}

    public static function from(array $attributes): self
    {
        return new self(
            id: $attributes['id'],
            name: $attributes['name'],
            description: $attributes['description'],
            sensitivityLevel: $attributes['sensitivity_level'],
            defaultFor: $attributes['default_for'],
            object: $attributes['object'],
            created: $attributes['created'] ?? $attributes['created_at'] ?? null,
            ownedBy: $attributes['owned_by'] ?? null,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'sensitivity_level' => $this->sensitivityLevel,
            'default_for' => $this->defaultFor,
            'object' => $this->object,
            'created' => $this->created,
            'owned_by' => $this->ownedBy,
        ];
    }
}
