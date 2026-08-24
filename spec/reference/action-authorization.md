---
title: Firstlight action authorization
description: Current Laravel Gate and Policy evaluation contract for hiding, disabling, and guarding Firstlight actions.
status: current
audience: maintainer
sources:
  - Constitution.md
  - src/Authorization/GateEvaluator.php
  - src/Concerns/AuthorizesActions.php
  - src/NativeComponent.php
  - src/Facades/Feedback.php
  - src/Feedback/FeedbackManager.php
  - src/Elements/Button.php
  - src/Elements/IconButton.php
  - src/Elements/ListItem.php
  - src/Elements/ConfirmationDialog.php
  - tests/Feature/AuthorizesActionsTest.php
  - docs/how-to/authorize-actions.md
  - docs/how-to/destroy-list-items.md
  - src/Concerns/DestroysListItems.php
---

# Firstlight Action Authorization

Action authorization is a PHP extension over existing controls. Laravel Gate
and Policy decisions stay server-owned. Firstlight maps those decisions onto
authored Blade branches and the existing `disabled` or `visible` props; it
does not publish abilities to SwiftUI or Jetpack Compose.

## Public PHP surface

`FirstlightUI\Concerns\AuthorizesActions` exposes:

- `allows(string $ability, mixed ...$arguments): bool`
- `denies(string $ability, mixed ...$arguments): bool`
- `authorize(string $ability, mixed ...$arguments): bool`

`allows()` evaluates `Gate::allows($ability, $arguments)`. `denies()` is its
strict boolean inverse. Arguments retain their variadic order when passed to
Laravel Gate.

`authorize()` returns `true` when allowed. When denied, it publishes
`Feedback::danger('This action is unauthorized.')->send()` and returns
`false`. It deliberately does not call Laravel Gate's throwing `authorize()`
method. Consumer action handlers must return when this method returns
`false`.

## Gate resolution

`GateEvaluator` uses the `Illuminate\Support\Facades\Gate` facade when its
application container has `Illuminate\Contracts\Auth\Access\Gate` bound. An
unbound Gate returns `false` as a fail-closed default.

For focused tests, `GateEvaluator` accepts a callable with the shape
`(string $ability, array $arguments, mixed $user): bool`. A screen may
override protected `actionGateEvaluator()` to supply it. Protected
`gateUser()` returns `null` by default, which tells the evaluator to use
Laravel's current Gate user. A non-null override evaluates through
`Gate::forUser($user)` and exists for controlled test or host integration
seams.

## Presentation policy

Ordinary non-destructive actions have an explicit product choice:

- hide with Blade `@if ($this->allows(...))` when absence is unsurprising;
- keep visible and bind `:disabled="$this->denies(...)"` when location or
  unavailable context matters.

Button, Icon Button, and List Item are the supported disabled action surfaces.
Authorization adds no `can` element prop and changes no element compiler or
native renderer.

Destructive actions must stay visible when denied. A destructive Button, Icon
Button, or List Item is disabled rather than omitted, with authored supporting
or nearby text when the denial would otherwise be unclear. This visibility
rule is consumer composition guidance, not a new element.

Confirmation Dialog exposes `visible` and `tone` (`default` or
`destructive`), but no disabled or loading state. The PHP request handler
guards with `authorize()` before setting `visible=true`. The confirmation
handler closes the dialog and guards again before mutation, covering policy
changes between presentation and confirmation. A denied request never opens
the dialog.

## Failure and native boundary

Every mutating handler remains responsible for an action-time `authorize()`
guard even when the published control was hidden or disabled. A policy can
change after frame publication. The denied action produces danger Feedback
and a normal `false` return instead of an uncaught `AuthorizationException`
that NativePHP could present as a generic error overlay.

Gate exceptions other than an ordinary boolean denial are not converted into
authorization decisions. Misconfigured policies and application failures
remain visible failures.

No new native state, event, component, layout, navigation, mask, schema,
submission, or pagination API belongs to this extension. List-row destruction
by stable key is specified separately in
[Destructive list actions](destructive-list-actions.md). Consumer guidance
lives in [Authorize Firstlight actions](../../docs/how-to/authorize-actions.md).
