<?php

namespace FirstlightUI\Feedback;

use FirstlightUI\Haptics\Haptic;

enum FeedbackTone: string
{
    case Default = 'default';
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';

    /** The haptic a Feedback of this tone plays when a user interaction sent it. */
    public function haptic(): ?Haptic
    {
        return match ($this) {
            self::Success => Haptic::Success,
            self::Warning => Haptic::Warning,
            self::Danger => Haptic::Error,
            self::Default => null,
        };
    }
}
