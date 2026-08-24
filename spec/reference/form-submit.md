---
title: Firstlight form submission contract
description: Current PHP helper for guarded validation, form actions, success Feedback, and honest Button state timing.
status: current
audience: maintainer
sources:
  - Constitution.md
  - src/Concerns/SubmitsForms.php
  - src/Concerns/ValidatesFields.php
  - src/NativeComponent.php
  - src/Facades/Feedback.php
  - src/Feedback/FeedbackManager.php
  - src/Elements/Button.php
  - tests/Feature/SubmitsFormsTest.php
  - tests/Feature/ValidatesFieldsTest.php
  - docs/how-to/submit-forms.md
---

# Firstlight Form Submission Contract

Form submission is a PHP extension over existing Firstlight fields, Button,
Laravel validation, and Transient Feedback. It adds no Element Tree type,
Blade container, native renderer, or client-side state machine.

## Public PHP surface

`FirstlightUI\Concerns\SubmitsForms` exposes:

```php
public bool $submitting = false;

public function submit(
    callable $action,
    ?string $successMessage = null,
    bool $validate = true,
): bool;
```

`FirstlightUI\NativeComponent` includes this trait. A screen that extends
NativePHP's component directly may use `SubmitsForms` together with
`FirstlightUI\Concerns\ValidatesFields`.

## Execution order

When `$submitting` is already `true`, `submit()` returns `false` immediately.
It does not validate, invoke the callable, or publish Feedback.

Otherwise the helper:

1. sets `$submitting` to `true`;
2. calls `$this->validate()` when `$validate` is `true`;
3. invokes `$action` with no arguments;
4. sends `Feedback::success($successMessage)->send()` when the message is
   non-null and non-blank after trimming;
5. returns `true`; and
6. restores `$submitting` to `false` in `finally`.

The callable's return value is ignored. A non-blank success message is passed
to Feedback unchanged, including surrounding whitespace.

## Validation and failure behaviour

`ValidationException` is converted to a `false` result. `ValidatesFields`
stores the validator's `MessageBag` before rethrowing, so the next normal
publication binds first messages into native field `error` slots. The
callable and success Feedback are skipped after failed validation.

Passing `$validate = false` skips `$this->validate()` entirely. It does not
clear existing field errors.

Unexpected exceptions from validation, the callable, or Feedback propagate.
The `finally` block still releases the submission guard. The helper does not
convert those exceptions to danger Feedback.

## State timing and Button binding

The guard prevents re-entry only while the synchronous callable is executing
on the same PHP component. It does not claim a device-visible lock across
sequential requests. `submit()` always restores `$submitting` to `false` in
`finally`, so `:loading="$this->submitting"` cannot flash during the default
one-request path.

NativePHP dispatches one event and compiles one tree afterward. A published
loading frame requires application code to set `$submitting` outside
`submit()` and clear it on a later request.

`SubmitsForms` must not call `publishPlaceholder()`: that API is for
`#[Lazy]` mounting. It must not invent `wire:loading`, an intermediate
publication, or a native/client-side validation engine without a real public
NativePHP lifecycle API and a separately approved contract.

## Product boundary

Submission remains an ordinary Button `@press` method. Fields continue to use
`native:model` and `ValidatesFields`; outcomes continue to use the public
`FirstlightUI\Facades\Feedback` service. No `<firstlight:form>`, layout
primitive, masks, Filament-style schema, authorization helper, or pagination
binding belongs to this contract.

Consumer guidance lives in
[Submit Firstlight forms](../../docs/how-to/submit-forms.md). Field error
details live in [Field validation](field-validation.md).
