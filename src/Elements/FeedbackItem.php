<?php

namespace FirstlightUI\Elements;

use FirstlightUI\Feedback\FeedbackDismissReason;
use FirstlightUI\Feedback\FeedbackRecord;
use FirstlightUI\Feedback\FeedbackTone;
use FirstlightUI\Support\CallbackExpression;
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
    ) {}

    public static function fromRecord(FeedbackRecord $record): self
    {
        return new self(
            $record->id,
            $record->message,
            $record->tone,
            $record->hold,
            $record->actionLabel,
            $record->actionKey,
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
            $action = CallbackExpression::appendValue('action', $this->feedbackId);
            $action = CallbackExpression::appendValue($action, $this->actionKey);

            $props['action_label'] = $this->actionLabel;
            $props['on_action'] = $registry->register($action);
        }

        if (! $this->hold) {
            $timeout = CallbackExpression::appendValue('dismiss', $this->feedbackId);
            $timeout = CallbackExpression::appendValue($timeout, FeedbackDismissReason::Timeout->value);
            $props['on_timeout'] = $registry->register($timeout);
        }

        $manual = CallbackExpression::appendValue('dismiss', $this->feedbackId);
        $manual = CallbackExpression::appendValue($manual, FeedbackDismissReason::Manual->value);
        $props['on_manual'] = $registry->register($manual);

        return $props;
    }
}
