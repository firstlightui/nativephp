---
title: Guard screens
description: Keep signed-out or locked users off protected screens with route middleware, re-check when a screen is shown again, and test both.
type: how-to
audience: consumer
sources:
  - src/NativeComponent.php
  - vendor/nativephp/mobile/src/Edge/ScreenGuard.php
  - vendor/nativephp/mobile/src/Edge/NativeRouter.php
  - vendor/nativephp/mobile/src/NativeServiceProvider.php
  - vendor/nativephp/mobile/src/Testing/TestableComponent.php
---

# Guard Screens

Put the check that decides who may open a screen in route middleware, not in
each screen's `mount()`. NativePHP runs a native route's middleware before the
screen mounts, on every navigation: cold start, `navigate()`, `replace()`, deep
links, and the stack restored after a hot reload. A refused screen never mounts,
so it runs no queries or API calls and publishes no frame.

This guide adds a sign-in guard to an app with a tab layout and a stack layout.
The same pattern works for an app lock, a selected account, or an onboarding
step: anything the app can read from its session or device state.

## 1. Write the middleware

Save this as `app/Http/Middleware/EnsureSignedIn.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSignedIn
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return $next($request);
        }

        return redirect()->route('login');
    }
}
```

Replace `Auth::check()` with whatever your app treats as signed in, such as a
device token in secure storage. Return a redirect to refuse the screen. A
redirect to a native route replaces the refused screen with that one; a
redirect to any other URL leaves the native screens for the web view.

Return the redirect yourself rather than relying on Laravel's `auth`
middleware. With NativePHP Mobile 4.7.0, `auth` refusing a guest during
in-app navigation sends the user to the app's `start_url` instead of the
`login` route.

## 2. Register the routes

```php
use App\Http\Middleware\EnsureSignedIn;
use App\NativeComponents\HomeScreen;
use App\NativeComponents\Layouts\StackLayout;
use App\NativeComponents\Layouts\TabsLayout;
use App\NativeComponents\LoginScreen;
use App\NativeComponents\MessageScreen;
use Illuminate\Support\Facades\Route;

Route::native('/login', LoginScreen::class)->name('login');

Route::middleware(EnsureSignedIn::class)->group(function () {
    Route::nativeGroup(TabsLayout::class, function () {
        Route::native('/', HomeScreen::class)->name('home');
    });

    Route::native('/messages/{message}', MessageScreen::class)
        ->name('messages.show')
        ->layout(StackLayout::class);
});
```

`Route::nativeGroup()` gives every route inside it the same layout, and
`->layout()` sets one route's layout. Middleware and layouts are independent,
so a guarded group can hold screens with different chrome. See
[Build a list and detail flow](build-a-list-detail-flow.md) for a layout that
shows a large title on the first screen and a back button on pushed screens.

Leave the login screen outside the guard. A guard that redirects to the screen
it guards is refused instead of looping.

## 3. Re-check when a screen is shown again

Middleware runs when a screen is pushed or replaced. It does not run when the
screen above it closes and the screen underneath is shown again. If the user can
sign out or lock the app from a pushed screen, check again in `onResume()`.
A shared base class keeps that in one place:

```php
<?php

namespace App\NativeComponents;

use FirstlightUI\NativeComponent;
use Illuminate\Support\Facades\Auth;

abstract class AppScreen extends NativeComponent
{
    public function onResume(): void
    {
        if (Auth::guest()) {
            $this->replace('/login');
        }
    }
}
```

Screens that override `onResume()` call `parent::onResume()` first. To lock the
app when it returns from the background, follow
[Build an app lock screen](build-an-app-lock-screen.md); its protected screens
use the same `onResume()` check.

## 4. Test the guard

`Native::visit()` resolves a screen by URI and runs its middleware, as in-app
navigation does:

```php
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Native\Mobile\Testing\Native;

it('sends a guest to the login screen', function () {
    Native::visit('/')->assertReplacedWith('/login');
    Native::visit('/messages/4')->assertReplacedWith('/login');
});

it('lets a signed-in user in', function () {
    $this->actingAs(User::factory()->create());

    Native::visit('/')->assertSee('Inbox');
});

it('checks again when the screen is shown again', function () {
    $this->actingAs(User::factory()->create());
    $screen = Native::visit('/');

    Auth::logout();

    $screen->call('onResume')->assertReplacedWith('/login');
});
```

`Native::test(HomeScreen::class)` mounts the class directly and does not run
route middleware, so a suite that only uses `Native::test()` cannot catch a
guard regression.

## What middleware sees

The request middleware receives during in-app navigation is built by NativePHP
for the target URI. It carries the live session, the launch request's user,
cookies and server values, and the bound route, so `auth()->user()`, `can:`
and route model binding work. Its method is always `GET`, and it has no body,
query string, uploaded files, device headers, or client IP. Session start, CSRF
and cookie middleware already ran for the launch request and are skipped.

Middleware that should run once per launch, such as a rate limiter or a visit
counter, can opt out in a service provider's `boot()` method:

```php
use Native\Mobile\Edge\ScreenGuard;

ScreenGuard::skip([RecordVisit::class]);
```

If middleware throws instead of answering, the screen is refused, the exception
is reported, and the user goes to the app's `start_url`. Use
`ScreenGuard::onFailure()` to send them somewhere else.

## Rules to keep

- Guard with middleware on the route, not with a check in `mount()`. A screen
  reached through a route without the middleware is not protected.
- Re-check in `onResume()` when the condition can change while a screen is
  covered by another.
- Keep every guard's redirect target outside that guard.
- Test guarded screens with `Native::visit()`, not only `Native::test()`.
