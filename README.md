# Firstlight UI

Native controls for NativePHP, shaped for each platform. Firstlight exposes a shared EDGE API backed by genuine SwiftUI and Jetpack Compose controls.

[Documentation](https://firstlightui.dev) · [Source](https://github.com/firstlightui/nativephp) · [Email](mailto:team@firstlightui.dev)

## Installation

```bash
composer require firstlightui/nativephp
php artisan native:plugin:register
php artisan native:install --force
```

## Pre-alpha NativePHP requirement

Firstlight is not currently release-ready on NativePHP Mobile 4.0.1. Its
package API works against 4.0.1, but the bundled PHP Element Runtime can
suppress a byte-identical tree publication before iOS or Android receives it.
Stateful controls may then fail to reconcile a rejected or unchanged PHP
response.

Firstlight development temporarily pins a mobile-air development branch and
uses a guarded Android binary proof in the showcase. That patched binary must
not ship. The public-alpha release gate is waiting for an official NativePHP
release with a content-independent publication acknowledgement. See
[Compatibility](docs/reference/compatibility.md#pre-alpha-runtime-limitation)
for the affected behaviour and upstream tracking.

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
