---
title: Submit Firstlight forms
description: Validate Firstlight fields, run a PHP action once, and publish success Feedback.
type: how-to
audience: consumer
sources:
  - src/Concerns/SubmitsForms.php
  - src/Concerns/ValidatesFields.php
  - src/NativeComponent.php
  - src/Facades/Feedback.php
  - src/Elements/Button.php
  - tests/Feature/SubmitsFormsTest.php
---

# Submit Firstlight Forms

Firstlight submits forms through an ordinary Button press and a PHP screen
method. There is no `<firstlight:form>` container, client-side validator, or
form schema.

## Declare the screen

Extend `FirstlightUI\NativeComponent` to use the form submission and field
validation helpers. Declare Laravel rules against the public properties bound
by your fields:

```php
<?php

namespace App\Native;

use App\Models\Profile;
use FirstlightUI\NativeComponent;

class EditProfileScreen extends NativeComponent
{
    public string $name = '';

    public string $email = '';

    protected array $rules = [
        'name' => 'required',
        'email' => 'required|email',
    ];

    public function save(): void
    {
        $this->submit(
            action: fn () => Profile::query()->updateOrCreate(
                ['user_id' => auth()->id()],
                ['name' => $this->name, 'email' => $this->email],
            ),
            successMessage: 'Profile saved',
        );
    }
}
```

Screens that extend `Native\Mobile\Edge\NativeComponent` directly can opt in
with both `FirstlightUI\Concerns\ValidatesFields` and
`FirstlightUI\Concerns\SubmitsForms`.

## Bind the fields and Button

Use the existing fields, `native:model`, and Button:

```blade
<firstlight:text-field
    label="Name"
    native:model.blur="name"
/>

<firstlight:text-field
    label="Email"
    native:model.blur="email"
/>

<firstlight:button @press="save">
    Save profile
</firstlight:button>
```

`submit()` runs `$this->validate()` first. A validation failure returns
`false`, skips the callable and success Feedback, and lets
`ValidatesFields` publish each first Laravel message through the matching
field's native `error` slot. See [Validate Firstlight fields](validate-fields.md)
for rule sources, custom messages, and explicit error binding.

After successful validation, `submit()` runs the callable and sends
`Feedback::success($successMessage)->send()` when the message is non-empty.
It then returns `true`. Pass `validate: false` only when the action does not
need field validation:

```php
$sent = $this->submit(
    action: fn () => $this->sendVerificationEmail(),
    successMessage: 'Verification email sent',
    validate: false,
);
```

Unexpected exceptions from the callable are not converted to Feedback or
swallowed.

## Loading and duplicate submissions

`$submitting` is `true` while validation and the callable run. Re-entering
`submit()` on the same PHP component during that interval returns `false`
without validating, calling the action, or sending Feedback. `submit()` always
clears `$submitting` in `finally`, so binding Button `loading` or `disabled` to
`$this->submitting` does not flash during the default one-request path.

NativePHP handles a press and then compiles one Element Tree afterward. A
device-visible loading frame requires application code to set `$this->submitting
= true` outside `submit()`, publish that tree, and clear the flag on a later
request.

The helper does not publish an intermediate tree, call `publishPlaceholder()`,
or add a client-side loading mechanism. `publishPlaceholder()` belongs to
lazy screen mounting, not form submission.
