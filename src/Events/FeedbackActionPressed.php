<?php

namespace FirstlightUI\Events;

final readonly class FeedbackActionPressed
{
    public function __construct(
        public string $id,
        public string $actionKey,
    ) {}
}
