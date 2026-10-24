---
title: Test Firstlight screens
description: Scaffold a screen with make:firstlight-screen and test its fields, lists, dialogs, and Feedback with Native::test().
type: how-to
audience: consumer
sources:
  - src/Console/MakeScreenCommand.php
  - src/Testing/ScreenAssertions.php
  - src/FirstlightServiceProvider.php
  - src/Feedback/FeedbackFake.php
  - tests/Feature/ScreenAssertionsTest.php
  - tests/Feature/MakeScreenCommandTest.php
---

# Test Firstlight Screens

Firstlight screens are tested with NativePHP's screen test harness,
`Native\Mobile\Testing\Native::test()`. The harness mounts the screen,
renders the Element Tree, and sends taps and input through the same callbacks a
device uses. Firstlight adds checks for its own controls: field errors,
List rows, Confirmation Dialog, and Feedback.

## Scaffold a screen

```bash
php artisan make:firstlight-screen Posts --form --destroy --test
```

This creates the following files:

- `app/NativeComponents/Posts.php`: a `FirstlightUI\NativeComponent` with a
  validated `name` field and `save()` (`--form`), and paginated
  `reload()`, `loadMore()`, and `listQuery()` (`--list`). It also has
  `requestDeletion()`, `cancelDeletion()`, and `deleteItem()` (`--destroy`,
  which turns on `--list`).
- `resources/views/native/posts.blade.php`: the matching Text Field, Button,
  List, and Confirmation Dialog markup.
- `tests/Feature/NativeComponents/PostsTest.php`: a Pest test that uses the
  checks below (`--test`).

Nested names such as `Settings/Notifications` create
`App\NativeComponents\Settings\Notifications` and the view
`native.settings.notifications`. The command does not overwrite existing files
unless you pass `--force`. Register the route it prints in `routes/mobile.php`.

Replace the body of `listQuery()` with your paginator query, for example
`Post::query()->latest()->paginate(perPage: 20, page: $page->page)`.

## Register the checks

Register the Firstlight checks once from `tests/Pest.php`:

```php
FirstlightUI\Testing\ScreenAssertions::register();
```

Calling it again has no effect. The generated test also calls it in
`beforeEach()`.

## Test a form

```php
use App\NativeComponents\EditProfile;
use FirstlightUI\Facades\Feedback;
use FirstlightUI\Feedback\FeedbackTone;
use Native\Mobile\Testing\Native;

it('validates and saves the profile', function () {
    Feedback::fake();

    Native::test(EditProfile::class)
        ->input('email', 'not-an-email')
        ->tap('Save')
        ->assertFieldError('email', 'The email must be a valid email address.')
        ->assertNoFeedbackSent()
        ->input('email', 'ada@example.com')
        ->tap('Save')
        ->assertNoFieldError('email')
        ->assertHasNoErrors()
        ->assertFeedbackSent('Profile saved', FeedbackTone::Success);
});
```

`assertFieldError()` reads the `error` value the field shows in the rendered
tree, so it confirms the message reached the control, not only the
MessageBag. Use `assertHasErrors()` to check the bag directly.

Select a Select, Segmented, Pill Group, or Choice Group option by its stable
value, never by its label or position:

```php
Native::test(EditProfile::class)
    ->choose('plan', 'team')
    ->assertSet('plan', 'team');
```

## Test a list and its destructive action

```php
it('deletes a post after confirmation', function () {
    Feedback::fake();

    Native::test(PostsScreen::class)
        ->assertListItem('Draft', fn (array $row) => $row['props']['disabled'] === false)
        ->pressListItem('Draft', 'Delete')
        ->assertConfirmationDialog('Delete post?')
        ->confirmDialog()
        ->assertMissingListItem('Draft')
        ->assertFeedbackSent('Post deleted');
});
```

`pressListItem('Draft')` presses the row. Pass a swipe action label as the
second argument to press that action. Both fail when the row is disabled, as
the device would ignore the press. `cancelDialog()` sends the dismissal that
both platforms send for Cancel and system dismissal.

See [Screen assertions](../reference/screen-assertions.md) for every method.
