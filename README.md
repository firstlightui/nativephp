<a href="https://firstlightui.dev">
  <img src="https://raw.githubusercontent.com/firstlightui/firstlight-public/main/public/firstlight-icon.png" alt="Firstlight UI sunrise logo" width="96" height="96">
</a>

# Firstlight UI

The app-shell layer for NativePHP. `composer require firstlightui/nativephp` plus [Laravel Boost](https://laravel.com/docs/boost) gives you, or your coding agent, what a standard forms app needs: lists and detail, search, forms with Laravel validation, settings, sheets and dialogs, and feedback.

[Documentation](https://firstlightui.dev/docs) · [Recipes](https://firstlightui.dev/docs/how-to/build-a-list-detail-flow) · [Components](https://firstlightui.dev/components) · [llms.txt](https://firstlightui.dev/llms.txt) · [Source](https://github.com/firstlightui/nativephp) · [Email](mailto:team@firstlightui.dev)

## Try the showcase

Explore the native controls and recipes in the [showcase app](https://github.com/firstlightui/showcase).

<a href="https://apps.apple.com/au/app/firstlight-showcase/id6826403090"><img src="https://raw.githubusercontent.com/firstlightui/firstlight-public/main/public/badges/app-store.svg" alt="Download on the App Store" height="40"></a>
<a href="https://play.google.com/store/apps/details?id=dev.firstlightui.showcase"><img src="https://raw.githubusercontent.com/firstlightui/firstlight-public/main/public/badges/google-play.png" alt="Get it on Google Play" height="40"></a>

## What you get

- **Genuine native controls.** Every control is SwiftUI on iOS and Jetpack Compose Material 3 on Android. Nothing is drawn to imitate a platform control. One Blade API becomes the right pattern on each platform: Tabs are a segmented control on iOS and Material 3 tabs on Android, and a Select becomes a searchable picker when its list is long.
- **Laravel on native screens.** Validate with `$rules` or a Form Request, show each message in the field's native error slot, show form-level errors in a Callout, submit once, authorize with Gates and Policies, and paginate lists. Test screens with Pest and scaffold them with `make:firstlight-screen`.
- **Accessibility from real controls.** VoiceOver and TalkBack labels and actions, spoken validation errors with focus on the first invalid field, Dynamic Type and font scaling, right-to-left layout, and Reduce Motion. See [Accessibility](https://firstlightui.dev/docs/concepts/accessibility).
- **Built for coding agents.** A Boost guideline and two skills, `firstlight-init` and `building-firstlight-screens`, steer agents to the recipes, and `llms.txt` and `llms-full.txt` carry the whole reference. See [Build with a coding agent](https://firstlightui.dev/docs/getting-started/build-with-a-coding-agent).

## What Firstlight is not

- **Not navigation chrome.** Stacks, title bars, back navigation, and tab bars come from NativePHP's native chrome (`usesNativeChrome()`), and layout primitives come from NativePHP Mobile UI. Firstlight works inside them and does not replace them.
- **Not a design system.** Controls take their colours and fonts from the NativePHP theme and keep each platform's own look.
- **Not a catalogue of everything.** There are no charts, carousels, or custom-drawn controls. A control is added only when it has an equal native contract on iOS and Android.

## Start with a recipe

Each recipe is a complete screen. Each one runs unchanged in the [showcase app](https://github.com/firstlightui/showcase), whose Pest tests exercise it.

| Recipe | What it builds |
| --- | --- |
| [List and detail flow](https://firstlightui.dev/docs/how-to/build-a-list-detail-flow) | The app shell: native title bars and back navigation, a list, and a pushed detail screen |
| [Searchable list](https://firstlightui.dev/docs/how-to/build-a-searchable-list-screen) | Native search, a paginated List, pull-to-refresh, row navigation, and confirmed swipe deletion |
| [Large list](https://firstlightui.dev/docs/how-to/build-a-large-list-screen) | Thousands of local records in a virtual list that builds only the visible List Item rows |
| [Form screen](https://firstlightui.dev/docs/how-to/build-a-form-screen) | A Form Request, native field errors, a form-level error, a loading Save button, and success Feedback |
| [Settings screen](https://firstlightui.dev/docs/how-to/build-a-settings-screen) | Grouped native sections with Switch and Select rows that save on change |
| [Photo upload](https://firstlightui.dev/docs/how-to/build-a-photo-upload-screen) | A cropped Media field validated with Laravel rules and saved to a model on Save |
| [App lock](https://firstlightui.dev/docs/how-to/build-an-app-lock-screen) | An app-switcher cover, a lock after time in the background, and biometric unlock with App Guard |

## Requirements

| Dependency | Requirement |
| --- | --- |
| PHP | `^8.4` |
| NativePHP Mobile | `^4.7.0` |
| NativePHP Mobile UI | `^0.9.0` |
| iOS / Android targets | iOS 18.2+ / API 29+ |

See [Compatibility](https://firstlightui.dev/docs/reference/compatibility) for the full table.

## Installation

```bash
composer require firstlightui/nativephp
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

On iOS each error appears under its field; on Android it is Material 3 supporting text in the error state. VoiceOver and TalkBack read the errors and move to the first invalid field. The [form recipe](https://firstlightui.dev/docs/how-to/build-a-form-screen) has the complete screen.

## Components

| Group | Components |
| --- | --- |
| Actions | [Button](https://firstlightui.dev/docs/components/button), [Icon Button](https://firstlightui.dev/docs/components/icon-button) |
| Choices | [Checkbox](https://firstlightui.dev/docs/components/checkbox), [Choice Group](https://firstlightui.dev/docs/components/choice-group), [Pill Group](https://firstlightui.dev/docs/components/pill-group), [Segmented](https://firstlightui.dev/docs/components/segmented), [Select](https://firstlightui.dev/docs/components/select), [Slider](https://firstlightui.dev/docs/components/slider), [Stepper](https://firstlightui.dev/docs/components/stepper), [Switch](https://firstlightui.dev/docs/components/switch), [Tabs](https://firstlightui.dev/docs/components/tabs) |
| Text | [Text Field](https://firstlightui.dev/docs/components/text-field), [Text Area](https://firstlightui.dev/docs/components/text-area), [Search Field](https://firstlightui.dev/docs/components/search-field), [Code Editor](https://firstlightui.dev/docs/components/code-editor) |
| Dates and media | [Date Picker](https://firstlightui.dev/docs/components/date-picker), [Time Picker](https://firstlightui.dev/docs/components/time-picker), [Media](https://firstlightui.dev/docs/components/media) |
| Lists | [List](https://firstlightui.dev/docs/components/list), [List Section](https://firstlightui.dev/docs/components/list-section), [List Item](https://firstlightui.dev/docs/components/list-item) |
| Overlays | [Alert Dialog](https://firstlightui.dev/docs/components/alert-dialog), [Confirmation Dialog](https://firstlightui.dev/docs/components/confirmation-dialog), [Modal](https://firstlightui.dev/docs/components/modal), [Bottom Sheet](https://firstlightui.dev/docs/components/bottom-sheet) |
| Status and feedback | [Activity Indicator](https://firstlightui.dev/docs/components/activity-indicator), [Progress](https://firstlightui.dev/docs/components/progress), [Badge](https://firstlightui.dev/docs/components/badge), [Status Label](https://firstlightui.dev/docs/components/status-label), [Callout](https://firstlightui.dev/docs/components/callout), [Transient Feedback](https://firstlightui.dev/docs/components/transient-feedback) |


## Accessibility

Every control is a real SwiftUI or Jetpack Compose control, so VoiceOver, TalkBack, Dynamic Type, Android font scaling, contrast settings, Reduce Motion, and right-to-left layout reach it as they reach the platform's own apps. Firstlight adds what a native control cannot decide alone:

- Icon Button, Search Field, and Progress refuse to render without an accessible name, and fields warn in development without one.
- Rows and fields are one focus stop, and swipe and context-menu actions are also VoiceOver and TalkBack actions.
- A failed submit speaks its validation errors and moves screen-reader focus to the first invalid field.
- Targets Firstlight owns are at least 44 pt on iOS and 48 dp on Android.

This is covered by contract, XCTest, and Robolectric tests, not yet by listening on physical devices. See [Accessibility](https://firstlightui.dev/docs/concepts/accessibility) for what is tested and the known gaps.

## Laravel extensions

Concerns on `NativeComponent` connect Laravel to the native controls:

- [`ValidatesFields`](https://firstlightui.dev/docs/how-to/validate-fields): `validate()`, `validateOnly()`, and Form Requests, with messages on native field error slots.
- [`SubmitsForms`](https://firstlightui.dev/docs/how-to/submit-forms): validate, run an action once, and publish success Feedback.
- [`AuthorizesActions`](https://firstlightui.dev/docs/how-to/authorize-actions): hide, disable, or guard actions with Gates and Policies.
- [`PaginatesLists`](https://firstlightui.dev/docs/how-to/paginate-lists): bind Laravel paginators to List `@refresh` and `@endReached`.
- [`DestroysListItems`](https://firstlightui.dev/docs/how-to/destroy-list-items): authorized, confirmed deletion of List rows by stable key.
- [`ScreenAssertions`](https://firstlightui.dev/docs/how-to/test-screens): Pest checks for field errors, List rows, Confirmation Dialogs, and Feedback on NativePHP's `Native::test()` harness, plus `php artisan make:firstlight-screen` to scaffold a screen and its test.

Firstlight-owned labels use Laravel's lang files, and Date and Time pickers inherit the application locale and timezone. See [Localize chrome](https://firstlightui.dev/docs/how-to/localize).

Warnings use the theme's `warning` / `on-warning` pair, with amber defaults independent of brand accent. List Item supports semantic icon tones and nullable optional text. See [Theming](https://firstlightui.dev/docs/getting-started/theming) and [List Item](https://firstlightui.dev/docs/components/list-item).

Use `Feedback::fake()` to assert application feedback without native presentation; see [Feedback testing](https://firstlightui.dev/docs/components/transient-feedback#testing). For forms submitted by a separate button, use live text binding until cross-platform blur-before-press ordering is available; see [Text Field](https://firstlightui.dev/docs/components/text-field).

## Response contract

Firstlight stamps each complete screen response with a process-wide increasing revision, so an otherwise identical response still reaches the native renderers. Blade render paths are automatic. Hand-built Element roots must return `$this->response($element)`. Streaming is disabled. See the [response contract and limitations](https://firstlightui.dev/docs/reference/compatibility#runtime-response-contract).

## Documentation

Start with a [recipe](https://firstlightui.dev/docs/how-to/build-a-list-detail-flow), the [documentation index](https://firstlightui.dev/docs), or [firstlightui.dev](https://firstlightui.dev). Design rules live in the [Firstlight constitution](https://github.com/firstlightui/nativephp/blob/main/Constitution.md).

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
