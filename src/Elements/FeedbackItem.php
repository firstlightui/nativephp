<?php

namespace FirstlightUI\Elements;

use FirstlightUI\Feedback\FeedbackDismissReason;
use FirstlightUI\Feedback\FeedbackRecord;
use FirstlightUI\Feedback\FeedbackTone;
use FirstlightUI\Support\CallbackExpression;
use FirstlightUI\Support\Chrome;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;

final class FeedbackItem extends Element
{
    protected string $type = 'firstlight.feedback-item';

    private function __construct(
        private readonly string $feedbackId,
        private readonly string $message,
        private readonly FeedbackTone $tone,
        private readonly bool $hold,
        private readonly ?string $actionLabel,
        private readonly ?string $actionKey,
        private readonly int $publicationGeneration,
    ) {}

    public static function fromRecord(FeedbackRecord $record, int $publicationGeneration): self
    {
        return new self(
            $record->id,
            $record->message,
            $record->tone,
            $record->hold,
            $record->actionLabel,
            $record->actionKey,
            $publicationGeneration,
        );
    }

    public function getLayout(): array
    {
        return [];
    }

    public function getStyle(): array
    {
        return [];
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        $props = [
            'feedback_id' => $this->feedbackId,
            'message' => $this->message,
            'tone' => $this->tone->value,
            'hold' => $this->hold,
        ];

        if ($this->actionLabel !== null && $this->actionKey !== null) {
            $props['action_label'] = $this->actionLabel;
            $props['on_action'] = $this->registerCallback($registry, 'action', $this->actionKey);
        }

        if (! $this->hold) {
            $props['on_timeout'] = $this->registerCallback(
                $registry,
                'dismiss',
                FeedbackDismissReason::Timeout->value,
            );
        }

        $props['on_manual'] = $this->registerCallback(
            $registry,
            'dismiss',
            FeedbackDismissReason::Manual->value,
        );
        $props['dismiss_label'] = Chrome::string('dismiss');
        $props['dismiss_a11y_label'] = Chrome::string('dismiss_feedback');

        return $props;
    }

    private function registerCallback(CallbackRegistry $registry, string $method, string $argument): int
    {
        $expression = CallbackExpression::appendValue($method, $this->feedbackId);
        $expression = CallbackExpression::appendValue($expression, $argument);

        return $registry->register(
            CallbackExpression::appendInteger($expression, $this->publicationGeneration),
        );
    }
}
