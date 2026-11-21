---
title: Scan documents and read their text
description: Recipe for a screen that scans paper documents with the system document scanner and reads their text on the device with Local OCR.
type: how-to
audience: consumer
sources:
  - src/NativeComponent.php
  - src/Elements/Button.php
  - src/Elements/Callout.php
  - vendor/nativephp/mobile/src/Attributes/On.php
  - vendor/nativephp/mobile/src/Edge/NativeComponent.php
  - vendor/nativephp/mobile/src/Testing/Native.php
---

# Scan Documents and Read Their Text

This recipe adds a screen that scans a paper document and shows its text. The
user taps Scan, the system document scanner opens, and once they have scanned
their pages the screen reads the text and shows it. The images and the text
never leave the phone.

## Before you start

The recipe uses
[`firstlightui/local-ocr`](https://github.com/firstlightui/local-ocr) 1.0,
free and MIT licensed:

```bash
composer require firstlightui/local-ocr:^1.0
php artisan native:plugin:register firstlightui/local-ocr
```

Rebuild the app with `php artisan native:run` after installing.

| | iOS | Android |
| --- | --- | --- |
| Scanner | VisionKit's document camera | ML Kit Document Scanner, which runs in Google Play services |
| Text recognition | Apple Vision, in many languages | ML Kit Text Recognition, Latin-script text only |
| Needs | iOS 18 or later, on a real device: the scanner doesn't run in the Simulator | Android 8 (API 26) or later, with Google Play services |
| Permissions | Camera, which the plugin adds | None |

The plugin's camera purpose string is generic. Word it for your app in
`config/nativephp.php`, which overrides plugins:

```php
'permissions' => [
    'NSCameraUsageDescription' => 'Scan letters so their text can be read on this device.',
],
```

## How the pieces fit

- `LocalOcr::capture()` opens the scanner. The user scans as many pages as they
  need, and the pages are saved as JPEGs in the app's private temporary storage.
- `LocalOcr::recognize($pages)` reads the pages and returns their text, with
  blocks, lines, words, confidence scores and bounding boxes for each page.
- `LocalOcr::discard($pages)` deletes the page images once you are done with
  them.

Capture and recognition return straight away. Their outcome arrives later as an
event carrying the request's `id`, which the screen receives with `#[On]`.

## 1. Build the scan screen

```php
<?php

namespace App\NativeComponents;

use FirstlightUI\LocalOcr\Events\CaptureCancelled;
use FirstlightUI\LocalOcr\Events\CaptureFailed;
use FirstlightUI\LocalOcr\Events\PagesCaptured;
use FirstlightUI\LocalOcr\Events\TextRecognitionFailed;
use FirstlightUI\LocalOcr\Events\TextRecognized;
use FirstlightUI\LocalOcr\Facades\LocalOcr;
use FirstlightUI\NativeComponent;
use Illuminate\View\View;
use Native\Mobile\Attributes\On;

class ScanScreen extends NativeComponent
{
    public bool $canScan = false;

    public bool $working = false;

    public string $text = '';

    public ?string $error = null;

    public ?string $requestId = null;

    public function mount(): void
    {
        // null off the device, so the button stays disabled in a browser.
        $this->canScan = LocalOcr::capabilities()['capture']['available'] ?? false;
    }

    public function navTitle(): string
    {
        return 'Scan';
    }

    public function scan(): void
    {
        $this->error = null;
        $this->working = true;
        $this->requestId = LocalOcr::capture()->getId();
    }

    #[On(PagesCaptured::class)]
    public function pagesCaptured(string $id, array $pages): void
    {
        if ($id !== $this->requestId) {
            return;
        }

        $this->requestId = LocalOcr::recognize($pages)->getId();
    }

    #[On(TextRecognized::class)]
    public function textRecognized(string $id, array $result): void
    {
        if ($id !== $this->requestId) {
            return;
        }

        $this->text = $result['text'];
        $this->finish();

        LocalOcr::discard(array_column($result['pages'], 'path'));
    }

    #[On(CaptureCancelled::class)]
    public function captureCancelled(string $id): void
    {
        if ($id === $this->requestId) {
            $this->finish();
        }
    }

    #[On(CaptureFailed::class)]
    #[On(TextRecognitionFailed::class)]
    public function failed(string $id, string $code, string $message): void
    {
        if ($id === $this->requestId) {
            $this->error = "Couldn't read the document. {$message}";
            $this->finish();
        }
    }

    public function render(): View
    {
        return view('native.scan');
    }

    private function finish(): void
    {
        $this->working = false;
        $this->requestId = null;
    }
}
```

The builder from `capture()` or `recognize()` starts its request when it goes
out of scope, so the screen only keeps its `id`. Matching on the `id` keeps a
late result from an earlier scan from replacing the current one.

Save the view as `resources/views/native/scan.blade.php`:

```blade
<native:scroll-view class="w-full flex-1 bg-theme-background">
    <native:column class="w-full p-4 gap-4">
        @if (! $canScan)
            <firstlight:callout message="Scanning needs a device with a camera and, on Android, Google Play services." tone="info" />
        @endif

        @if ($error)
            <firstlight:callout :message="$error" tone="warning" />
        @endif

        <firstlight:button :loading="$working" :disabled="! $canScan" @press="scan">Scan a document</firstlight:button>

        @if ($text !== '')
            <native:text class="text-base text-theme-on-background">{{ $text }}</native:text>
        @endif
    </native:column>
</native:scroll-view>
```

## 2. Use the structure, not just the text

`$result['text']` joins every page's text with blank lines. Each entry of
`$result['pages']` also has its `blocks`, their `lines` and `words`, each with a
`confidence` from 0 to 1 and a `boundingBox` normalised to the upright page,
origin top-left. Use them to pick out a field by where it sits on the page, or
to ignore low-confidence lines.

Two platform differences matter when you do:

- iOS scores lines only, so each word repeats its line's confidence. Android
  scores words.
- iOS makes each line its own block. Android groups lines into blocks.

Recognition is the same API on both platforms. On iOS you can pass
`->languages(['en-AU'])` to `recognize()`, or `->fast()` to trade accuracy for
speed; in fast mode every line reports a confidence of 0.5, so don't filter on
it there.

## 3. Clean up the pages

Captured pages stay until you call `discard()`. The screen above discards them
once their text is read. Anything left over, for example because the app closed
between capture and recognition, is deleted on the next capture after 24 hours,
and iOS also clears its temporary directory when it needs space.

`discard()` only deletes files the plugin captured. If you need to keep a page,
copy it somewhere else first.

## Failures

| Event | Codes |
| --- | --- |
| `CaptureFailed` | `INVALID_PARAMETERS`, `CAPTURE_UNAVAILABLE`, `CAPTURE_FAILED`, `SAVE_FAILED` |
| `TextRecognitionFailed` | `INVALID_PARAMETERS`, `FILE_NOT_FOUND`, `DECODE_FAILED`, `RECOGNITION_FAILED` |

A failure on any page fails the whole recognition, and the message names the
page. If Android ends the app while the scanner is open, which can happen on a
device that is low on memory, that capture's event never arrives; the next scan
starts fresh.

## Test the screen

Deliver the plugin's events with `emitNative()` and check what the screen
shows:

```php
use App\NativeComponents\ScanScreen;
use FirstlightUI\LocalOcr\Events\TextRecognized;
use Native\Mobile\Testing\Native;

it('shows the text it read', function () {
    Native::test(ScanScreen::class)
        ->set('requestId', 'scan-1')
        ->set('working', true)
        ->emitNative(TextRecognized::class, [
            'id' => 'scan-1',
            'result' => ['text' => 'Ward 4B', 'pages' => []],
        ])
        ->assertSet('text', 'Ward 4B')
        ->assertSet('working', false)
        ->assertSee('Ward 4B');
});

it('ignores a result for an earlier scan', function () {
    Native::test(ScanScreen::class)
        ->set('requestId', 'scan-2')
        ->emitNative(TextRecognized::class, [
            'id' => 'scan-1',
            'result' => ['text' => 'Old page', 'pages' => []],
        ])
        ->assertSet('text', '')
        ->assertDontSee('Old page');
});
```

See [Test screens](test-screens.md) for more of `Native::test()`.

The plugin's
[README](https://github.com/firstlightui/local-ocr#readme) has the full
capability report, the result contract, and the JavaScript library for
Inertia apps.
