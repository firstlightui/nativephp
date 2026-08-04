---
title: Install Firstlight UI
description: Install Firstlight, register its NativePHP plugin, and rebuild the native projects.
type: how-to
audience: consumer
sources:
  - composer.json
  - nativephp.json
  - src/FirstlightServiceProvider.php
---

# Install Firstlight UI

## Requirements

Start with a NativePHP Mobile 4 application that meets the [current compatibility requirements](../reference/compatibility.md).

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

## Next step

Build [your first Firstlight component](first-component.md).
