---
title: Build an app lock screen
description: Recipe for a lock screen that covers the app in the app switcher, locks it after time in the background, and unlocks with Face ID, Touch ID, or a fingerprint.
type: how-to
audience: consumer
sources:
  - src/NativeComponent.php
  - src/Elements/Button.php
  - src/Elements/Callout.php
  - src/Elements/Metric.php
  - vendor/nativephp/mobile/src/PendingBiometric.php
  - vendor/nativephp/mobile/src/Events/Biometric/Completed.php
  - vendor/nativephp/mobile/src/Edge/NativeComponent.php
---

# Build an App Lock Screen

This recipe locks an app that shows private data. The app opens locked, hides
its content in the app switcher, and locks again when the user comes back after
a minute away. The user unlocks it with Face ID, Touch ID, or a fingerprint.

This recipe runs unchanged in the
[showcase app](https://github.com/firstlightui/showcase), where
[`AppLockRecipeTest`](https://github.com/firstlightui/showcase/blob/main/tests/Feature/Recipes/AppLockRecipeTest.php)
exercises it.

## Before you start

The recipe uses two plugins:

- [`firstlightui/app-guard`](https://github.com/firstlightui/app-guard), free
  and MIT licensed, provides the app-switcher cover, the shield shown on return,
  and the `Foregrounded` event.
- [`nativephp/mobile-biometrics`](https://nativephp.com/plugins/nativephp/mobile-biometrics),
  an official paid NativePHP plugin, shows the system prompt.

```bash
composer require firstlightui/app-guard nativephp/mobile-biometrics
php artisan native:plugin:register firstlightui/app-guard
php artisan native:plugin:register nativephp/mobile-biometrics
```

The `Biometrics` facade and the `Biometric\Completed` event belong to
`nativephp/mobile`, so the screen's PHP and its tests work without the paid
plugin. Without it, `prompt()` returns `false` and the lock screen shows its
failure message.

## How the pieces fit

Three pieces share the work:

- `AppLock` holds whether the app is unlocked. The flag lives in memory, so
  every cold start begins locked. It turns on the app-switcher cover and the
  shield once per PHP process, because native state does not survive the
  process. An app-wide listener hands it every return from the background, so
  the app locks whichever screen is showing.
- `LockedScreen` is the base class for every screen with protected content.
  It sends a locked app to the lock screen when the screen opens, when it is
  shown again after the screen above it closes, and when the app returns behind
  the shield.
- `LockScreen` asks for Face ID, Touch ID, or a fingerprint, and replaces itself
  with the screen the user was on once they pass.

The shield is what keeps the content hidden on return. After `TIMEOUT` seconds
in the background, App Guard covers the app with an opaque view before its first
frame and sends `Foregrounded` with `shielded: true`. A protected screen
replaces itself with the lock screen, which releases the shield when it opens;
the shield's 200 ms fade covers the lock screen's first render. On any other
screen, such as settings, `AppLock` releases the shield itself, and protected
screens lower in the stack lock when they are shown again.

## 1. Register the lock state and routes

`AppLock` must be a singleton, so its flag survives from one screen to the
next, and it listens for every return from the background. In
`AppServiceProvider`:

```php
use App\Security\AppLock;
use FirstlightUI\AppGuard\Events\Foregrounded;
use Illuminate\Support\Facades\Event;

public function register(): void
{
    $this->app->singleton(AppLock::class);
}

public function boot(): void
{
    Event::listen(fn (Foregrounded $event) => app(AppLock::class)->foregrounded($event->shielded));
}
```

Then register the routes:

```php
use App\NativeComponents\LockScreen;
use App\NativeComponents\VaultScreen;
use Illuminate\Support\Facades\Route;

Route::native('/vault', VaultScreen::class)->name('vault');
Route::native('/lock', LockScreen::class)->name('lock');
```

## 2. Write the lock state

`TIMEOUT` is how long the app may stay in the background before a return needs
the lock screen. Store it in your settings instead if users choose it.

```php
<?php

namespace App\Security;

use App\NativeComponents\LockedScreen;
use FirstlightUI\AppGuard\Facades\AppGuard;
use Native\Mobile\Edge\NativeComponent;

/**
 * Whether the app is locked. The flag lives in memory, so every cold start begins locked.
 */
class AppLock
{
    /** Seconds in the background before a return needs the lock screen. */
    public const TIMEOUT = 60;

    private bool $booted = false;

    private bool $unlocked = false;

    /**
     * Native state does not survive process death, so apply it once per PHP process.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;
        AppGuard::setPrivacyCover(true);
        $this->resumeShield();
    }

    /**
     * Runs for every return from the background. A shielded return locks the app. Protected screens
     * swap themselves for the lock screen; on any other screen the shield is released here.
     */
    public function foregrounded(bool $shielded): void
    {
        if (! $shielded) {
            return;
        }

        $this->lock();

        if (! NativeComponent::active() instanceof LockedScreen) {
            AppGuard::releaseShield();
        }
    }

    public function isLocked(): bool
    {
        return ! $this->unlocked;
    }

    public function lock(): void
    {
        $this->unlocked = false;
    }

    public function unlock(): void
    {
        $this->unlocked = true;
    }

    /**
     * Android's passcode screen stops the app, which would otherwise count as a trip to the background.
     */
    public function suspendShield(): void
    {
        AppGuard::shieldAfter(null);
    }

    public function resumeShield(): void
    {
        AppGuard::shieldAfter(self::TIMEOUT);
    }
}
```

Save this as `app/Security/AppLock.php`.

## 3. Write the base for protected screens

Every screen that shows protected content extends `LockedScreen`. It owns
`mount()`, so the lock check cannot be skipped, and gives screens `opened()`
instead. A deep link to a protected screen therefore opens the lock screen
first.

```php
<?php

namespace App\NativeComponents;

use App\Security\AppLock;
use FirstlightUI\AppGuard\Events\Foregrounded;
use FirstlightUI\NativeComponent;
use Native\Mobile\Attributes\On;

/**
 * Base for every screen that shows protected content.
 */
abstract class LockedScreen extends NativeComponent
{
    final public function mount(): void
    {
        $lock = app(AppLock::class);
        $lock->boot();

        if ($lock->isLocked()) {
            $this->showLockScreen();

            return;
        }

        $this->opened();
    }

    /**
     * Runs when the screen opens unlocked. Screens use this instead of mount().
     */
    protected function opened(): void {}

    /**
     * The app may have locked while another screen was on top.
     */
    public function onResume(): void
    {
        if (app(AppLock::class)->isLocked()) {
            $this->showLockScreen();
        }
    }

    #[On(Foregrounded::class)]
    public function appForegrounded(int $secondsAway, bool $shielded): void
    {
        if (! $shielded) {
            return;
        }

        app(AppLock::class)->lock();
        $this->showLockScreen();
    }

    private function showLockScreen(): void
    {
        $this->replace('/lock', ['return' => $this->nativeRouter?->currentUri()]);
    }
}
```

## 4. Write the lock screen

The lock screen tags each prompt with an id and ignores answers for any other
prompt. It suspends the shield while the system prompt is open, and asks again
when the app returns while no prompt is showing.

```php
<?php

namespace App\NativeComponents;

use App\Security\AppLock;
use FirstlightUI\AppGuard\Events\Foregrounded;
use FirstlightUI\AppGuard\Facades\AppGuard;
use FirstlightUI\NativeComponent;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Native\Mobile\Attributes\On;
use Native\Mobile\Edge\Layouts\Builders\NavBarOptions;
use Native\Mobile\Events\Biometric\Completed;
use Native\Mobile\Facades\Biometrics;

class LockScreen extends NativeComponent
{
    protected bool $hidesNavBar = true;

    public bool $prompting = false;

    public bool $failed = false;

    private ?string $promptId = null;

    public function mount(): void
    {
        // The shield fades out over 200 ms, covering this first render.
        AppGuard::releaseShield();

        $this->unlock();
    }

    public function unlock(): void
    {
        $this->failed = false;
        $this->promptId = (string) Str::uuid();
        app(AppLock::class)->suspendShield();

        $this->prompting = Biometrics::prompt()->id($this->promptId)->prompt();

        if (! $this->prompting) {
            $this->finishPrompt(success: false);
        }
    }

    #[On(Completed::class)]
    public function biometricCompleted(bool $success, ?string $id = null): void
    {
        if ($id === null || $id !== $this->promptId) {
            return;
        }

        $this->finishPrompt($success);
    }

    /**
     * AppLock releases the shield on this screen; asking again is this screen's job.
     */
    #[On(Foregrounded::class)]
    public function appForegrounded(int $secondsAway, bool $shielded): void
    {
        if (! $this->prompting) {
            $this->unlock();
        }
    }

    /**
     * Nothing behind the lock may show, so back does nothing.
     */
    public function onBackPressed(): void {}

    public function navigationOptions(): ?NavBarOptions
    {
        return NavBarOptions::make()->back(false);
    }

    public function render(): View
    {
        return view('native.lock');
    }

    private function finishPrompt(bool $success): void
    {
        $this->promptId = null;
        $this->prompting = false;
        app(AppLock::class)->resumeShield();

        if (! $success) {
            $this->failed = true;

            return;
        }

        app(AppLock::class)->unlock();
        $this->replace($this->data('return') ?: '/');
    }
}
```

Save the view as `resources/views/native/lock.blade.php`:

```blade
<native:column class="flex-1 items-center justify-center gap-6 p-8 bg-theme-background">
    <native:icon name="lock" ios="lock.fill" android="lock" class="text-6xl text-theme-primary" />
    <native:text class="text-2xl font-semibold text-theme-on-background">Locked</native:text>

    @if ($failed)
        <firstlight:callout message="Couldn't confirm it's you. Try again." tone="warning" />
    @endif

    <firstlight:button :loading="$prompting" @press="unlock">Unlock</firstlight:button>
</native:column>
```

## 5. Protect a screen

A protected screen extends `LockedScreen` and loads its data in `opened()`, so
nothing protected is loaded while the app is locked.

```php
<?php

namespace App\NativeComponents;

use Illuminate\View\View;

class VaultScreen extends LockedScreen
{
    public string $balance = '';

    protected function opened(): void
    {
        $this->balance = '$12,480.00';
    }

    public function navTitle(): string
    {
        return 'Vault';
    }

    public function render(): View
    {
        return view('native.vault');
    }
}
```

```blade
<native:column class="w-full flex-1 gap-4 p-4 bg-theme-background">
    <firstlight:metric label="Balance" :value="$balance" />
</native:column>
```

## 6. Test it

Fake the bridge functions the screens call, then emit the native events. See
[Test screens](test-screens.md) for the harness.

```php
Native::fakeBridge()
    ->respondTo('AppGuard.SetPrivacyCover', [])
    ->respondTo('AppGuard.SetShieldTimeout', [])
    ->respondTo('AppGuard.ReleaseShield', [])
    ->respondTo('Biometric.Prompt', []);

$vault = Native::visit('/vault')->assertReplacedWith('/lock');
$lock = $vault->follow()->assertScreen(LockScreen::class);

$id = $lock->bridge()->callsTo('Biometric.Prompt')[0]['params']['id'];

$lock->emitNative(Completed::class, ['success' => true, 'id' => $id])
    ->assertReplacedWith('/vault');
```

## What each platform shows

| Behaviour | iOS | Android |
| --- | --- | --- |
| App switcher | A blur over the app while it is inactive | `FLAG_SECURE`, which also blocks screenshots and screen recording |
| Return after `TIMEOUT` | An opaque shield over every window before the first frame | An opaque shield over the window before the first frame |
| Unlock prompt | Face ID or Touch ID, falling back to the device passcode | Fingerprint or face unlock with a Cancel button. There is no passcode fallback |
| Time away | Measured natively on a clock that keeps running while the device sleeps | The same |

## Rules to keep

- Give users a way out of the lock screen, such as signing out and removing the
  app's data. On Android, a device with no fingerprint or face enrolled can
  never pass the prompt.
- Extend `LockedScreen` from every screen with protected content. A screen that
  does not extend it stays visible after a shielded return, because `AppLock`
  releases the shield over it.
- Load protected data in `opened()`, never in `mount()` or a property default.
- Keep the app-wide `Foregrounded` listener. Without it, a shielded return to a
  screen that is not protected leaves the shield covering the app.
- Tell users that the app blocks screenshots on Android, because the app-switcher
  cover uses `FLAG_SECURE`.
- Keep the unlocked flag in memory. Persisting it would let a cold start skip
  the lock.
