---
title: Build a settings screen
description: Recipe for a grouped settings screen with native section headers and footers, Switch and Select rows, and save-on-change.
type: how-to
audience: consumer
sources:
  - src/NativeComponent.php
  - src/Elements/SwitchControl.php
  - src/Elements/Select.php
  - src/Elements/ListItem.php
  - vendor/nativephp/mobile-ui/src/Elements/NativeList.php
  - vendor/nativephp/mobile-ui/src/Elements/ListSection.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIListRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/ListRenderer.kt
---

# Build a Settings Screen

This recipe builds a standard settings screen: grouped sections with headers
and footers, switches and pickers that save as soon as they change, and rows
that open other screens.

Use it for preferences the user changes one at a time. Use the
[form recipe](build-a-form-screen.md) instead when several fields must be
validated and saved together with a button.

## How the pieces fit

Settings controls are not List Item rows, so the container is Mobile UI's
`native:list` with `native:list-section`, not `firstlight:list`. Mobile UI
renders that container with a SwiftUI `List` in the inset-grouped style on iOS
and grouped Material sections on Android, and places Firstlight controls inside
it unchanged. There is no settings tag to learn.

## 1. Register the route

```php
use App\NativeComponents\SettingsScreen;
use Illuminate\Support\Facades\Route;

Route::native('/settings', SettingsScreen::class)->name('settings');
```

## 2. Write the screen

Each setting is a public property. `updated{Property}` runs when PHP accepts a
new value, so it is where the value is saved. This example stores values with
Laravel's cache; use your own model or storage instead.

```php
<?php

namespace App\NativeComponents;

use FirstlightUI\NativeComponent;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SettingsScreen extends NativeComponent
{
    public bool $notifications = true;

    public bool $marketing = false;

    public string $digest = 'weekly';

    /** @var array<string, string> */
    public array $digestOptions = [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'never' => 'Never',
    ];

    public function mount(): void
    {
        $this->notifications = Cache::get('settings.notifications', true);
        $this->marketing = Cache::get('settings.marketing', false);
        $this->digest = Cache::get('settings.digest', 'weekly');
    }

    public function updatedNotifications(bool $value): void
    {
        Cache::forever('settings.notifications', $value);
    }

    public function updatedMarketing(bool $value): void
    {
        Cache::forever('settings.marketing', $value);
    }

    public function updatedDigest(string $value): void
    {
        Cache::forever('settings.digest', $value);
    }

    public function openPrivacy(): void
    {
        $this->navigate('/settings/privacy');
    }

    public function navTitle(): string
    {
        return 'Settings';
    }

    public function render(): View
    {
        return view('native.settings');
    }
}
```

## 3. Write the view

Save this as `resources/views/native/settings.blade.php`:

```blade
<native:list class="w-full flex-1">
    <native:list-section header="Notifications" footer="Alerts for replies and mentions.">
        <firstlight:switch label="Push notifications" native:model="notifications" />
        <firstlight:switch label="Product news" native:model="marketing" />
    </native:list-section>

    <native:list-section header="Email" footer="A summary of activity you missed.">
        <firstlight:select label="Digest" :options="$digestOptions" native:model="digest" />
    </native:list-section>

    <native:list-section header="Account">
        <firstlight:list-item
            headline="Privacy"
            trailing-icon="chevron-right"
            trailing-icon-ios="chevron.right"
            trailing-icon-android="chevron_right"
            @press="openPrivacy"
        />
    </native:list-section>
</native:list>
```

## What each platform shows

| Behaviour | iOS | Android |
| --- | --- | --- |
| Grouping | SwiftUI `List` in the inset-grouped style | Grouped Material sections with headers and footers |
| Switches | SwiftUI `Toggle` | Material 3 `Switch` |
| Digest | Native menu picker | Material 3 exposed dropdown |
| Privacy row | List Item row with a chevron | Material list item with a chevron |

## Rules to keep

- Save in `updated{Property}`, not in a Save button. Settings screens apply each
  change immediately.
- Keep a section footer short. It explains the section, not each control.
- Use `firstlight:list` only when every row is a List Item, such as a list of
  records. Settings mix controls, so they use `native:list`.
- Put the screen under a layout that uses native chrome, as described in
  [Build a list and detail flow](build-a-list-detail-flow.md), so the title and
  back navigation come from the platform.
