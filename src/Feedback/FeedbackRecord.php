<?php

namespace FirstlightUI\Feedback;

use InvalidArgumentException;

final readonly class FeedbackRecord
{
    public function __construct(
        public string $id,
        public string $message,
        public FeedbackTone $tone,
        public bool $hold,
        public ?string $actionLabel,
        public ?string $actionKey,
    ) {
        self::ensureNotBlank($id, 'id');
        self::ensureNotBlank($message, 'message');

        if (($actionLabel === null) !== ($actionKey === null)) {
            throw new InvalidArgumentException('Feedback action label and key must be authored together.');
        }

        if ($actionLabel !== null && $actionKey !== null) {
            self::ensureNotBlank($actionLabel, 'action label');
            self::ensureNotBlank($actionKey, 'action key');
        }
    }

    private static function ensureNotBlank(string $value, string $field): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException("Feedback requires a non-empty `{$field}`.");
        }
    }
}
