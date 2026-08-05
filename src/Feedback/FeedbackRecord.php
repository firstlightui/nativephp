<?php

namespace FirstlightUI\Feedback;

final readonly class FeedbackRecord
{
    public function __construct(
        public string $id,
        public string $message,
        public FeedbackTone $tone,
        public bool $hold,
        public ?string $actionLabel,
        public ?string $actionKey,
    ) {}
}
