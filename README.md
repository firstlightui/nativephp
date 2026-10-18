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

Use `FirstlightUI\NativeComponent` for screens on NativePHP Mobile `^4.6`
and Mobile UI `^0.8`, with official, unpatched runtime binaries. It stamps each
complete nonstreaming response with a process-wide increasing revision so an
otherwise identical response can reach the native renderers. Blade render paths
are automatic; hand-built Element roots must return `$this->response($element)`.
Streaming is disabled and cannot be opted into.

The upstream identical-frame bug [#365](https://github.com/NativePHP/mobile-air/issues/365)
remains, but Firstlight no longer waits for it. Firstlight remains pre-alpha,
pending physical-device, soak/performance, and wider application qualification;
this is not a production-readiness claim. See the
[response contract and limitations](docs/reference/compatibility.md#pre-alpha-runtime-limitation).

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
