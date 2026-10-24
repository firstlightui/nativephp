---
name: building-firstlight-screens
description: Builds NativePHP Mobile screens with Firstlight UI controls, Laravel validation, lists, dialogs, Feedback, native navigation chrome, and theme tokens. Use when adding or changing a native screen, form, list, settings page, or its tests in an app that requires firstlightui/nativephp.
---

# Building Firstlight Screens

Firstlight renders genuine SwiftUI and Jetpack Compose controls from one Blade view. Navigation chrome and layout primitives come from NativePHP Mobile and Mobile UI. Read https://firstlightui.dev/llms-full.txt for the complete component reference.

## Pick the pattern first

| The screen... | Start from |
| --- | --- |
| Is the app shell, or pushes a detail view | [List and detail flow](https://firstlightui.dev/docs/how-to/build-a-list-detail-flow) |
| Shows many records to find, open, or delete | [Searchable list](https://firstlightui.dev/docs/how-to/build-a-searchable-list-screen) |
| Validates several values and saves with a button | [Form screen](https://firstlightui.dev/docs/how-to/build-a-form-screen) |
| Changes preferences one at a time | [Settings screen](https://firstlightui.dev/docs/how-to/build-a-settings-screen) |

Copy the recipe, then change names, models and fields. Or scaffold with `php artisan make:firstlight-screen Name [--form] [--list] [--destroy] [--test]` and replace every `TODO`.

## Workflow

1. Create the screen class extending `FirstlightUI\NativeComponent`, its view in `resources/views/native/`, and a `Route::native()` entry with the app's native-chrome layout.
2. Bind controls with `native:model`. Use plain `native:model` or `.live` for text fields submitted by a separate Button, because `.blur` is not guaranteed to reach PHP before the press. Use `.blur` only with `updated{Property}` validation on leave, and `.debounce.300ms` for search. Choose a control by meaning:
   - Text: `text-field`, `text-area`, `search-field`, `code-editor`
   - One choice: `segmented` (2–5 short options), `select` (many), `choice-group` (visible radios)
   - Many choices: `pill-group`, `choice-group multiple`
   - Booleans: `switch` (settings, applies now), `checkbox` (forms and consent)
   - Numbers and dates: `stepper`, `slider`, `date-picker`, `time-picker`
   - Files: `media` (image or document mode)
   - Rows: `list`, `list-section`, `list-item` (with `trailing-actions`, `leading-actions`, `context-menu`)
   - Status: `status-label`, `badge`, `metric`, `progress`, `activity-indicator`, `callout`
   - Overlays: `confirmation-dialog`, `alert-dialog`, `bottom-sheet`, `modal`
   - Outcomes: `Feedback::success('Saved')->send()` or `submit(successMessage: ...)`
3. Validate with `$rules` or a Form Request class passed to `$this->validate()`; save with `$this->submit(action: ..., successMessage: ...)`. Throw `ValidationException::withMessages(['form' => ...])` for whole-form errors and render `<firstlight:callout error-for="form" />`.
4. Gate actions with `$this->allows()`, `$this->denies()` and `$this->authorize()`. Disable, do not hide, destructive rows the Policy denies.
5. Write a Pest test with `Native::visit()` and the Firstlight checks (`assertFieldError`, `assertListItem`, `pressListItem`, `confirmDialog`, `assertFeedbackSent`). Register them once with `FirstlightUI\Testing\ScreenAssertions::register()`.
6. Run the tests. Then run the app on both an iOS simulator and an Android emulator before calling the screen done.

## Never

- Draw your own controls, tab bars, drawers, charts or carousels in Blade.
- Use `custom` top bars or bottom navs, or set `usesNativeChrome()` to false.
- Put non-List-Item children in `firstlight:list`.
- Pass loop indexes to callbacks, or delete from a swipe without confirmation.
- Add `@nativeError`, try/catch around validation, or hard-coded colours.
- Add props a component's reference does not list. Firstlight rejects unknown attributes with an exception; fix the markup instead of suppressing it.

## Theming

Colours and fonts come from `config/native-ui.php` (`php artisan vendor:publish --tag=native-ui-config`). Set accessible `primary`/`on-primary`, `warning`/`on-warning` and other pairs for light and optionally dark. Use `bg-theme-*` and `text-theme-*` classes on surrounding `native:*` layout. See https://firstlightui.dev/docs/getting-started/theming.
