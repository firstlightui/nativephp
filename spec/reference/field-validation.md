---
title: Firstlight field validation contract
description: Current PHP validation binding from Laravel Validator and MessageBag onto Firstlight field error slots without a native rule engine.
status: current
audience: maintainer
sources:
  - Constitution.md
  - src/Concerns/ValidatesFields.php
  - src/NativeComponent.php
  - src/Validation/FieldErrorBag.php
  - src/Validation/FieldErrorBinder.php
  - src/Elements/TextField.php
  - src/Elements/TextArea.php
  - src/Elements/SwitchControl.php
  - src/Elements/Checkbox.php
  - src/Elements/ChoiceGroup.php
  - src/Elements/PillGroup.php
  - src/Elements/Segmented.php
  - src/Elements/Select.php
  - src/Elements/DatePicker.php
  - src/Elements/TimePicker.php
  - src/Elements/Slider.php
  - src/Elements/Stepper.php
  - src/Elements/SearchField.php
  - src/Validation/ValidationAlert.php
  - src/Validation/ErrorSummary.php
  - src/Elements/Callout.php
  - src/Components/Callout.php
  - lang/en/validation.php
  - resources/ios/FirstlightValidationAlert.swift
  - resources/android/FirstlightValidationAlert.kt
  - tests/Feature/ValidatesFieldsTest.php
  - tests/ios/ValidationAlertTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/ValidationAlertTest.kt
  - spec/reference/form-submit.md
  - docs/how-to/validate-fields.md
  - docs/reference/validates-fields.md
---

# Firstlight Field Validation Contract

Field validation is a PHP extension over existing Firstlight fields. Laravel's `Validator` owns rules and messages. Native renderers only present the published `error` string. Firstlight does not compile rules to Swift or Kotlin, does not invent a parallel rule DSL, and does not emit a sibling `@nativeError` text node.

`ValidatesFields` composes Mobile 4.7's `ValidatesInput`: validation, field-only
bag updates, clearing, and public-property collection delegate upstream.
Firstlight retains Form Request sources, Media conversion, standalone test
validation, error summaries, and interaction-scoped accessibility alerts.

## Public PHP surface

`FirstlightUI\Concerns\ValidatesFields` is the opt-in API. `FirstlightUI\NativeComponent` already uses the trait. Screens that extend NativePHP's `NativeComponent` directly may `use ValidatesFields`.

The trait exposes:

- `validate($rules = null, $messages = [], $attributes = [])`
- `validateOnly($field, $rules = null, $messages = [], $attributes = [])`
- `addError($field, $message)`
- `resetValidation($field = null)`
- `getErrorBag()`
- `hasError($field)`

Rules come from the `$rules` argument, `rules()`, or the `$rules` property. Custom messages and attribute names come from matching methods or properties. Passing a Form Request class name uses that class's `rules()`, `messages()`, and `attributes()` without HTTP redirects.

Rule keys must equal public property names. Nested and wildcard keys are unsupported.

`validate()` replaces the bag with the failed keys, or clears those keys on success. `validateOnly($field)` replaces or clears only that key. `dispatch()` catches `ValidationException` from user actions and stores the bag; other throwables still propagate. Throwing `ValidationException` while NativePHP is constructing or rendering the screen still hits NativePHP's generic overlay path.

## Error slot binding

While `view()`, `partial()`, `fromView()`, and `fromViewPartial()` run, upstream
`FieldErrors` holds the screen's `MessageBag`. `FieldErrorBag` delegates bag
lookups and scopes upstream and adds only Firstlight's alert context, tied to
the same bag so a nested upstream screen cannot claim a parent's alert. Each
participating element's `applyAttributes()` calls `FieldErrorBinder::apply()`.
Views receive upstream's `$errors` and the existing `$errorBag` alias.

`FieldErrorBinder` resolves the bag key in this order:

1. non-empty `error-for` / `errorFor`
2. the value of `native:model` or any `native:model.*` attribute
3. a compiled `_change` callback of the form `__syncProperty('name')`

Then:

- a non-empty authored `error` attribute wins and the bag is ignored for that element;
- an omitted or empty `error` receives `MessageBag::first($key)` when the bag is present and that first message is non-empty;
- missing bag, missing field name, or missing `error()` method leaves the slot unchanged.

The binder does not set `required`, `disabled`, `helper`, or any other prop.

## Validation surface

These thirteen elements call `FieldErrorBinder::apply()` and expose an `error` string that replaces helper text when non-empty:

| Element | `required` metadata |
| --- | --- |
| Text Field | yes |
| Text Area | yes |
| Media | yes |
| Checkbox | yes |
| Choice Group | yes |
| Pill Group | yes |
| Segmented | yes |
| Select | yes |
| Date Picker | yes |
| Time Picker | yes |
| Switch | no; `required` is rejected |
| Slider | no; `required` is rejected |
| Stepper | no; `required` is rejected |

`required` on fields that accept it is authored display metadata. It does not run Laravel `required` / `accepted` rules and is not derived from `$rules`.

Search Field rejects `error`, `required`, `label`, and `helper`. It is a query control, not a validation surface.

Action, presentation, display, and collection components (Button, Icon Button, Confirmation Dialog, Modal, Bottom Sheet, Badge, Status Label, Callout, Progress, Activity Indicator, List, List Item, List Section) are not MessageBag targets.

## Form-level errors

Messages that belong to no single field (for example a failed sign-in, `addError('form', ...)`) and summaries of field messages reuse [Callout](../components/callout.md); there is no new catalogue tag.

- `<firstlight:callout error-for="form" />` shows `MessageBag::first('form')`.
- `<firstlight:callout error-summary />` shows `ErrorSummary::text()`: every key's first message in bag order, either the single message alone or the `firstlight::validation.summary` heading (`There are :count errors.`) and one message per line.

Both default to the `danger` tone, reject an authored `message`, and are mutually exclusive. While their key or bag has no message the Blade adapter publishes no node, like Blade's `@error`. A bound Callout is not a field: it never takes the error slot, `required`, or focus.

## Screen-reader alerts

`ValidatesFields` records which keys an interaction added messages for: `validate()` failures (every failed key), `validateOnly()` failures (that key), and `addError()`. When the outermost `dispatchUiEvent()` ends with at least one recorded key that is still in the bag, it stores one `ValidationAlert`. Every `view()` / `fromView()` render until the next interaction republishes it, because NativePHP may render one publication more than once; each render claims the announcement and focus afresh from a copy. The next interaction clears it before dispatching, so a passing interaction publishes none. Partials never carry it.

| Event type | Meaning | Announcement | Focus |
| --- | --- | --- | --- |
| `PRESS` (0), `SUBMIT` (4) | form submission | `ErrorSummary::announcement()` of the whole bag | first invalid field |
| any other (text, toggle, select, slider, …) | property sync, including `updated{Property}` | messages for the keys just failed only | none |

During that publication the first error-bound element in render order that is concerned by the alert (a field whose key is in the alert, or a summary/`error-for` Callout) receives `validation_announcement`; the first error-bound field receives `validation_focus: true` when focus moves. Both carry the same increasing integer `validation_alert` revision. A repeated identical failure gets a new revision. An element concerned by the alert may be an error-bound Callout, which then carries the announcement but never focus.

Renderers act on each revision once, including on the frame that creates the node, and never on republication of the same revision:

- **iOS** moves VoiceOver focus with `@AccessibilityFocusState` to the field's primary accessible element (the input, toggle, trigger, first choice, or Media choose button), then posts `AccessibilityNotification.Announcement` with low speech priority so it queues behind the focused field. Keyboard focus is unchanged; VoiceOver users do not get an unexpected keyboard.
- **Android** acts only while TalkBack (touch exploration) is enabled. Text Field, Text Area, and Code Editor request input focus with a `FocusRequester`, which TalkBack follows and reads with the field's `error` semantics; other fields do not move focus, because Android moves input focus only to editable text. The announcement is then sent with `View.announceForAccessibility`.

Text that names the error (`Error: …`) remains each control's existing semantics. The alert adds no visible chrome and does not repeat on rotation, reconciliation, or unrelated republication.

## Native boundary

iOS and Android field renderers already treat a non-empty `error` as visible and accessible validation feedback. PHP validation publishes that string through the ordinary Element Tree. The only native additions are the one-time alert above; no native validation engine, focus-stealing overlay, live region, or WebView error list is permitted.

Consumer guidance lives in [Validate fields](../../docs/how-to/validate-fields.md) and the [ValidatesFields reference](../../docs/reference/validates-fields.md). Guarded submit plus success Feedback lives in [Form submission](form-submit.md). Catalogue expansion rules live in [Catalogue boundary](catalogue-boundary.md).
