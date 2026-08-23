---
title: Validate Firstlight fields
description: Show Laravel validation messages on Firstlight fields with validate() and validateOnly().
type: how-to
audience: consumer
sources:
  - src/Concerns/ValidatesFields.php
  - src/NativeComponent.php
  - src/Validation/FieldErrorBag.php
  - src/Validation/FieldErrorBinder.php
  - tests/Feature/ValidatesFieldsTest.php
---

# Validate Firstlight Fields

NativePHP EDGE has no built-in `validate()`. Firstlight adds a Livewire-shaped
Laravel Validator layer so a screen can call `$this->validate()` or
`$this->validateOnly()`, and field components show the first `MessageBag`
message in the native `error` slot.

Do not place `@nativeError` next to Firstlight fields. That directive emits a
sibling text node and skips native field error colour, helper replacement, and
accessibility.

## Opt in

Use the trait on a `NativeComponent`, or extend the thin
`FirstlightUI\NativeComponent` subclass that already includes it:

```php
use FirstlightUI\Concerns\ValidatesFields;
use Native\Mobile\Edge\NativeComponent;

class SignupScreen extends NativeComponent
{
    use ValidatesFields;
}
```

## Declare rules and bind fields

Rule keys must match public property names (`email` ↔ `native:model="email"`).
Nested and wildcard keys are not supported in this version.

```php
public string $email = '';
public bool $accepted = false;

protected array $rules = [
    'email' => 'required|email',
    'accepted' => 'accepted',
];

public function updatedEmail(): void
{
    $this->validateOnly('email');
}

public function save(): void
{
    $this->validate();
    // persist
}
```

```blade
<firstlight:text-field label="Email" native:model.blur="email" />
<firstlight:checkbox label="I agree" native:model="accepted" />
<firstlight:button label="Continue" @press="save" />
```

No `:error` wiring is required. After a failed `validate()` or
`validateOnly()`, the next published frame fills each field's `error` from
`MessageBag::first($field)`. An authored `error` attribute wins over the bag.

You can still bind explicitly when you want to:

```blade
<firstlight:text-field
    label="Email"
    native:model.blur="email"
    :error="$errorBag->first('email')"
/>
```

`required` on a field is display metadata only. Laravel `required`,
`accepted`, and other rules remain the enforcement.

## API

Familiar Laravel and Livewire methods, backed by `Illuminate\Validation`:

- `$this->validate($rules = null, $messages = [], $attributes = [])`
- `$this->validateOnly($field)`
- `$rules` / `$messages` / `$validationAttributes` properties, or
  `rules()`, `messages()`, and `validationAttributes()` methods
- Form Request class names: `$this->validate(StoreSignupRequest::class)` uses
  that request's `rules()`, `messages()`, and `attributes()` — not HTTP
  redirects
- `addError($field, $message)`, `resetValidation($field = null)`,
  `getErrorBag()`, `hasError($field)`

Call these from actions, `@submit` / `@change` handlers, and
`updated{Property}` hooks. On success, messages for the validated keys are
cleared. `validateOnly('email')` replaces only the `email` key.

When the rule key differs from the model name, set `error-for`:

```blade
<firstlight:text-field
    label="Work email"
    native:model="email"
    error-for="contact_email"
/>
```

## Where validate() belongs

`mount()` and `render()` still sit in NativePHP's generic `Throwable` overlay
path. Throwing `ValidationException` there paints the red error overlay
instead of field messages. Upstream NativePHP catching `ValidationException`
in `mount` / `render` would be a later compatibility ask, not a blocker.
