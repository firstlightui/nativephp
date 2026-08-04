# Firstlight UI

Native controls for NativePHP, shaped for each platform. Firstlight exposes a shared EDGE API backed by genuine SwiftUI and Jetpack Compose controls.

[Documentation](https://firstlightui.dev) · [Source](https://github.com/firstlightui/nativephp) · [Email](mailto:team@firstlightui.dev)

## Installation

```bash
composer require firstlightui/nativephp
php artisan native:plugin:register
php artisan native:install --force
```

## Example

```blade
<firstlight:segmented
    :options="['mine' => 'Mine', 'all' => 'All']"
    native:model="queue"
    label="Queue"
/>
```

Firstlight is preparing its first public alpha. Segmented is its first paired iOS and Android control.

Read the [documentation index](docs/index.md) or the governing [Firstlight constitution](Constitution.md).

## Licence

MIT
