# Firstlight UI

Native controls for NativePHP, shaped for each platform. Firstlight exposes a shared EDGE API backed by genuine SwiftUI and Jetpack Compose controls, plus the Laravel contracts mobile screens need: validation, submission, authorization, pagination, and list deletion.

[Documentation](https://firstlightui.dev) · [Components](https://firstlightui.dev/components) · [Source](https://github.com/firstlightui/nativephp) · [Email](mailto:team@firstlightui.dev)

> **Release candidate.** Firstlight is pending physical-device, soak/performance, and wider application qualification. It is not yet a production-readiness claim.

## Requirements

| Dependency | Requirement |
| --- | --- |
| PHP | `^8.4` |
| NativePHP Mobile | `^4.6` |
| NativePHP Mobile UI | `^0.8` |
| iOS / Android targets | iOS 18+ / API 29+ |

See [Compatibility](docs/reference/compatibility.md) for the full table.

## Installation

```bash
composer require "firstlightui/nativephp:^1.0@RC"
php artisan native:plugin:register
php artisan native:install --force
```

Run `php artisan native:install --force` again after every Firstlight update; renderer source is compiled into the host application.

## Example

Screens extend `FirstlightUI\NativeComponent`:

```php
use FirstlightUI\NativeComponent;

class ServerScreen extends NativeComponent
{
    public ?string $tab = 'overview';

    public array $tabs = [
        'overview' => 'Overview',
        'sites' => 'Sites',
        'deploys' => 'Deployments',
    ];
}
```

```blade
<firstlight:tabs native:model="tab" :tabs="$tabs" label="Server sections" />

@if ($tab === 'overview')
    …
@endif
```

Selection is server-authoritative: a tap proposes a stable value, and the value PHP publishes decides what is shown. The same markup renders a native segmented control on iOS and Material 3 tabs on Android.

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

Layout and navigation chrome, such as stacks, top bars, and bottom tab bars, stay in NativePHP Mobile UI.

## Laravel extensions

Concerns on `NativeComponent` connect Laravel to existing native controls:

- [`ValidatesFields`](docs/how-to/validate-fields.md): `validate()`, `validateOnly()`, and Form Requests, with messages on native field error slots.
- [`SubmitsForms`](docs/how-to/submit-forms.md): validate, run an action once, and publish success Feedback.
- [`AuthorizesActions`](docs/how-to/authorize-actions.md): hide, disable, or guard actions with Gates and Policies.
- [`PaginatesLists`](docs/how-to/paginate-lists.md): bind Laravel paginators to List `@refresh` and `@endReached`.
- [`DestroysListItems`](docs/how-to/destroy-list-items.md): authorized, confirmed deletion of List rows by stable key.

Firstlight-owned labels use Laravel's lang files, and Date and Time pickers inherit the application locale and timezone. See [Localize chrome](docs/how-to/localize.md).

Warnings use the theme's `warning` / `on-warning` pair, with amber defaults independent of brand accent. List Item supports semantic icon tones and nullable optional text. See [Theming](docs/getting-started/theming.md) and [List Item](docs/components/list-item.md).

Use `Feedback::fake()` to assert application feedback without native presentation; see [Feedback testing](docs/components/transient-feedback.md#testing). For forms submitted by a separate button, use live text binding until cross-platform blur-before-press ordering is available; see [Text Field](docs/components/text-field.md).

## Response contract

Firstlight stamps each complete screen response with a process-wide increasing revision, so an otherwise identical response still reaches the native renderers. Blade render paths are automatic. Hand-built Element roots must return `$this->response($element)`. Streaming is disabled. See the [response contract and limitations](docs/reference/compatibility.md#runtime-response-contract).

## Accessibility

Every control is a real SwiftUI or Jetpack Compose control, so VoiceOver, TalkBack, Dynamic Type, font scaling, contrast, Reduce Motion, and right-to-left layout work as they do in platform apps. Firstlight adds required accessible names, single focus stops, 44 pt / 48 dp targets, gesture-free alternatives for swipe and context-menu actions, and spoken validation errors. See [Accessibility](docs/concepts/accessibility.md) for what is covered, what is tested, and the known gaps.

## Documentation

Start with the [documentation index](docs/index.md) or [firstlightui.dev](https://firstlightui.dev). Design rules live in the [Firstlight constitution](Constitution.md).

## Licence

MIT
