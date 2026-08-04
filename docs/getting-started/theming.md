---
title: Theme Firstlight components
description: Configure the semantic NativePHP theme tokens that Firstlight components inherit on iOS and Android.
type: how-to
audience: consumer
sources:
  - Constitution.md
  - src/Components/TextField.php
  - vendor/nativephp/mobile-ui/config/native-ui.php
  - vendor/nativephp/mobile-ui/src/NativeUIServiceProvider.php
  - vendor/nativephp/mobile-ui/src/Theme.php
---

# Theme Firstlight Components

Firstlight components inherit the semantic theme from `nativephp/mobile-ui`. There is no separate Firstlight theme file: configure the host application once, and the same tokens reach the native SwiftUI and Jetpack Compose renderers.

The theme changes colour and typography while each platform retains its native geometry, motion, and interaction.

## Publish the theme configuration

Publish `config/native-ui.php` into the application:

```shell
php artisan vendor:publish --tag=native-ui-config
```

## Set the brand colour pairs

Set both the background token and its `on-*` content token. This example uses the Firstlight showcase palette; replace the values with accessible pairs from your own brand:

```php
'theme' => [
    'light' => [
        'primary' => '#AE1515',
        'on-primary' => '#FFF5EA',
    ],
    'dark' => [
        'primary' => '#ED4E0C',
        'on-primary' => '#110805',
    ],
],
```

The `primary` pair colours filled actions, selected states, and key accents. Surface, outline, destructive, success, accent, and disabled-state colours use their corresponding semantic tokens from the published configuration.

Dark values are optional because NativePHP can derive them from the light theme. Define explicit dark overrides when brand perception or contrast needs a tuned pair. Keep text and icons at a contrast ratio of at least 4.5:1 against their background token.

Theme colours accept CSS hex values, Tailwind palette names such as `violet-600`, and opacity modifiers such as `violet-600/15`.

## Use theme tokens in surrounding native UI

Firstlight controls consume the tokens automatically. Use the same tokens for surrounding `nativephp/mobile-ui` elements with theme-aware utility classes:

```blade
<native:column class="bg-theme-surface border border-theme-outline rounded-2xl p-4">
    <native:text class="text-theme-on-surface text-lg font-bold">
        Account
    </native:text>

    <firstlight:text-field
        label="Email"
        native:model="email"
    />
</native:column>
```

## Apply a runtime brand

For tenant or user-specific branding, merge only the tokens that change from a service provider:

```php
use Native\Mobile\UI\Theme;

public function boot(): void
{
    Theme::merge([
        'light' => [
            'primary' => '#AE1515',
            'on-primary' => '#FFF5EA',
        ],
        'dark' => [
            'primary' => '#ED4E0C',
            'on-primary' => '#110805',
        ],
    ]);
}
```

`Theme::merge()` deep-merges the new values, synchronises the effective Laravel configuration, and publishes the updated tokens to the native renderers.
