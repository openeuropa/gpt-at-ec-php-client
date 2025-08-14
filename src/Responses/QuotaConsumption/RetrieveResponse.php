<?php

declare(strict_types=1);

namespace Openeuropa\GptAtEcPhpClient\Responses\QuotaConsumption;

use OpenAI\Contracts\ResponseContract;
use OpenAI\Responses\Concerns\ArrayAccessible;

class RetrieveResponse implements ResponseContract
{

    use ArrayAccessible;

    private function __construct(
        public readonly int $consumedPromptTokens,
        public readonly int $consumedCompletionTokens,
        public readonly int $consumedTotalTokens,
        public readonly int $quota,
    ) {}

    public static function from(array $data): self {
        return new self(
            consumedPromptTokens: $data['consumed_prompt_tokens'],
            consumedCompletionTokens: $data['consumed_completion_tokens'],
            consumedTotalTokens: $data['consumed_total_tokens'],
            quota: $data['quota'],
        );
    }

    public function toArray(): array
    {
        return [
            'consumed_prompt_tokens' => $this->consumedPromptTokens,
            'consumed_completion_tokens' => $this->consumedCompletionTokens,
            'consumed_total_tokens' => $this->consumedTotalTokens,
            'quota' => $this->quota,
        ];
    }

}