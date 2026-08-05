<?php

namespace FirstlightUI\Events;

use FirstlightUI\Feedback\FeedbackDismissReason;

final readonly class FeedbackDismissed
{
    public function __construct(
        public string $id,
        public FeedbackDismissReason $reason,
    ) {}
}
