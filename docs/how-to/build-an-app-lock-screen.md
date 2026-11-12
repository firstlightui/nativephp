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

This recipe runs in the
[showcase app](https://github.com/firstlightui/showcase), where
[`AppLockRecipeTest`](https://github.com/firstlightui/showcase/blob/main/tests/Feature/Recipes/AppLockRecipeTest.php)
exercises it.

## Before you start

The recipe uses two plugins:

- [`firstlightui/app-guard`](https://github.com/firstlightui/app-guard) 1.0,
  free and MIT licensed, provides the lock state, the route middleware, the
  app-switcher cover, and the shield shown on return.
- [`nativephp/mobile-biometrics`](https://nativephp.com/plugins/nativephp/mobile-biometrics),
  an official paid NativePHP plugin, shows the system prompt.

```bash
composer require firstlightui/app-guard:^1.0 nativephp/mobile-biometrics
php artisan native:plugin:register firstlightui/app-guard
php artisan native:plugin:register nativephp/mobile-biometrics
```

The `Biometrics` facade and the `Biometric\Completed` event belong to
`nativephp/mobile`, so the screen's PHP and its tests work without the paid
plugin. Without it, `prompt()` returns `false` and the lock screen shows its
failure message.

## How the pieces fit

App Guard owns the lock; your app owns the lock screen:

- `FirstlightUI\AppGuard\AppLock` holds whether the app is unlocked. The flag
  lives in memory, so every cold start begins locked. It turns on the
  app-switcher cover and the shield once per PHP process, because native state
  does not survive the process.
- The `app-lock` route middleware keeps a protected screen from mounting while
  the app is locked and sends it to the `lock` route instead. NativePHP runs it
  before every navigation, including deep links. See
  [Guard screens](guard-screens.md).
- The `RequiresUnlock` trait covers the moments middleware does not run: the
  app returning behind the shield while a protected screen shows, and a
  protected screen being shown again after the one above it closes.
- `LockScreen` asks for Face ID, Touch ID, or a fingerprint, and replaces itself
  with the screen the user was sent away from once they pass.

The shield is what keeps the content hidden on return. After the timeout in the
background, App Guard covers the app with an opaque view before its first frame:
your splash screen by default. A protected screen replaces itself with the lock
screen, which releases the shield when it opens; the shield's 200 ms fade covers
the lock screen's first render. On any other screen App Guard releases the
shield itself, and protected screens lower in the stack lock when they are
shown again.

## 1. Register the routes

```php
use App\NativeComponents\LockScreen;
use App\NativeComponents\VaultScreen;
use Illuminate\Support\Facades\Route;

Route::middleware('app-lock')->group(function () {
    Route::native('/vault', VaultScreen::class)->name('vault');
    Route::native('/lock', LockScreen::class)->name('lock');
});
```

The middleware always lets the `lock` route through, so it can sit in the same
group. Put every screen with protected content behind it.

## 2. Choose the timeout and shield image

The defaults lock after 60 seconds in the background, turn on the app-switcher
cover, and show your splash screen on the shield. To change them, publish the
config:

```bash
php artisan vendor:publish --tag=app-guard-config
```

```php
// config/app-guard.php
'lock' => [
    'timeout' => 300,
    // ...
],
'shield' => [
    'image' => 'lock-cover.png', // under public/
    'image_dark' => null,
    'fit' => 'center',           // or 'fill'
],
```

## 3. Write the lock screen

The lock screen tags each prompt with an id and ignores answers for any other
prompt. It suspends the shield while the system prompt is open, and asks again
when the app returns while no prompt is showing.

```php
<?php

namespace App\NativeComponents;

use FirstlightUI\AppGuard\AppLock;
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
     * App Guard releases the shield over this screen; asking again is this screen's job.
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
        $this->replace(app(AppLock::class)->pullIntended('/'));
    }
}
```

`pullIntended()` returns the screen the middleware or `RequiresUnlock` sent
away, and forgets it once read.

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

## 4. Protect a screen

A protected screen uses `RequiresUnlock`. The middleware has already let it
through by the time `mount()` runs, so it can load its data there. Put resume
work in `resumed()`, which runs only while unlocked.

```php
<?php

namespace App\NativeComponents;

use FirstlightUI\AppGuard\Concerns\RequiresUnlock;
use FirstlightUI\NativeComponent;
use Illuminate\View\View;

class VaultScreen extends NativeComponent
{
    use RequiresUnlock;

    public string $balance = '';

    public function mount(): void
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

## 5. Test it

Fake the bridge functions App Guard and the lock screen call, then emit the
native events. Use `Native::visit()` so the middleware runs. See
[Test screens](test-screens.md) for the harness.

```php
Native::fakeBridge()
    ->respondTo('AppGuard.SetPrivacyCover', [])
    ->respondTo('AppGuard.SetShieldTimeout', [])
    ->respondTo('AppGuard.SetShieldImage', [])
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
| Return after the timeout | The shield image, splash screen or app icon over every window before the first frame | The same, over the window before the first frame |
| Unlock prompt | Face ID or Touch ID, falling back to the device passcode | Fingerprint or face unlock with a Cancel button. There is no passcode fallback |
| Time away | Measured natively on a clock that keeps running while the device sleeps | The same |

## Rules to keep

- Give users a way out of the lock screen, such as signing out and removing the
  app's data. On Android, a device with no fingerprint or face enrolled can
  never pass the prompt.
- Put every screen with protected content behind `app-lock` and give it
  `RequiresUnlock`. A screen without the trait stays visible after a shielded
  return, because App Guard releases the shield over it.
- Suspend the shield around your own prompt and resume it afterwards, as the
  lock screen does. Android's credential screen otherwise counts as a trip to
  the background.
- Tell users that the app blocks screenshots on Android, because the app-switcher
  cover uses `FLAG_SECURE`.
- Never persist the unlocked state. App Guard keeps it in memory so a cold start
  cannot skip the lock.
