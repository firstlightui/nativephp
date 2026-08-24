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
  - tests/Feature/ValidatesFieldsTest.php
---

# ValidatesFields

`FirstlightUI\Concerns\ValidatesFields` is a Livewire-shaped Laravel Validator
layer for NativePHP EDGE screens. `FirstlightUI\NativeComponent` includes the
trait. See [Validate fields](../how-to/validate-fields.md) for a complete
working screen.

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

The view also receives `$errorBag` for explicit `:error="$errorBag->first('email')"`
bindings.

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
