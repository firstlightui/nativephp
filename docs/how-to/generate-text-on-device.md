---
title: Generate text on the device
description: Recipe for a screen that streams an answer from the phone's own language model with Local AI, and falls back cleanly where the model can't run.
type: how-to
audience: consumer
sources:
  - src/NativeComponent.php
  - src/Elements/Button.php
  - src/Elements/Callout.php
  - src/Elements/TextArea.php
  - vendor/nativephp/mobile/src/Attributes/On.php
  - vendor/nativephp/mobile/src/Edge/NativeComponent.php
  - vendor/nativephp/mobile/src/Testing/Native.php
---

# Generate Text on the Device

This recipe adds a screen that asks the phone's own language model a question
and shows the answer as it streams in. The prompt and the answer never leave
the phone. On a phone without a supported model, the screen says why and the
rest of the app carries on.

## Before you start

The recipe uses
[`firstlightui/local-ai`](https://github.com/firstlightui/local-ai) 1.0, free
and MIT licensed:

```bash
composer require firstlightui/local-ai:^1.0
php artisan native:plugin:register firstlightui/local-ai
```

Rebuild the app with `php artisan native:run` after installing. It needs no
permissions, API keys or environment variables.

| | iOS | Android |
| --- | --- | --- |
| Model | Apple Foundation Models | Gemini Nano, through the ML Kit GenAI Prompt API (a Google beta) |
| Generates on | iOS 26 or later, on a device that supports Apple Intelligence, with it turned on | Devices on Google's Prompt API list, such as a Pixel 9 or a Galaxy S25 or later |
| Installs on | iOS 18 or later | Android 10 (API 29) or later |

The app installs and runs on every version in the last row. Where the model
can't run, Local AI reports `unavailable` with a reason instead.

## How the pieces fit

- `LocalAI::availability()` says whether the model can be used now:
  `available`, `downloadable`, `downloading` or `unavailable`, with a `reason`
  such as `not_enabled` when Apple Intelligence is turned off.
- `LocalAI::generate()` and `LocalAI::chat()` queue a generation and return a
  `Generation` straight away. Its `id` ties together the events that follow.
- The text arrives as `TextDeltaGenerated` events, then one
  `GenerationCompleted`, `GenerationFailed` or `GenerationCancelled`.
- Events reach only the screen that is live when they arrive. After the user
  navigates away and back, read the generation's text again by its `id`.

## 1. Build the screen

```php
<?php

namespace App\NativeComponents;

use FirstlightUI\LocalAI\Events\GenerationCancelled;
use FirstlightUI\LocalAI\Events\GenerationCompleted;
use FirstlightUI\LocalAI\Events\GenerationFailed;
use FirstlightUI\LocalAI\Events\TextDeltaGenerated;
use FirstlightUI\LocalAI\Facades\LocalAI;
use FirstlightUI\LocalAI\LocalAIException;
use FirstlightUI\NativeComponent;
use Illuminate\View\View;
use Native\Mobile\Attributes\On;

class AskScreen extends NativeComponent
{
    public bool $available = false;

    public string $question = '';

    public string $answer = '';

    public ?string $generationId = null;

    public ?string $error = null;

    public function mount(): void
    {
        try {
            $this->available = LocalAI::availability()->isAvailable();
        } catch (LocalAIException) {
            // Off the device, or the plugin isn't registered.
            $this->available = false;
        }
    }

    public function navTitle(): string
    {
        return 'Ask';
    }

    public function ask(): void
    {
        $this->answer = '';
        $this->error = null;

        try {
            $this->generationId = LocalAI::generate(
                $this->question,
                instructions: 'Answer in two or three sentences.',
                options: ['maxTokens' => 256],
            )->id;
        } catch (LocalAIException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function cancel(): void
    {
        if ($this->generationId !== null) {
            LocalAI::cancel($this->generationId);
        }
    }

    #[On(TextDeltaGenerated::class)]
    public function delta(string $id, int $sequence, string $delta): void
    {
        if ($id === $this->generationId) {
            $this->answer .= $delta;
        }
    }

    #[On(GenerationCompleted::class)]
    public function completed(string $id, string $text): void
    {
        if ($id === $this->generationId) {
            // The authoritative text replaces the streamed deltas.
            $this->answer = $text;
            $this->generationId = null;
        }
    }

    #[On(GenerationFailed::class)]
    public function failed(string $id, string $code, string $message, string $text = ''): void
    {
        if ($id === $this->generationId) {
            $this->answer = $text;
            $this->error = $code === 'guardrail'
                ? "That question can't be answered here."
                : "Couldn't finish the answer. {$message}";
            $this->generationId = null;
        }
    }

    #[On(GenerationCancelled::class)]
    public function cancelled(string $id, string $text = ''): void
    {
        if ($id === $this->generationId) {
            $this->answer = $text;
            $this->generationId = null;
        }
    }

    public function render(): View
    {
        return view('native.ask');
    }
}
```

Save the view as `resources/views/native/ask.blade.php`:

```blade
<native:scroll-view class="w-full flex-1 bg-theme-background">
    <native:column class="w-full p-4 gap-4">
        @if (! $available)
            <firstlight:callout message="Answers need a phone with Apple Intelligence or Gemini Nano." tone="info" />
        @endif

        <firstlight:text-area
            label="Question"
            :min-lines="2"
            :disabled="! $available"
            native:model.blur="question"
        />

        @if ($generationId)
            <firstlight:button variant="secondary" @press="cancel">Stop</firstlight:button>
        @else
            <firstlight:button :disabled="! $available || $question === ''" @press="ask">Ask</firstlight:button>
        @endif

        @if ($error)
            <firstlight:callout :message="$error" tone="warning" />
        @endif

        @if ($answer !== '')
            <native:text class="text-base text-theme-on-background">{{ $answer }}</native:text>
        @endif
    </native:column>
</native:scroll-view>
```

`cancel()` is a request: the answer keeps streaming until
`GenerationCancelled` arrives with any partial text. Don't name the action
`stop()`: the screen's own `stop()` ends it when the user leaves, so
overriding it leaves Back doing nothing.

## 2. Offer the download on Android

When Gemini Nano is supported but not yet on the phone, `availability()`
reports `downloadable`. `LocalAI::download()` asks the system for it and
reports `downloading`; progress arrives as `ModelDownloadProgress` events with
a `status` of `started`, `downloading`, `completed` or `failed`. iOS manages
Apple Intelligence itself, so there `download()` only reports availability.

## 3. Catch up after navigation

Events don't queue for a screen that isn't showing. Read the text again when
the screen comes back:

```php
public function onResume(): void
{
    if ($this->generationId !== null) {
        $snapshot = LocalAI::snapshot($this->generationId);
        $this->answer = $snapshot['text'];

        if (in_array($snapshot['status'], ['completed', 'failed', 'cancelled'], true)) {
            $this->generationId = null;
        }
    }
}
```

The runtime keeps the last 32 finished generations in memory. An older `id`,
or any `id` after the app restarts, throws a `LocalAIException` with the code
`unknown_operation`.

## Continue a conversation

`LocalAI::chat()` takes the whole conversation. System messages may only come
first, and the last message must come from the user:

```php
$generation = LocalAI::chat([
    ['role' => 'system', 'content' => 'Be brief.'],
    ['role' => 'user', 'content' => 'Hi'],
    ['role' => 'assistant', 'content' => 'Hello.'],
    ['role' => 'user', 'content' => 'Name a colour.'],
], ['temperature' => 0.7, 'maxTokens' => 128]);
```

The only options are `temperature`, from 0 to 2, and `maxTokens`. Any other
option, an unknown role or an empty message throws `InvalidArgumentException`
before any native call.

## Failures and limits

| Code | What to tell the user |
| --- | --- |
| `unavailable` | The model can't be used on this phone now. Check `availability()`. |
| `busy` | 8 generations are already waiting. Try again shortly. |
| `context_exceeded` | The question and history are too long. Shorten them. |
| `guardrail` | The system's safety checks blocked the question or the answer. |
| `rate_limited` | The system is busy or the app's quota is used up. Try again later. |
| `background_blocked` | Android blocked generation while the app was in the background. |

The plugin's [README](https://github.com/firstlightui/local-ai#readme) lists
every code and availability reason. Generations run one at a time, and queued
ones wait while the app is in the background. Both models take about 4,000
tokens of input.

## Test the screen

Deliver the plugin's events with `emitNative()`:

```php
use App\NativeComponents\AskScreen;
use FirstlightUI\LocalAI\Events\GenerationCompleted;
use FirstlightUI\LocalAI\Events\TextDeltaGenerated;
use Native\Mobile\Testing\Native;

it('streams the answer, then shows the final text', function () {
    Native::test(AskScreen::class)
        ->set('generationId', 'gen-1')
        ->emitNative(TextDeltaGenerated::class, ['id' => 'gen-1', 'sequence' => 1, 'delta' => 'Io, '])
        ->assertSet('answer', 'Io, ')
        ->emitNative(GenerationCompleted::class, ['id' => 'gen-1', 'text' => 'Io, Europa and Ganymede.'])
        ->assertSet('answer', 'Io, Europa and Ganymede.')
        ->assertSet('generationId', null)
        ->assertSee('Io, Europa and Ganymede.');
});

it('ignores text from an earlier question', function () {
    Native::test(AskScreen::class)
        ->set('generationId', 'gen-2')
        ->emitNative(TextDeltaGenerated::class, ['id' => 'gen-1', 'sequence' => 1, 'delta' => 'Old'])
        ->assertSet('answer', '');
});
```

Generation itself needs a device or simulator. The iOS 26 and later Simulator
uses the Mac's own Apple Intelligence model; Android emulators have no model
and report `unavailable`. See [Test screens](test-screens.md) for more of
`Native::test()`.
