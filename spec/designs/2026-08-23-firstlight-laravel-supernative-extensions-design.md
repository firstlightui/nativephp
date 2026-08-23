---
title: Firstlight Laravel SuperNative extensions design
description: Approved layering for Laravel-shaped PHP extensions on SuperNative, the current field-validation contract, and the ranked queue after validation.
status: historical
sources:
  - Constitution.md
  - spec/reference/catalogue-boundary.md
  - spec/reference/field-validation.md
  - spec/components/transient-feedback.md
  - spec/components/list.md
  - spec/components/button.md
  - spec/components/confirmation-dialog.md
  - src/Concerns/ValidatesFields.php
  - src/Validation/FieldErrorBinder.php
  - docs/how-to/validate-fields.md
---

# Firstlight Laravel SuperNative Extensions Design

Date: 2026-08-23

Status: approved

This dated record is historical context, not a current implementation checklist. Current behaviour lives in [Field validation](../reference/field-validation.md) and [Catalogue boundary](../reference/catalogue-boundary.md). Items below that are not implemented must not be described as shipped.

## Objective

Give NativePHP application authors Laravel experience on genuine SuperNative controls: the same Validator, Gates, paginators, Storage, and Notifications they already know, presented through Firstlight fields, actions, lists, and Feedback.

Firstlight does not rebuild NativePHP Mobile UI. Extensions are PHP services. Native code presents existing props (`error`, `loading`, list rows, confirmation, feedback tones).

## Layering rule

```
Laravel Validator / Gate / Paginator / Storage / Notification
        → Firstlight PHP (traits, binders, facades)
                → existing Element Tree props and chrome
                        → SwiftUI / Compose
```

A new `<firstlight:...>` tag is allowed only when no existing control can express the contract at equal iOS and Android quality. Layout, navigation chrome, input masks, and schema builders remain out of catalogue.

## Validation (current, in package)

Validation is the model extension. It is implemented:

- `Illuminate\Validation` is the engine.
- `ValidatesFields` stores a `MessageBag` on the screen.
- `FieldErrorBinder` writes `MessageBag::first($key)` into the field `error` slot when the author did not supply a non-empty `error`.
- Keys resolve from `error-for`, then `native:model` / `native:model.*`, then compiled `__syncProperty`.
- Twelve fields participate. Search Field does not. `required` stays authored metadata and is not inferred from rules. Switch, Slider, and Stepper reject `required` and still accept bound `error`.
- Submit remains a Button `@press` that calls `validate()` or `validateOnly()`.
- No Swift/Kotlin rule engine and no sibling HTML-style `@error` directive.

Follow-on validation work, if any, must stay inside this binder: optional form-level Callout or Feedback summary, optional moving VoiceOver/TalkBack focus to the first invalid field, still without a native validator.

## Ranked queue after validation

These items are approved as PHP SuperNative extensions. They are not catalogue commitments and are not implemented by this record.

### 1. Form submit helper

Collect named field models, run `validate()` on submit, set Button `loading`, republish field errors, and on success use Transient Feedback. Not a layout container. Modal and Bottom Sheet already host content.

### 2. Authorization on actions

Map `Gate` / `Policy` onto Button, Icon Button, List Item, and Confirmation Dialog as explicit hide-versus-disable behaviour. Destructive actions stay visible when denied, with helper or error text. A 403 may publish Feedback instead of a blank screen. Confirmation Dialog `tone="destructive"` remains the native `authorize()` expression.

### 3. List pagination

Bind `LengthAwarePaginator` or cursor pagination to List `@refresh` and `@end-reached`. Empty collections compose Status Label, Callout, and Button rather than a new Empty primitive unless that composition is proven painful. Do not invent a second scroll-event vocabulary.

### 4. Media field (new component, after the PHP layer is stable)

Form-grade image or file field using NativePHP Camera/Photos plugins, Laravel `Storage`, and `image` / `file` rules lighting the same `error` slot as Text Field. v1 is one image or one document, PHP-owned path, native picker or camera sheet. No crop editor and no gallery CMS. This is the first ranked item that may add a catalogue tag, because Mobile UI does not ship a form-grade media field.

### 5. Destructive list actions

Stable action keys on List Item (not renderer indexes), `authorize()`, Confirmation Dialog for destructive tone, then Feedback and list republish. Laravel `destroy` + Policy as SuperNative list behaviour. Depends on List's adapter remaining able to carry the contract without leaking a second Mobile UI API.

### 6. Notification bridge

Map `Notification::send()` onto Feedback tones and optionally NativePHP push plugins. Feedback remains the in-session queue. Do not add a second toast API.

### 7. Locale as package chrome

`__()` already works in Blade. Remaining work is package-owned strings (Confirmation defaults, Feedback dismiss, Search clear) and proving Date/Time pickers follow application locale and timezone. No translation UI.

## Explicitly deferred

- Text Field masks and formatters (already deferred in the Text Field design).
- Dependent-field schema APIs; Blade `@if` plus server republish is sufficient.
- Skeleton/shimmer primitives while Button `loading` and Activity Indicator exist.
- Settings page kits, auth/onboarding screens, and navigation shells.
- Client-side validation, HTML forms, and Livewire `@error` clones.

## Evidence when an item is implemented

Each shipped extension needs PHP contract tests, consumer documentation that names only implemented APIs, and no native rule engine. A new component still follows `spec/workflows/adding-components.md` and [Catalogue boundary](../reference/catalogue-boundary.md).
