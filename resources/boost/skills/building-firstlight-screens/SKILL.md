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
| Shows thousands of local records | [Large list](https://firstlightui.dev/docs/how-to/build-a-large-list-screen) |
| Validates several values and saves with a button | [Form screen](https://firstlightui.dev/docs/how-to/build-a-form-screen) |
| Changes preferences one at a time | [Settings screen](https://firstlightui.dev/docs/how-to/build-a-settings-screen) |
| Uploads a photo or document | [Photo upload](https://firstlightui.dev/docs/how-to/build-a-photo-upload-screen) |
| Locks private data behind Face ID or a fingerprint | [App lock](https://firstlightui.dev/docs/how-to/build-an-app-lock-screen) |
| Scans paper documents and reads their text | [Scan documents](https://firstlightui.dev/docs/how-to/scan-documents) |
| Drafts, summarises or answers with on-device AI | [Generate text on the device](https://firstlightui.dev/docs/how-to/generate-text-on-device) |
| Must only open for a signed-in or unlocked user | [Guard screens](https://firstlightui.dev/docs/how-to/guard-screens) |

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
   - Order: `reorder-list` (drag handles, optional sections, locked rows; PHP decides each move)
   - Rows: `list`, `list-section`, `list-item` (with `navigates`, `trailing-actions`, `leading-actions`, `context-menu`)
   - Status: `status-label`, `badge`, `metric`, `progress`, `activity-indicator`, `callout`
   - Overlays: `confirmation-dialog`, `alert-dialog`, `bottom-sheet`, `modal`
   - Outcomes: `Feedback::success('Saved')->send()` or `submit(successMessage: ...)`. Feedback, validation and the controls already play each platform's haptics; set `haptic="keypress"` only on keypad buttons, and use `Haptics::success()` or `error()` for an outcome with no Feedback (https://firstlightui.dev/docs/how-to/play-haptics)
3. Validate with `$rules` or a Form Request class passed to `$this->validate()`; save with `$this->submit(action: ..., successMessage: ...)`. Throw `ValidationException::withMessages(['form' => ...])` for whole-form errors and render `<firstlight:callout error-for="form" />`.
4. Gate actions with `$this->allows()`, `$this->denies()` and `$this->authorize()`. Disable, do not hide, destructive rows the Policy denies.
5. Write a Pest test with `Native::visit()` and the Firstlight checks (`assertFieldError`, `assertListItem`, `pressListItem`, `confirmDialog`, `assertFeedbackSent`). Register them once with `FirstlightUI\Testing\ScreenAssertions::register()`.
6. Run the tests. Then run the app on both an iOS simulator and an Android emulator before calling the screen done.

## Platform conventions

Firstlight's defaults follow Apple's Human Interface Guidelines (https://developer.apple.com/design/human-interface-guidelines/) and Material 3 (https://m3.material.io/). Keep screens inside them:

- Targets: controls already give at least 44 × 44 pt on iOS and 48 × 48 dp on Android. Do not shrink them with fixed sizes (https://developer.apple.com/design/human-interface-guidelines/accessibility, https://m3.material.io/foundations/designing/structure).
- Tabs: use a tab bar for three to five top-level destinations; with two, use a `segmented` control on one screen (https://m3.material.io/components/navigation-bar/guidelines, https://developer.apple.com/design/human-interface-guidelines/tab-bars).
- Search: put a screen's main search in the nav bar. Return `NavBarOptions::make()->searchBar(placeholder: ..., onQuery: 'search')` from the screen's `navigationOptions()` and reload the list in `search(string $query)`. Use `search-field` only for filters inside content (https://developer.apple.com/design/human-interface-guidelines/search-fields, https://m3.material.io/components/search/overview).
- Rows that open another screen: set `navigates` on the List Item. iOS draws its disclosure chevron and Android draws none, as Material lists show none. Never author a `chevron-right` trailing icon (https://developer.apple.com/design/human-interface-guidelines/lists-and-tables, https://m3.material.io/components/lists/guidelines).
- Icon buttons: leave `variant` unset for ordinary actions, which draws a plain icon. Use `primary` only for a screen's one main icon action (https://m3.material.io/components/icon-buttons/guidelines).
- Sheets: give a `bottom-sheet` a `title` instead of authoring a heading row. iOS draws it in the sheet's own bar with the system close button; Android draws heading text under the drag handle and no close button (https://developer.apple.com/design/human-interface-guidelines/sheets, https://m3.material.io/components/bottom-sheets/guidelines).
- Empty and no-results states: centre a short headline with a hint, and an action if one helps, instead of a lone line of text (https://developer.apple.com/design/human-interface-guidelines/searching).
- Colour: keep `primary` visibly different from `destructive`, so errors never look like the brand colour (https://m3.material.io/styles/color/roles).
- Text: let Dynamic Type and Android font scale grow text; do not fix the height of rows that hold text.

## Never

- Draw your own controls, tab bars, drawers, charts or carousels in Blade.
- Use `custom` top bars or bottom navs, or set `usesNativeChrome()` to false.
- Put non-List-Item children in `firstlight:list`.
- Gate who may open a screen in `mount()`; put the check in route middleware.
- Pass loop indexes to callbacks, or delete from a swipe without confirmation.
- Add `@nativeError`, try/catch around validation, or hard-coded colours.
- Call `Device::vibrate()` for feedback, or set `haptic` on ordinary buttons.
- Set `glass` on buttons in ordinary content, or use the Mobile UI `glass` class. Glass is for actions floating over a photo or map; sheets, Feedback and segmented controls already take it on iOS 26 and later.
- Add props a component's reference does not list. Firstlight rejects unknown attributes with an exception; fix the markup instead of suppressing it.

## Theming

Colours and fonts come from `config/native-ui.php` (`php artisan vendor:publish --tag=native-ui-config`). Set accessible `primary`/`on-primary`, `warning`/`on-warning` and other pairs for light and optionally dark. Use `bg-theme-*` and `text-theme-*` classes on surrounding `native:*` layout. See https://firstlightui.dev/docs/getting-started/theming.
