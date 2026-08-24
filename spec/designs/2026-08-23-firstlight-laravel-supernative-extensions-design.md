---
title: Firstlight Laravel SuperNative extensions design
description: Approved layering for Laravel-shaped PHP extensions on SuperNative, the implemented validation, submission, and authorization contracts, and the remaining ranked queue.
status: historical
sources:
  - Constitution.md
  - spec/reference/catalogue-boundary.md
  - spec/reference/field-validation.md
  - spec/reference/form-submit.md
  - spec/reference/action-authorization.md
  - spec/reference/list-pagination.md
  - spec/reference/destructive-list-actions.md
  - spec/components/transient-feedback.md
  - spec/components/list.md
  - spec/components/button.md
  - spec/components/confirmation-dialog.md
  - src/Concerns/ValidatesFields.php
  - src/Concerns/SubmitsForms.php
  - src/Concerns/AuthorizesActions.php
  - src/Concerns/PaginatesLists.php
  - src/Concerns/DestroysListItems.php
  - src/Authorization/GateEvaluator.php
  - src/Validation/FieldErrorBinder.php
  - docs/how-to/validate-fields.md
  - docs/how-to/submit-forms.md
  - docs/how-to/authorize-actions.md
  - docs/how-to/paginate-lists.md
  - docs/how-to/destroy-list-items.md
---

# Firstlight Laravel SuperNative Extensions Design

Date: 2026-08-23

Status: approved

This dated record is historical context, not a current implementation checklist. Current behaviour lives in [Field validation](../reference/field-validation.md), [Form submission](../reference/form-submit.md), [Action authorization](../reference/action-authorization.md), [List pagination](../reference/list-pagination.md), [Destructive list actions](../reference/destructive-list-actions.md), and [Catalogue boundary](../reference/catalogue-boundary.md). Items below that are not implemented must not be described as shipped.

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
- Submit remains a Button `@press`; screens may call `validate()` directly or
  use the implemented `SubmitsForms::submit()` workflow described below.
- No Swift/Kotlin rule engine and no sibling HTML-style `@error` directive.

Follow-on validation work, if any, must stay inside this binder: optional form-level Callout or Feedback summary, optional moving VoiceOver/TalkBack focus to the first invalid field, still without a native validator.

## Ranked extensions after validation

These items are approved as PHP SuperNative extensions, not catalogue
commitments. Implementation state is recorded on each item.

### 1. Form submit helper (implemented)

`FirstlightUI\Concerns\SubmitsForms` exposes public
`bool $submitting = false` and
`submit(callable $action, ?string $successMessage = null, bool $validate = true): bool`.
It rejects re-entry while the synchronous callable runs, validates through
`ValidatesFields` by default, returns `false` for validation failure, invokes
the callable, and sends non-blank success copy through
`Feedback::success(...)->send()`. Unexpected exceptions propagate.

The guard resets in `finally`. NativePHP compiles one tree after dispatch, so
the default `true` then `false` transition cannot flash Button `loading`
inside one request. Authors may bind `:loading="$this->submitting"` for a
workflow that deliberately publishes `true` across a round-trip, but the
helper does not invent an intermediate publication or use the lazy-mount-only
`publishPlaceholder()`. It is not a layout container.

### 2. Authorization on actions (implemented)

`FirstlightUI\Concerns\AuthorizesActions` exposes `allows()`, `denies()`, and
non-throwing `authorize()`. `GateEvaluator` uses Laravel's Gate facade,
accepts a callable test resolver, and fails closed when Gate is unbound.
Denied `authorize()` calls publish danger Feedback and return `false` instead
of throwing an `AuthorizationException` into NativePHP's generic overlay path.

Ordinary actions use explicit Blade hide-or-disable composition. Destructive
Button, Icon Button, and List Item actions stay visible and disabled when
denied. Confirmation Dialog has no invented disabled or loading state, so PHP
authorizes before opening it and again before mutation. No `can` element prop,
native authorization engine, or new action element was added. The maintained
contract is [Action authorization](../reference/action-authorization.md).

### 3. List pagination (implemented)

`FirstlightUI\Concerns\PaginatesLists` exposes public `$listItems`,
`$listHasMore`, `$listPage`, `$listCursor`, and `$listPaginating`, plus
`refreshList(callable $fetch)` and `loadMoreList(callable $fetch)`. Each
callable receives `ListPage` (`$page`, `$cursor`) and must return a Laravel
paginator or compatible `items()` / `hasMorePages()` object.

`refreshList()` replaces accumulated rows with the first page. `loadMoreList()`
appends the next page and no-ops when `$listHasMore` is false. Re-entry while
a fetch is running returns `false`. Empty collections remain Blade composition
of Status Label, Callout, and Button. No Empty primitive or second scroll-event
vocabulary was added. The maintained contract is
[List pagination](../reference/list-pagination.md).

### 4. Media field (designed 2026-08-24 — not yet implemented)

Form-grade `<firstlight:media>` for one image or one document, `MediaValue` on
Laravel Storage (default `mobile_public`), ValidatesFields integration, and
Firstlight-owned crop when `aspect` / `crop` require it. Camera + library for
images; system file picker for documents. See
[Media field design](2026-08-24-firstlight-media-field-design.md). This
supersedes the earlier “no crop” sketch for this item only.

### 5. Destructive list actions (implemented)

`FirstlightUI\Concerns\DestroysListItems` exposes `$confirmingListDestruction`,
`$pendingListDestructionKey`, `requestDestructiveListAction()`,
`cancelDestructiveListAction()`, and `confirmDestructiveListAction()`. Rows are
addressed by stable keys (`getKey()`, `id`, or an `id` array key), never
renderer indexes. Request and confirm both call `authorize()`. Confirmation
Dialog stays the destructive presentation surface. Success Feedback is
optional. Matching rows are removed from `$listItems` after a successful
destroy. No swipe-delete or trailing-action catalogue API was added. The
maintained contract is
[Destructive list actions](../reference/destructive-list-actions.md).

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
