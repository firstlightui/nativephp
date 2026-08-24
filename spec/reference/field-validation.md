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
  - tests/Feature/ValidatesFieldsTest.php
  - spec/reference/form-submit.md
  - docs/how-to/validate-fields.md
---

# Firstlight Field Validation Contract

Field validation is a PHP extension over existing Firstlight fields. Laravel's `Validator` owns rules and messages. Native renderers only present the published `error` string. Firstlight does not compile rules to Swift or Kotlin, does not invent a parallel rule DSL, and does not emit a sibling `@nativeError` text node.

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

While `view()`, `fromView()`, and `fromViewPartial()` run, `FieldErrorBag` holds the screen's `MessageBag`. Each participating element's `applyAttributes()` calls `FieldErrorBinder::apply()`.

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

These twelve elements call `FieldErrorBinder::apply()` and expose an `error` string that replaces helper text when non-empty:

| Element | `required` metadata |
| --- | --- |
| Text Field | yes |
| Text Area | yes |
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

## Native boundary

iOS and Android field renderers already treat a non-empty `error` as visible and accessible validation feedback. PHP validation publishes that string through the ordinary Element Tree. No additional native validation engine, focus-stealing overlay, or WebView error list is permitted.

Consumer guidance lives in [Validate fields](../../docs/how-to/validate-fields.md). Guarded submit plus success Feedback lives in [Form submission](form-submit.md). Catalogue expansion rules live in [Catalogue boundary](catalogue-boundary.md).
