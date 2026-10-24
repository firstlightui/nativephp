---
title: Build a form screen
description: Recipe for a create form with a Form Request, native field errors, a form-level error, a loading Save button, success Feedback, and return navigation.
type: how-to
audience: consumer
sources:
  - src/NativeComponent.php
  - src/Concerns/SubmitsForms.php
  - src/Concerns/ValidatesFields.php
  - src/Elements/TextField.php
  - src/Elements/TextArea.php
  - src/Elements/Callout.php
  - src/Elements/Button.php
---

# Build a Form Screen

This recipe builds the create screen most apps need: labelled fields, Laravel
validation through a Form Request, errors shown by each native field, an error
for problems that belong to the whole form, a Save button with a loading state,
and a return to the previous screen with success Feedback.

Use it when several values are checked and saved together. Use the
[settings recipe](build-a-settings-screen.md) when each value saves on its own.

## 1. Register the route

```php
use App\NativeComponents\CreatePostScreen;
use Illuminate\Support\Facades\Route;

Route::native('/posts/create', CreatePostScreen::class)->name('posts.create');
```

Register it before any `/posts/{post}` route so `create` is not read as an id.

## 2. Write the Form Request

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|min:3|max:80',
            'body' => 'required|min:10',
        ];
    }

    public function messages(): array
    {
        return [
            'body.min' => 'Write at least a sentence.',
        ];
    }
}
```

Declare rules in the order the fields appear. The error summary lists messages
in rule order.

## 3. Write the screen

```php
<?php

namespace App\NativeComponents;

use App\Http\Requests\StorePostRequest;
use App\Models\Post;
use FirstlightUI\NativeComponent;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CreatePostScreen extends NativeComponent
{
    public string $title = '';

    public string $body = '';

    public function save(): void
    {
        $saved = $this->submit(
            action: function (): void {
                $this->validate(StorePostRequest::class);

                if (Post::query()->where('title', $this->title)->exists()) {
                    throw ValidationException::withMessages([
                        'form' => 'A post with this title already exists.',
                    ]);
                }

                Post::query()->create([
                    'title' => $this->title,
                    'excerpt' => str($this->body)->limit(60)->toString(),
                    'body' => $this->body,
                ]);
            },
            successMessage: 'Post created',
            validate: false,
        );

        if ($saved) {
            $this->back();
        }
    }

    public function navTitle(): string
    {
        return 'New post';
    }

    public function render(): View
    {
        return view('native.create-post');
    }
}
```

`validate: false` stops `submit()` from running `$rules` first, because the Form
Request supplies them inside the action. Any `ValidationException` thrown inside
the action, from the Form Request or from your own check, is shown on the
screen and `submit()` returns `false`.

## 4. Write the view

Save this as `resources/views/native/create-post.blade.php`:

```blade
<native:scroll-view class="w-full flex-1">
    <native:column class="w-full p-4 gap-4">
        <firstlight:callout error-for="form" />

        <firstlight:text-field
            label="Title"
            autocapitalize="sentences"
            submit-label="next"
            native:model="title"
            required
        />

        <firstlight:text-area
            label="Body"
            native:model="body"
            required
        />

        <firstlight:button variant="primary" :loading="$submitting" @press="save">
            Save post
        </firstlight:button>
    </native:column>
</native:scroll-view>
```

No `:error` wiring is needed. After a failed save, each field shows its first
message in its native error slot, and the Callout shows the `form` message.
For long forms, add `<firstlight:callout error-summary />` above the fields to
list every message in one place.

## What each platform shows

| Behaviour | iOS | Android |
| --- | --- | --- |
| Field errors | Error text under the field with error colouring | Material 3 supporting text in the error state |
| Form error | Danger Callout | Danger Callout |
| Screen reader | VoiceOver reads the errors and moves to the first invalid field | TalkBack reads the errors and moves to the first invalid field |
| Success | Returns to the previous screen and shows "Post created" | Returns to the previous screen and shows "Post created" |

## Rules to keep

- Do not wrap `save()` in `try`/`catch`. Firstlight shows validation errors for
  you.
- Do not place `@nativeError` next to Firstlight fields. It skips native error
  colouring and screen-reader announcements.
- Use a key that is not a field name, such as `form`, for errors that belong to
  the whole form.
- `required` on a field is display only. The Form Request enforces it.

## Adapt the recipe

- **Edit instead of create:** load the model in `mount()`, copy its values into
  the properties, and call `update()` instead of `create()`.
- **Validate as the user leaves a field:** add
  `public function updatedTitle(): void { $this->validateOnly('title', StorePostRequest::class); }`.
- **Test it:** see [Test screens](test-screens.md).
