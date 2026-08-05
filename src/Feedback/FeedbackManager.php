<?php

namespace FirstlightUI\Feedback;

final class FeedbackManager
{
    public function __construct(private readonly FeedbackStore $store) {}

    public function message(string $message): PendingFeedback
    {
        return PendingFeedback::create($this->store, $message, FeedbackTone::Default);
    }

    public function success(string $message): PendingFeedback
    {
        return PendingFeedback::create($this->store, $message, FeedbackTone::Success);
    }

    public function warning(string $message): PendingFeedback
    {
        return PendingFeedback::create($this->store, $message, FeedbackTone::Warning);
    }

    public function danger(string $message): PendingFeedback
    {
        return PendingFeedback::create($this->store, $message, FeedbackTone::Danger);
    }

    public function dismiss(string $id): bool
    {
        return $this->store->remove($id) !== null;
    }
}
