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

Firstlight is not currently release-ready on the latest supported NativePHP
Mobile constraint (`^4.2`). Its package API works against 4.2.0, but the
bundled PHP Element Runtime can suppress a byte-identical tree publication
before iOS or Android receives it. Stateful controls may then fail to reconcile
a rejected or unchanged PHP response.

NativePHP Mobile 4.1.0 added Android equal-tree publication revisions
([#280](https://github.com/NativePHP/mobile-air/pull/280)); that is necessary
but not sufficient. The remaining cross-platform gap is tracked upstream as
[#365](https://github.com/NativePHP/mobile-air/issues/365).

The public-alpha release gate is waiting for an official NativePHP release with
a content-independent publication acknowledgement in the bundled PHP runtime.
See [Compatibility](docs/reference/compatibility.md#pre-alpha-runtime-limitation)
for the affected behaviour and upstream tracking.

## Example

```blade
<firstlight:segmented
    :options="['mine' => 'Mine', 'all' => 'All']"
    native:model="queue"
    label="Queue"
/>
```

Read the [documentation index](docs/index.md) or the governing [Firstlight constitution](Constitution.md).

## Licence

MIT
