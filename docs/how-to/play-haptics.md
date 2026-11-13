---
title: Play haptics
description: Use the haptics Firstlight controls and Feedback already play, give keypads and buttons a touch haptic, play outcomes from PHP, and turn haptics off.
type: how-to
audience: consumer
sources:
  - src/Haptics/Haptic.php
  - src/Haptics/HapticsManager.php
  - src/Haptics/HapticsFake.php
  - src/Facades/Haptics.php
  - src/Elements/Concerns/AcceptsHaptic.php
  - src/Feedback/PendingFeedback.php
  - src/Concerns/ValidatesFields.php
  - resources/android/FirstlightHaptics.kt
  - resources/ios/FirstlightHaptics.swift
  - tests/Feature/HapticsTest.php
---

# Play Haptics

Firstlight plays each platform's own haptic patterns. On iOS that means
UIKit's feedback generators and SwiftUI's sensory feedback. On Android it
means `HapticFeedbackConstants`. Both follow the user's system setting:
System Haptics on iOS and Touch feedback on Android. Firstlight never runs
the vibration motor the way `Device::vibrate()` does.

Most screens need no code. Firstlight already plays:

| What happens | Haptic |
| --- | --- |
| A Feedback with tone `success`, `warning` or `danger`, sent while PHP handles a tap, change or submit | Success, Warning or Error |
| A press or submit that leaves validation errors | Error |
| Switch, Checkbox and Pill Group changes on Android | Toggle on or off |
| Segmented, Tabs and Stepper changes on Android | Selection |
| Each step of a stepped Slider on Android | Tick |

On iOS, Switch, Slider, pickers and menus play the system's own haptics, so
Firstlight adds nothing to them.

Ordinary buttons stay silent, as they do on both platforms. Feedback that no
tap caused, such as a sync result or a reconnect notice, stays silent too. When
one interaction ends in several outcomes, such as a success Feedback and then a
failed `validate()`, only the strongest plays: error over warning over success.

## Give a keypad or button a touch haptic

Set `haptic` on a Button or Icon Button. The device plays it as the tap lands,
without waiting for PHP:

```blade
<firstlight:button variant="secondary" size="lg" haptic="keypress" a11y-label="Digit 1" @press="pressDigit('1')">
    1
</firstlight:button>

<firstlight:icon-button icon="backspace" haptic="keypress" a11y-label="Delete last digit" @press="backspace" />
```

Use `keypress` for digit keys and `press` for the few buttons that should feel
like a physical key. On Android the haptic plays on touch down. On iOS it plays
as the tap fires.

## Play an outcome from PHP

When an action finishes without a Feedback, such as a submit that navigates
away, play the outcome yourself:

```php
use FirstlightUI\Facades\Haptics;

public function finish(): void
{
    $this->submission->complete();

    Haptics::success();
    $this->navigate('/patients');
}
```

`Haptics::warning()`, `error()` and `selection()` work the same way.
`Haptics::play()` takes any meaning from the `FirstlightUI\Haptics\Haptic` enum
or its name, such as `Haptics::play('increase')`. `Haptics::impact('medium', 0.7)`
plays a physical impact: `light`, `medium`, `heavy`, `soft` or `rigid`, at an
intensity from 0 to 1.

A call from PHP plays when PHP gets there, a moment after the tap. Use `haptic`
on the control for anything that should feel instant.

## Keep something silent

- Send a Feedback without its haptic: `Feedback::danger('Not saved')->withoutHaptic()->send()`.
- Silence a control's default: `<firstlight:switch haptic="none" ... />`. Controls
  other than Button and Icon Button accept only `none`. On iOS this does not
  silence the system's own Switch and Slider haptics.
- Turn every Firstlight haptic off for an in-app setting: `Haptics::setEnabled(false)`.
  The device remembers the choice, and controls obey it without a round trip.

## Test haptics

`Haptics::fake()` records what would have played, after the same rules:

```php
use FirstlightUI\Facades\Haptics;
use FirstlightUI\Haptics\Haptic;

it('buzzes once when saving fails', function () {
    $haptics = Haptics::fake();

    Native::test(EditPost::class)->tap('save');

    $haptics->assertPlayed(Haptic::Error, times: 1);
    $haptics->assertNotPlayed(Haptic::Success);
});
```

Native touch haptics, such as a keypad's `keypress`, play on the device and are
not recorded by the fake. Simulators and emulators play no haptics, so check
how they feel on a phone.
