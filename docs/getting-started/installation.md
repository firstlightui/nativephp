---
title: Install Firstlight UI
description: Install Firstlight and Laravel Boost, register the NativePHP plugin, and rebuild the native projects.
type: how-to
audience: consumer
sources:
  - composer.json
  - nativephp.json
  - src/FirstlightServiceProvider.php
  - docs/reference/compatibility.md
  - resources/boost/guidelines/core.blade.php
---

# Install Firstlight UI

Firstlight is the app-shell layer for NativePHP. After these steps your app,
and your coding agent, can build lists and detail, search, forms with Laravel
validation, settings, sheets and dialogs, and feedback from genuine SwiftUI and
Jetpack Compose controls.

## Requirements

Start with a NativePHP Mobile 4 application that meets the [current compatibility requirements](../reference/compatibility.md).

Use NativePHP Mobile `^4.7.0` and Mobile UI `^0.9.0`, with iOS 18.2+ or
Android API 29+. Firstlight screens must
extend `FirstlightUI\NativeComponent` for the
[response-revision contract](../reference/compatibility.md#runtime-response-contract).

## Install and register the plugin

Install Firstlight as a normal Composer dependency:

```bash
composer require firstlightui/nativephp
```

Laravel discovers `FirstlightUI\FirstlightServiceProvider` from the package metadata. Register the native plugin explicitly so NativePHP adds the renderers declared in `nativephp.json` to the application:

```bash
php artisan native:plugin:register
```

The command discovers unregistered plugins. If your application disables Laravel package discovery, register `FirstlightUI\FirstlightServiceProvider` through the application's normal provider configuration as well.

## Rebuild the native projects

Native renderer source is compiled into the host application. Regenerate the native projects after installing or updating Firstlight:

```bash
php artisan native:install --force
```

Build and run the application through your normal NativePHP workflow. Firstlight's `<firstlight:...>` tags are collected as native elements only in a native render; an ordinary web render leaves them unchanged.

## Give your coding agent the Firstlight guideline

Firstlight ships a Laravel Boost guideline and two skills. Install Boost so
your coding agent builds screens from the Firstlight recipes:

```bash
composer require laravel/boost --dev
php artisan boost:install
```

If Boost is already installed, run `php artisan boost:update --discover`
instead. See [Build with a coding agent](build-with-a-coding-agent.md) for what
the agent receives.

## Next step

Build the app shell with the
[list and detail recipe](../how-to/build-a-list-detail-flow.md), then add the
[searchable list](../how-to/build-a-searchable-list-screen.md),
[form](../how-to/build-a-form-screen.md), and
[settings](../how-to/build-a-settings-screen.md) screens your app needs. To
learn one control at a time instead, add
[your first Firstlight component](first-component.md).
