---
title: ValidatesFields
description: Public contract for Firstlight Laravel field validation, MessageBag binding, and validate() timing.
type: reference
audience: consumer
sources:
  - src/Concerns/ValidatesFields.php
  - src/NativeComponent.php
  - src/Validation/FieldErrorBag.php
  - src/Validation/FieldErrorBinder.php
  - src/Validation/ErrorSummary.php
  - src/Validation/ValidationAlert.php
  - src/Elements/Callout.php
  - tests/Feature/ValidatesFieldsTest.php
---

# ValidatesFields

`FirstlightUI\Concerns\ValidatesFields` extends NativePHP Mobile's
`ValidatesInput` with Form Request sources, Media conversion, error summaries,
and accessibility announcements. `FirstlightUI\NativeComponent` includes the
trait. Validation and bag management use upstream's implementation; existing
Firstlight screen code does not need a trait swap. See
[Validate fields](../how-to/validate-fields.md) for a complete working screen.

Do not place `@nativeError` next to Firstlight fields. That directive emits a
sibling text node and skips native field error colour, helper replacement, and
accessibility.

## Methods

| Method | Contract |
| --- | --- |
| `validate($rules = null, $messages = [], $attributes = [])` | Validates public properties. Returns the validated array. On failure, stores the exception bag and rethrows `ValidationException`. On success, clears messages for the validated keys. |
| `validateOnly($field, $rules = null, $messages = [], $attributes = [])` | Validates one key. On failure, replaces only that key. On success, forgets that key. Other bag keys stay put. |
| `addError($field, $message)` | Appends one message without throwing. |
| `resetValidation($field = null)` | Forgets one key, an array of keys, or the whole bag. |
| `getErrorBag()` | Returns the current `Illuminate\Support\MessageBag`. |
| `hasError($field)` | Whether that key currently has messages. |

`$rules` may be an array, `null` (use the screen's rules), or a class name.
A class name is resolved through the container when possible, then
`$source->rules()`, and optionally `messages()` and `attributes()`. This is
the Form Request path: Firstlight does not perform an HTTP redirect.

When `$messages` or `$attributes` are empty, the trait reads the screen's
`messages()` / `$messages` and `validationAttributes()` /
`$validationAttributes`.

Action dispatch catches `ValidationException` from `@press`, `@change`,
`@submit`, and `updated{Property}` handlers, stores the bag, and republishes
the screen. Other exceptions still propagate.

## Field binding

During the screen's view pass, participating fields read the current bag and
set their native `error` slot from the first message for the resolved name:

1. A non-empty authored `error` attribute wins and skips the bag.
2. Otherwise `error-for` (or `errorFor`) selects the bag key.
3. Otherwise the `native:model` / `native:model.*` property name is used.
4. Otherwise a compiled `__syncProperty('name')` change callback is used.

An empty authored `error` is treated as unset. Fields without a resolvable
name stay empty. Only the first message for a key is published.

The view receives upstream's `$errors` (`ViewErrorBag`) and Firstlight's
`$errorBag` (`MessageBag`) for explicit bindings. Both expose the same messages.
Native and Firstlight fields read the shared `FieldErrors` render scope, so
mixed-control screens work together. A screen using only upstream
`ValidatesInput` also binds Firstlight field errors, but does not add
Firstlight's Form Request, Media, or announcement extensions.

## Form-level Callouts

[Callout](../components/callout.md) displays bag messages that belong to no
field:

| Attribute | Shows | Publishes nothing when |
| --- | --- | --- |
| `error-for="key"` | `MessageBag::first('key')` | the key has no message |
| `error-summary` | every key's first message, in bag order; a `firstlight::validation.summary` heading when there are two or more | the bag is empty |

Both default to `danger`, reject `message`, and are mutually exclusive. They
never receive field focus. The summary follows bag order, which is the order
of your rules, so list rules in screen order.

## Announcements and focus

| Interaction | Spoken once | Screen-reader focus |
| --- | --- | --- |
| `@press` or `@submit` that adds messages | the summary of the whole bag | first invalid field in render order |
| any other event (model sync, `@change`, `updated{Property}`) that adds messages | only the messages it added | unchanged |
| an interaction that adds no message | nothing | unchanged |

"Adds messages" means a failed `validate()` or `validateOnly()`, or
`addError()`. Frames republished before the next interaction carry the same
alert, and the platforms act on it once. iOS moves
VoiceOver focus and posts an announcement. Android acts while TalkBack is on:
Text Field, Text Area, and Code Editor take input focus, and every field
announces.

## Participating fields

These components apply bag messages when the screen uses `ValidatesFields`:

[Text Field](../components/text-field.md),
[Text Area](../components/text-area.md),
[Checkbox](../components/checkbox.md),
[Switch](../components/switch.md),
[Select](../components/select.md),
[Segmented](../components/segmented.md),
[Choice Group](../components/choice-group.md),
[Pill Group](../components/pill-group.md),
[Date Picker](../components/date-picker.md),
[Time Picker](../components/time-picker.md),
[Slider](../components/slider.md),
and [Stepper](../components/stepper.md).

`required` on those fields is display metadata and does not run Laravel rules.

## Current limitations

- Only public instance properties are validated. Nested and wildcard rule keys
  are not supported.
- Call `validate()` and `validateOnly()` from actions and property hooks, not
  from `mount()` or `render()`. Those lifecycle methods still sit on
  NativePHP's generic `Throwable` overlay path, so a `ValidationException`
  paints the red overlay instead of field messages.
- `addError()` does not throw and may run from `render()` when a fixture needs
  a stable failed frame.
