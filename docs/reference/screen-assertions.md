---
title: Screen assertions
description: Firstlight methods added to NativePHP's Native::test() harness, and the make:firstlight-screen options.
type: reference
audience: consumer
sources:
  - src/Testing/ScreenAssertions.php
  - src/Console/MakeScreenCommand.php
  - src/FirstlightServiceProvider.php
  - src/Feedback/FeedbackFake.php
  - vendor/nativephp/mobile/src/Testing/TestableComponent.php
  - tests/Feature/ScreenAssertionsTest.php
  - tests/Feature/MakeScreenCommandTest.php
---

# Screen Assertions

`FirstlightUI\Testing\ScreenAssertions::register()` adds the methods below to
NativePHP's `Native\Mobile\Testing\TestableComponent`, the object returned by
`Native::test()` and `Native::visit()`. They read the most recently published
Element Tree. Interactions send the event type and callback id that the
Firstlight iOS and Android renderers send, then re-render the screen as the
harness does after `tap()` or `input()`.

NativePHP's own methods are unchanged and work on Firstlight controls. These
include `input()`, `tap()`, `toggle()`, `check()`, `set()`, `assertSee()`,
`assertSet()`, and `assertElement()`. For example, `input('email', ...)` types
into a Text Field bound with `native:model="email"`.

## Targets

A field target is either an element `ref` or the property name in the
element's `native:model` binding. A List row is identified by its exact
`headline`.

## Fields and validation

| Method | Contract |
| --- | --- |
| `field($target)` | Returns the wire node for the target, or fails. |
| `assertFieldError($target, $message = null)` | The field's published `error` is not empty and, when given, equals `$message`. |
| `assertNoFieldError($target)` | The field's published `error` is empty. |
| `assertHasErrors($keys)` | The screen's `ValidatesFields` MessageBag has each key. `['email' => 'message']` also requires that message. |
| `assertHasNoErrors($keys = null)` | The bag is empty, or has none of the given keys. |
| `choose($target, $value)` | Chooses the option whose stable value equals `$value` (compared as a string) in a Select, Segmented, Pill Group, or Choice Group. Fails when no option has that value, or the option is disabled or cannot be chosen. A Select option that is already selected cannot be chosen. |

## Lists

| Method | Contract |
| --- | --- |
| `listItem($headline)` | Returns the first List Item node with that headline, or fails and lists the headlines. |
| `assertListItem($headline, $matcher = null)` | A row exists. `$matcher` receives the wire node and must return `true`. |
| `assertMissingListItem($headline)` | No row has that headline. |
| `assertListItemCount($count)` | The tree contains exactly `$count` List Item rows. |
| `pressListItem($headline, $action = null)` | Presses the row, or the leading or trailing swipe action with the label `$action`. Fails for a disabled row. |

## Confirmation Dialog

| Method | Contract |
| --- | --- |
| `assertConfirmationDialog($title = null)` | A Confirmation Dialog is visible, optionally with that title. |
| `assertNoConfirmationDialog()` | No Confirmation Dialog is visible. |
| `confirmDialog($title = null)` | Sends the visible dialog's `@press`. |
| `cancelDialog($title = null)` | Sends the visible dialog's `@dismiss`. |

## Feedback

Call `FirstlightUI\Facades\Feedback::fake()` before the screen sends Feedback.

| Method | Contract |
| --- | --- |
| `assertFeedbackSent($message, $tone = null)` | Feedback with that message, and optionally that `FeedbackTone`, was sent. |
| `assertNoFeedbackSent()` | No Feedback was sent. |

## Pest expectations

`register()` also adds two expectations when Pest is loaded:

```php
expect(Native::test(SignupScreen::class))
    ->toHaveFieldError('email')
    ->toHaveListItem('Inbox');
```

They pair with NativePHP's `Native\Mobile\Testing\PestExpectations`.

## make:firstlight-screen

```bash
php artisan make:firstlight-screen {name} [--form] [--list] [--destroy] [--test] [--force]
```

| Option | Generates |
| --- | --- |
| none | `App\NativeComponents\{name}` extending `FirstlightUI\NativeComponent`, with `render()` returning `native.{kebab-name}`, and a Blade view with a title. |
| `--form` | A required `name` property, `$rules`, `save()` calling `submit()`, a Text Field bound with `native:model`, and a Save Button with `:loading="$submitting"`. |
| `--list` | `mount()`, `reload()`, `loadMore()`, `open()`, and a `listQuery()` stub. A List bound to `@refresh` and `@endReached` renders `$this->listItems`. |
| `--destroy` | Includes `--list`. Adds `requestDeletion()`, `cancelDeletion()`, and `deleteItem()`; rows disabled by `denies('delete', $item)`; a Delete swipe action; and a destructive Confirmation Dialog. |
| `--test` | `tests/Feature/NativeComponents/{name}Test.php` that uses `Native::test()` and these assertions. |
| `--force` | Overwrites existing files. Without it the command fails and writes nothing if any target exists. |

The command is registered only when the application runs in the console.
