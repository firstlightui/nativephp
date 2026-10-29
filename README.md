<a href="https://firstlightui.dev">
  <img src="https://raw.githubusercontent.com/firstlightui/firstlight-public/main/public/firstlight-icon.png" alt="Firstlight UI sunrise logo" width="96" height="96">
</a>

# Firstlight UI

The app-shell layer for NativePHP. `composer require firstlightui/nativephp` plus [Laravel Boost](https://laravel.com/docs/boost) gives you, or your coding agent, what a standard forms app needs: lists and detail, search, forms with Laravel validation, settings, sheets and dialogs, and feedback.

[Documentation](https://firstlightui.dev/docs) · [Recipes](docs/how-to/build-a-list-detail-flow.md) · [Components](https://firstlightui.dev/components) · [llms.txt](https://firstlightui.dev/llms.txt) · [Source](https://github.com/firstlightui/nativephp) · [Email](mailto:team@firstlightui.dev)

> **Release candidate (`v1.0.0-RC6`).** Physical-device, spoken screen-reader, soak/performance, and wider application qualification are still outstanding. This is not yet a production-readiness claim.

## Try the showcase

Explore the native controls and recipes in the [showcase app](https://github.com/firstlightui/showcase).

- **iOS:** [Join the showcase beta on TestFlight](https://testflight.apple.com/join/4CCrA7JB).
- **Android:** Coming soon.

## What you get

- **Genuine native controls.** Every control is SwiftUI on iOS and Jetpack Compose Material 3 on Android. Nothing is drawn to imitate a platform control. One Blade API becomes the right pattern on each platform: Tabs are a segmented control on iOS and Material 3 tabs on Android, and a Select becomes a searchable picker when its list is long.
- **Laravel on native screens.** Validate with `$rules` or a Form Request, show each message in the field's native error slot, show form-level errors in a Callout, submit once, authorize with Gates and Policies, and paginate lists. Test screens with Pest and scaffold them with `make:firstlight-screen`.
- **Accessibility from real controls.** VoiceOver and TalkBack labels and actions, spoken validation errors with focus on the first invalid field, Dynamic Type and font scaling, right-to-left layout, and Reduce Motion. See [Accessibility](#accessibility).
- **Built for coding agents.** A Boost guideline and two skills, `firstlight-init` and `building-firstlight-screens`, steer agents to the recipes, and `llms.txt` and `llms-full.txt` carry the whole reference. See [Build with a coding agent](docs/getting-started/build-with-a-coding-agent.md).

## What Firstlight is not

- **Not navigation chrome.** Stacks, title bars, back navigation, and tab bars come from NativePHP's native chrome (`usesNativeChrome()`), and layout primitives come from NativePHP Mobile UI. Firstlight works inside them and does not replace them.
- **Not a design system.** Controls take their colours and fonts from the NativePHP theme and keep each platform's own look.
- **Not a catalogue of everything.** There are no charts, carousels, or custom-drawn controls. A control is added only when it has an equal native contract on iOS and Android.

## Start with a recipe

Each recipe is a complete screen. Each one runs unchanged in the [showcase app](https://github.com/firstlightui/showcase), whose Pest tests exercise it.

| Recipe | What it builds |
| --- | --- |
| [List and detail flow](docs/how-to/build-a-list-detail-flow.md) | The app shell: native title bars and back navigation, a list, and a pushed detail screen |
| [Searchable list](docs/how-to/build-a-searchable-list-screen.md) | Native search, a paginated List, pull-to-refresh, row navigation, and confirmed swipe deletion |
| [Large list](docs/how-to/build-a-large-list-screen.md) | Thousands of local records in a virtual list that builds only the visible List Item rows |
| [Form screen](docs/how-to/build-a-form-screen.md) | A Form Request, native field errors, a form-level error, a loading Save button, and success Feedback |
| [Settings screen](docs/how-to/build-a-settings-screen.md) | Grouped native sections with Switch and Select rows that save on change |
| [Photo upload](docs/how-to/build-a-photo-upload-screen.md) | A cropped Media field validated with Laravel rules and saved to a model on Save |

## Requirements

| Dependency | Requirement |
| --- | --- |
| PHP | `^8.4` |
| NativePHP Mobile | `^4.6.1` |
| NativePHP Mobile UI | `^0.8` |
| iOS / Android targets | iOS 18+ / API 29+ |

See [Compatibility](docs/reference/compatibility.md) for the full table.

## Installation

```bash
composer require "firstlightui/nativephp:^1.0@RC"
php artisan native:plugin:register
php artisan native:install --force
```

Then give your coding agent the Firstlight guideline and skills:

```bash
composer require laravel/boost --dev
php artisan boost:install
```

Run `php artisan native:install --force` again after every Firstlight update; renderer source is compiled into the host application.

## Example

A form screen validates with a Form Request and saves once. The fields show their own errors:

```php
use App\Http\Requests\StorePostRequest;
use App\Models\Post;
use FirstlightUI\NativeComponent;

class CreatePostScreen extends NativeComponent
{
    public string $title = '';

    public string $body = '';

    public function save(): void
    {
        $this->submit(
            action: function (): void {
                $this->validate(StorePostRequest::class);
                Post::create(['title' => $this->title, 'body' => $this->body]);
            },
            successMessage: 'Post created',
            validate: false,
        );
    }
}
```

```blade
<firstlight:callout error-for="form" />
<firstlight:text-field label="Title" native:model="title" required />
<firstlight:text-area label="Body" native:model="body" required />
<firstlight:button variant="primary" :loading="$submitting" @press="save">
    Save post
</firstlight:button>
```

On iOS each error appears under its field; on Android it is Material 3 supporting text in the error state. VoiceOver and TalkBack read the errors and move to the first invalid field. The [form recipe](docs/how-to/build-a-form-screen.md) has the complete screen.

## Components

| Group | Components |
| --- | --- |
| Actions | [Button](docs/components/button.md), [Icon Button](docs/components/icon-button.md) |
| Choices | [Checkbox](docs/components/checkbox.md), [Choice Group](docs/components/choice-group.md), [Pill Group](docs/components/pill-group.md), [Segmented](docs/components/segmented.md), [Select](docs/components/select.md), [Slider](docs/components/slider.md), [Stepper](docs/components/stepper.md), [Switch](docs/components/switch.md), [Tabs](docs/components/tabs.md) |
| Text | [Text Field](docs/components/text-field.md), [Text Area](docs/components/text-area.md), [Search Field](docs/components/search-field.md), [Code Editor](docs/components/code-editor.md) |
| Dates and media | [Date Picker](docs/components/date-picker.md), [Time Picker](docs/components/time-picker.md), [Media](docs/components/media.md) |
| Lists | [List](docs/components/list.md), [List Section](docs/components/list-section.md), [List Item](docs/components/list-item.md) |
| Overlays | [Alert Dialog](docs/components/alert-dialog.md), [Confirmation Dialog](docs/components/confirmation-dialog.md), [Modal](docs/components/modal.md), [Bottom Sheet](docs/components/bottom-sheet.md) |
| Status and feedback | [Activity Indicator](docs/components/activity-indicator.md), [Progress](docs/components/progress.md), [Badge](docs/components/badge.md), [Status Label](docs/components/status-label.md), [Callout](docs/components/callout.md), [Transient Feedback](docs/components/transient-feedback.md) |


## Accessibility

Every control is a real SwiftUI or Jetpack Compose control, so VoiceOver, TalkBack, Dynamic Type, Android font scaling, contrast settings, Reduce Motion, and right-to-left layout reach it as they reach the platform's own apps. Firstlight adds what a native control cannot decide alone:

- Icon Button, Search Field, and Progress refuse to render without an accessible name, and fields warn in development without one.
- Rows and fields are one focus stop, and swipe and context-menu actions are also VoiceOver and TalkBack actions.
- A failed submit speaks its validation errors and moves screen-reader focus to the first invalid field.
- Targets Firstlight owns are at least 44 pt on iOS and 48 dp on Android.

This is covered by contract, XCTest, and Robolectric tests, not yet by listening on physical devices. See [Accessibility](docs/concepts/accessibility.md) for what is tested and the known gaps.

## Laravel extensions

Concerns on `NativeComponent` connect Laravel to the native controls:

- [`ValidatesFields`](docs/how-to/validate-fields.md): `validate()`, `validateOnly()`, and Form Requests, with messages on native field error slots.
- [`SubmitsForms`](docs/how-to/submit-forms.md): validate, run an action once, and publish success Feedback.
- [`AuthorizesActions`](docs/how-to/authorize-actions.md): hide, disable, or guard actions with Gates and Policies.
- [`PaginatesLists`](docs/how-to/paginate-lists.md): bind Laravel paginators to List `@refresh` and `@endReached`.
- [`DestroysListItems`](docs/how-to/destroy-list-items.md): authorized, confirmed deletion of List rows by stable key.
- [`ScreenAssertions`](docs/how-to/test-screens.md): Pest checks for field errors, List rows, Confirmation Dialogs, and Feedback on NativePHP's `Native::test()` harness, plus `php artisan make:firstlight-screen` to scaffold a screen and its test.

Firstlight-owned labels use Laravel's lang files, and Date and Time pickers inherit the application locale and timezone. See [Localize chrome](docs/how-to/localize.md).

Warnings use the theme's `warning` / `on-warning` pair, with amber defaults independent of brand accent. List Item supports semantic icon tones and nullable optional text. See [Theming](docs/getting-started/theming.md) and [List Item](docs/components/list-item.md).

Use `Feedback::fake()` to assert application feedback without native presentation; see [Feedback testing](docs/components/transient-feedback.md#testing). For forms submitted by a separate button, use live text binding until cross-platform blur-before-press ordering is available; see [Text Field](docs/components/text-field.md).

## Response contract

Firstlight stamps each complete screen response with a process-wide increasing revision, so an otherwise identical response still reaches the native renderers. Blade render paths are automatic. Hand-built Element roots must return `$this->response($element)`. Streaming is disabled. See the [response contract and limitations](docs/reference/compatibility.md#runtime-response-contract).

## Documentation

Start with a [recipe](docs/how-to/build-a-list-detail-flow.md), the [documentation index](docs/index.md), or [firstlightui.dev](https://firstlightui.dev). Design rules live in the [Firstlight constitution](Constitution.md).

## About Firstlight UI

[Firstlight UI](https://firstlightui.dev) builds open-source tools for Laravel developers creating native iOS and Android apps. Our focus is everyday application screens: bringing familiar Laravel validation, authorization, and testing to genuine platform controls, with accessibility and coding-agent guidance built into the package.

The project spans three repositories:

- **[NativePHP package](https://github.com/firstlightui/nativephp)** — the Blade API, Laravel extensions, SwiftUI and Jetpack Compose renderers, and documentation.
- **[Showcase app](https://github.com/firstlightui/showcase)** — runnable examples and recipes, with Pest tests that exercise the screens.
- **[Public website](https://github.com/firstlightui/firstlight-public)** — the [documentation](https://firstlightui.dev/docs) and [component gallery](https://firstlightui.dev/components), including screenshots from both platforms.

Firstlight builds on [NativePHP](https://nativephp.com) and the SuperNative foundation created by [Shane Rosenthal](https://github.com/shanerbaner82) and [Simon Hamp](https://github.com/simonhamp). NativePHP supplies navigation chrome and Mobile UI supplies layout; Firstlight adds the controls and Laravel screen behaviour within them.

For bugs and feature requests, [open an issue](https://github.com/firstlightui/nativephp/issues). For other enquiries, email [team@firstlightui.dev](mailto:team@firstlightui.dev).

## Licence

MIT
