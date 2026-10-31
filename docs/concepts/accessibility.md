---
title: Accessibility in Firstlight
description: What VoiceOver, TalkBack, Dynamic Type, font scaling, contrast, Reduce Motion, and right-to-left users get from Firstlight's real native controls, what is tested, what is not yet verified, and what remains your app's job.
type: explanation
audience: consumer
sources:
  - Constitution.md
  - docs/components/button.md
  - docs/components/icon-button.md
  - docs/components/list-item.md
  - docs/components/callout.md
  - docs/components/badge.md
  - docs/components/status-label.md
  - docs/components/metric.md
  - docs/components/transient-feedback.md
  - docs/components/media.md
  - docs/components/stepper.md
  - docs/components/tabs.md
  - docs/components/code-editor.md
  - docs/how-to/validate-fields.md
  - docs/how-to/test-screens.md
  - tests/ios/ListItemSnapshotTests.swift
  - tests/ios/PillGroupSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/ListItemTest.kt
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/FeedbackCenterTest.kt
  - vendor/nativephp/mobile/src/Testing/TestableComponent.php
  - CHANGELOG.md
---

# Accessibility in Firstlight

Accessibility is a headline feature of Firstlight, and it starts with one
decision: every control is a real SwiftUI or Jetpack Compose control, not a
drawing that imitates one. VoiceOver, TalkBack, Dynamic Type, Android font
scaling, dark appearance, Increased Contrast, Reduce Motion, and right-to-left
layout reach each control the same way they reach the platform's own apps.

Firstlight then adds the parts a native control alone does not decide: whether
something has a name, how many focus stops a row has, how large a target is,
and what a screen reader hears when a form fails.

| Users who rely on | Get |
| --- | --- |
| VoiceOver and TalkBack | Required names on icon-only controls, development warnings for unnamed fields, one focus stop per row or field, and every swipe and context-menu action as a screen-reader action |
| Spoken feedback on forms | Validation errors read aloud after a failed submit, with screen-reader focus on the first invalid field |
| Larger text | Dynamic Type and Android font scaling, with labels that wrap instead of truncating |
| Right-to-left languages | Mirrored native layout, with code kept left-to-right |
| Reduce Motion | Platform motion settings, and Feedback that fades instead of moving on iOS |
| Contrast and colour | Status named in words, and status colours below 4.5:1 corrected to black or white text |

**Status.** The behaviour on this page
is covered by contract, XCTest, and Robolectric tests, and validation focus was
observed with TalkBack on the Android emulator. VoiceOver was checked on a
physical iPhone; spoken TalkBack output has not been recorded on a physical
Android device. See
[Known gaps](#known-gaps).

## What you get from the platform

Because the controls are native, these work without any Firstlight code:

| Need | iOS | Android |
| --- | --- | --- |
| Screen reader | VoiceOver roles, values, and states | TalkBack roles, values, and states |
| Larger text | Dynamic Type, including accessibility sizes | System font scale |
| Appearance | Light, dark, and Increased Contrast | Light, dark, and high-contrast text |
| Motion | Reduce Motion | Remove animations |
| Reading direction | Right-to-left layout | Right-to-left layout |
| Dialogs and sheets | System focus trapping and action order | System focus trapping and action order |

## What Firstlight adds

**Every control has a name.** Icon Button, Search Field, and Progress require
`a11y-label` and fail before publishing without it. Fields, choices, and
pickers warn during development when neither `label` nor `a11y-label` is set.
An icon name is never accepted as a substitute for a name.

**One thing, one focus stop.** A List Item row, a field with its label, helper,
and error, or a Metric with its value and detail is one accessibility element.
Decorative icons, avatars, monograms, checkmarks, and tone symbols are hidden,
so screen-reader users do not hear the same thing twice.

**Gestures have alternatives.** Swipe actions and context-menu items on List
Item are also VoiceOver actions and TalkBack custom actions, so nobody has to
perform a swipe or long press. The Media crop sheet has zoom buttons as well as
pinch. Stepper exposes named increase and decrease actions.

**Targets are large enough.** Actions Firstlight owns, such as icon buttons,
field icon actions, the search clear button, Callout and Feedback actions,
List Item rows, and Code Editor keys, are at least 44 points on iOS and 48 dp on
Android.

**Errors are spoken.** When a submit fails validation, VoiceOver and TalkBack
read a summary of the errors once, for example "There are 2 errors. The name
field is required. The email field is required.", and screen-reader focus
moves to the first invalid field. On iOS the keyboard does not open. On
Android, Text Field, Text Area, and Code Editor take input focus while TalkBack
is on; other fields announce without moving focus. When a single field fails
on blur, only that message is read and focus stays put. Form-level errors
appear in a Callout with `error-for` or `error-summary`. See
[Validate fields](../how-to/validate-fields.md#screen-reader-announcements).

**Status is not colour alone.** Callout names its tone in the spoken text, for
example "Warning: Your changes have not been submitted." Badge, Status Label,
Callout, and Feedback check theme colours as they render; a foreground and
background pair below 4.5:1 contrast is corrected to black or white text.

**Messages wait for the reader.** Transient Feedback announces each new message
once. On iOS it stays twice as long while VoiceOver or Switch Control is on and
fades instead of moving under Reduce Motion. On Android it uses the system's
recommended accessibility timeout.

**Text grows instead of shrinking.** Labels, rows, metrics, and messages wrap at
large text sizes rather than truncating or shrinking. On iPhone the Date and
Time picker sheets size to their content, so they grow with Dynamic Type.

**Right-to-left layouts mirror.** Native layout mirrors automatically. The
Android Stepper row mirrors with its label, and Code Editor keeps code
left-to-right inside right-to-left layouts.

Each component's reference page has an Accessibility section with its exact
behaviour.

## What is tested

| Area | Automated evidence |
| --- | --- |
| Accessible names | Contract tests reject missing required labels; iOS and Android renderer tests check the published label |
| Larger text | iOS snapshot tests at accessibility Dynamic Type sizes and Android tests at large font scales for most controls |
| Dark appearance | iOS snapshots in both appearances for most controls; Android tests for List Item, Status Label, Date Picker, and Time Picker |
| Target sizes | iOS and Android tests for the controls that own extra actions |
| Gesture alternatives | List Item swipe and context-menu actions on both platforms |
| Announcements | Activity Indicator, Feedback, Code Editor follow mode, and validation errors on both platforms |
| Right-to-left | Pill Group on iOS; List Item and Feedback on Android |
| Your screens | `assertAccessible()` in every showcase screen test |

Documentation screenshots for every component are captured on both platforms in
light and dark appearance.

## Known gaps

These are known gaps, stated plainly:

- A spoken TalkBack pass on a physical Android device has not been recorded.
  TalkBack focus moving to the first invalid field was observed on the Android
  emulator. VoiceOver was checked on a physical iPhone.
- The error summary reads messages in rule order, not screen order. Declare
  rules in the order the fields appear.
- The app recipes were checked on an iOS simulator and an Android emulator,
  not with VoiceOver or TalkBack.
- Right-to-left layout is automated for only the components listed above.
  Other controls rely on native layout, which mirrors automatically.
- Text-scaling tests do not yet cover Activity Indicator, Badge, Bottom Sheet,
  Date Picker, and Slider on either platform, nor the iOS dialogs and Time
  Picker or Android Media and Search Field.
- Android dark-appearance tests cover only the components listed above; the
  documentation screenshots cover the rest.

## What remains your app's job

Firstlight makes each control accessible. Your app still decides what the
screen says and in what order.

- Write labels that name the thing, such as "Delete post", not "Button".
- Give fields visible labels. Use `a11y-label` only when a visible label would
  be wrong for the design.
- Declare validation rules in the order the fields appear, so the error summary
  reads in screen order.
- Name the consequence in confirmation dialogs, such as "Delete" rather than
  "OK".
- Use the theme for colour, and keep each `on-*` pair at 4.5:1 or better. See
  [Theme Firstlight components](../getting-started/theming.md).
- Check your own screens in tests. `assertAccessible()` from NativePHP's test
  harness fails when an interactive element or image has no accessible name:

```php
Native::visit('/posts')->assertAccessible();
```

See [Test screens](../how-to/test-screens.md) for the Firstlight checks that
work alongside it.
