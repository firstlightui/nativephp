<?php

namespace FirstlightUI\Haptics;

use InvalidArgumentException;

/**
 * What a haptic means. Each platform plays its own canonical pattern for the
 * meaning: UIKit feedback generators or SwiftUI sensory feedback on iOS, and
 * HapticFeedbackConstants on Android (spec/reference/haptics.md).
 */
enum Haptic: string
{
    /** A task finished. */
    case Success = 'success';

    /** A task finished with a warning, or did nothing. */
    case Warning = 'warning';

    /** A task failed or was rejected. */
    case Error = 'error';

    /** A discrete choice changed, such as a segment or option. */
    case Selection = 'selection';

    /** Dense values stepping, such as slider steps or a minute wheel. */
    case Tick = 'tick';

    case ToggleOn = 'toggle-on';

    case ToggleOff = 'toggle-off';

    /** A button touch, for buttons that opt in. */
    case Press = 'press';

    /** A keypad or digit key. */
    case Keypress = 'keypress';

    /** A custom long press acted. */
    case LongPress = 'long-press';

    /** A swipe or pull crossed the point where letting go commits it. */
    case ThresholdActivate = 'threshold-activate';

    /** A swipe or pull went back under that point. */
    case ThresholdDeactivate = 'threshold-deactivate';

    /** An item was picked up to drag. */
    case DragStart = 'drag-start';

    /** A dragged item snapped into place. */
    case Alignment = 'alignment';

    /** An important value rose above a significant threshold. */
    case Increase = 'increase';

    /** An important value fell below a significant threshold. */
    case Decrease = 'decrease';

    /** Movement between discrete levels. */
    case LevelChange = 'level-change';

    /** An activity started, such as recording or a timer. */
    case Start = 'start';

    /** An activity stopped. */
    case Stop = 'stop';

    /** A drawn path completed or was recognized. */
    case PathComplete = 'path-complete';

    /** Explicitly silent. */
    case None = 'none';

    public static function parse(self|string $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        return self::tryFrom($value) ?? throw new InvalidArgumentException(
            "Unknown haptic [{$value}]. Expected one of: "
            .implode(', ', array_map(fn (self $case): string => $case->value, self::cases())).'.'
        );
    }

    /** Success, Warning and Error report how an action ended. */
    public function isOutcome(): bool
    {
        return $this->outcomeRank() > 0;
    }

    /** When one interaction ends in several outcomes, the highest rank plays. */
    public function outcomeRank(): int
    {
        return match ($this) {
            self::Error => 3,
            self::Warning => 2,
            self::Success => 1,
            default => 0,
        };
    }
}
