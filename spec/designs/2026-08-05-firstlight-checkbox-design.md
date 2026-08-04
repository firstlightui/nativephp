---
title: Firstlight Checkbox design
description: Approved strict Boolean field contract, server-authoritative state, native presentation, and evidence boundary for Firstlight Checkbox.
status: approved
sources:
  - Constitution.md
  - roadmap-v2.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - docs/components/switch.md
  - spec/components/choice-group.md
  - vendor/nativephp/mobile-ui/src/Elements/Checkbox.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUICheckboxRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/CheckboxRenderer.kt
  - https://nativephp.com/docs/mobile/4/edge-components/checkbox
  - https://developer.apple.com/documentation/swiftui/accessibilitytraits
  - https://developer.android.com/develop/ui/compose/components/checkbox
---

# Firstlight Checkbox

Date: 2026-08-05

Status: approved for implementation by the accepted `roadmap-v2.md` boundary.

## Purpose and state class

`<firstlight:checkbox>` represents one Boolean field in a form or checklist.
Its value is normally saved or submitted with surrounding data. It is distinct
from Switch, which represents a setting that takes effect immediately, and
Choice Group, which owns one-or-many selection from a visible collection.

Checkbox is a discrete server-authoritative control. A user interaction
proposes the inverse Boolean through standard `@change`; the accepted glyph
does not change until PHP publishes the next tree. Rejected and identical
publications clear the pending proposal without briefly displaying an
unaccepted value. Programmatic publication never emits.

## Public API

```blade
<firstlight:checkbox
    native:model="acceptedTerms"
    label="I agree to the terms"
    helper="Required before continuing."
    :required="true"
    :disabled="$saving"
    a11y-hint="Opens access to the next step"
/>
```

| API | Accepted type | Behaviour |
| --- | --- | --- |
| `value` / `native:model` | strict `bool` | Accepted checked state; omission defaults to `false`. |
| `label` | `string` | Visible field label and accessibility-name fallback. |
| `helper` | `string` | Supporting guidance when no error exists. |
| `error` | `string` | Validation feedback; replaces helper presentation. |
| `required` | strict `bool` | Marks a form requirement; it does not validate or force `true`. |
| `disabled` | strict `bool` | Prevents proposals while retaining the accepted state. |
| `a11y-label` | `string` | Optional explicit accessible name; `a11yLabel` is accepted internally. |
| `a11y-hint` | `string` | Optional supplementary interaction context; `a11yHint` is accepted internally. |
| `@change` | callback | Emits the proposed Boolean once per publication cycle. |
| `class` | `string` | External EDGE layout for the complete field row. |

The component is self-closing. It supports only immediate `native:model` and
`native:model.live`; `blur`, `lazy`, and `debounce` are rejected because a
discrete checkbox tap has no meaningful deferred editing phase.

The API excludes `indeterminate`, nullable values, `placement`, `variant`,
`tone`, consumer icons, arbitrary colours, slots, `@press`, and `@submit`.
The checkmark is a semantic affordance owned by the component and therefore
does not create a public icon contract.

## Validation and empty behaviour

`false` is the unchecked empty value. Authored `null`, integers, strings,
arrays, and objects fail rather than using PHP truthiness. The string
`value="false"` receives a diagnostic recommending `:value="false"` or
`native:model`. `required` and `disabled` also require actual booleans.

String metadata must be strings; omission defaults to the empty string. A
visible label or explicit accessibility label is mandatory for development.
Error presentation wins over helper text without deleting the helper from the
published contract, so a later accepted publication can restore it.

## Official primitive audit and path decision

NativePHP Mobile UI 0.3.0 exposes `native:checkbox`, an SF Symbol button on
iOS and a Material 3 Checkbox row on Android. Its API and theme inheritance
are close to this contract, but both installed renderers immediately mutate
local checked state before PHP publishes an accepted value. They cannot show
server rejection without first showing the rejected value, and they do not
publish Firstlight helper, error, or required field metadata.

A literal adapter is therefore inadequate. Checkbox uses one Firstlight EDGE
element and paired renderers through the official SuperNative seam:

- iOS renderer: `CheckboxRenderer`
- Android renderer: `dev.firstlightui.plugins.firstlight_ui.ui.FirstlightCheckboxRenderer`

No new bridge function is introduced; each platform uses NativePHP's existing
checkbox-change event transport.

## State and event flow

Each renderer stores the latest published configuration and one non-visual
pending-proposal marker:

1. The row displays the latest published Boolean.
2. A tap proposes `!value` without mutating accepted state.
3. Further taps are ignored while the proposal is pending.
4. The next tree publication replaces all field metadata and clears pending.
5. An accepted publication updates the glyph; an identical rejected
   publication keeps the existing glyph.
6. Programmatic publications update the glyph without emitting.

The identical-publication step relies on NativePHP exposing every PHP response
publication to plugin renderers. That prerequisite remains a public-release
gate and must not be represented as satisfied by package-only tests.

## Platform expression

### iOS

iOS uses an idiomatic SwiftUI checkmarked row rather than Material checkbox
geometry: a plain native `Button`, an owned square/checkmark SF Symbol, and a
native text column. The complete row is one 44-point minimum target. It uses
host semantic colours, Dynamic Type, and automatic right-to-left mirroring.

VoiceOver receives one toggle element with the authored or visible name,
`Checked` or `Not checked` value, optional required and error context, and the
authored hint. The decorative glyph is hidden from accessibility.

### Android

Android uses a genuine Material 3 `Checkbox` in a full-width labelled row.
The row owns one 48-dp `Role.Checkbox` toggleable target; the inner visual
checkbox clears its semantics to avoid a duplicate TalkBack stop. Material
colours, typography, state layers, font scaling, and RTL behaviour remain
native.

TalkBack receives the checked state from the row, the authored or visible
name, optional hint, required context, and native error semantics. The visual
Checkbox receives `onCheckedChange = null`; only the server-authoritative row
proposes changes.

## Accessibility and field presentation

The complete label, control, helper, and error presentation forms one
interactive accessibility target. Required state is visible in the label and
included in accessibility context; it does not imply that Firstlight performs
validation. Disabled state uses native disabled semantics. Error text replaces
helper text visually, uses the host destructive token, and is exposed through
platform error/value semantics.

Large text wraps vertically without shrinking the glyph or reducing the hit
target. Increased Contrast, Reduced Motion, light/dark appearance, and RTL are
delegated to native controls and semantic theme tokens.

## Showcase and evidence

The showcase screen covers unchecked, checked, required, disabled unchecked,
disabled checked, helper, error, long-label, rapid-tap, server-rejection, and
programmatic-publication cases. `/captures/checkbox` is a separate stable
fixture for light/dark platform captures.

Development evidence requires red-first Pest, XCTest, and Kotlin contracts;
full package and Android suites; generic iOS compilation; component and docs
gates; exact-package showcase tests; plugin validation; and an evidence-backed
constitutional review. Simulator, emulator, screenshot, and physical-device
actions require separate explicit permission for exact targets.

## Non-goals

- Immediate settings changes; use Switch.
- Single or multiple choice collections; use Choice Group.
- Indeterminate or tri-state values.
- Optimistic accepted-state animation.
- Authored icons, placement, colours, variants, shapes, or checkmark styles.
- Automatic form validation or submission.
