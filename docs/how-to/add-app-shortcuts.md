---
title: Add Siri and app icon shortcuts
description: Recipe for shortcuts that open a screen or run PHP from Siri, Spotlight, the Shortcuts app, and the app icon's long-press menu, even when the app is closed.
type: how-to
audience: consumer
sources:
  - src/NativeComponent.php
  - src/Facades/Feedback.php
  - src/Testing/ScreenAssertions.php
  - vendor/nativephp/mobile/src/Attributes/On.php
  - vendor/nativephp/mobile/src/Edge/NativeComponent.php
  - vendor/nativephp/mobile/src/Testing/Native.php
---

# Add Siri and App Icon Shortcuts

This recipe lets people reach the app from outside it. One shortcut opens the
new post form. Another adds a post without opening the app at all, and Siri or
Android says what PHP replied.

This recipe runs in the
[showcase app](https://github.com/firstlightui/showcase), where
[`AppShortcutsRecipeTest`](https://github.com/firstlightui/showcase/blob/main/tests/Feature/Recipes/AppShortcutsRecipeTest.php)
exercises it.

## Before you start

The recipe uses
[`firstlightui/app-shortcuts`](https://github.com/firstlightui/app-shortcuts),
free and MIT licensed. It is an alpha: it has been checked on the iOS
simulator and the Android emulator, not yet on physical devices or with Siri
voice.

```bash
composer require "firstlightui/app-shortcuts:^1.0@alpha"
php artisan native:plugin:register firstlightui/app-shortcuts
php artisan vendor:publish --tag=app-shortcuts-config
```

It needs iOS 18 or later and Android 8 (API 26) or later.

## Where shortcuts appear

| Place | iOS | Android |
| --- | --- | --- |
| Spotlight and the Shortcuts app | Every shortcut, also for the Action button | None |
| Siri | Up to 10 shortcuts with phrases, with no setup by the user | None. Gemini has no voice entry point for apps yet |
| App icon long-press menu | Up to 4 shortcuts | Up to 4 shortcuts, which people can pin to the home screen |
| Added while the app runs | The long-press menu only | The long-press menu only |

## 1. Declare shortcuts that open screens

Each shortcut has a stable id and opens a named route:

```php
// config/app-shortcuts.php
return [
    'shortcuts' => [
        'new-post' => [
            'title' => 'New post',
            'route' => 'recipes.posts.create',
            'icon' => 'square.and.pencil',
            'phrases' => ['New post in ${applicationName}', 'Write a post in ${applicationName}'],
        ],
        'haptics' => [
            'title' => 'Try haptics',
            'short_title' => 'Haptics',
            'route' => 'haptics',
            'icon' => 'hand.tap',
            'requires_unlock' => false,
        ],
    ],
];
```

| Key | Meaning |
| --- | --- |
| The array key | The id: lowercase letters and digits joined by hyphens. Keep it stable, because people's own automations in the Shortcuts app refer to it. |
| `title` | Shown in Spotlight, the Shortcuts app, and the long-press menu. |
| `short_title` | Optional. Shown where space is short. Defaults to `title`. |
| `route`, `parameters` | The named route to open, and optional route parameters. |
| `icon` | Optional SF Symbol for iOS. Android uses the app icon. |
| `phrases` | Optional Siri phrases, each containing `${applicationName}` once. |
| `launcher` | Optional, default `true`. Shows the shortcut in the long-press menu. |
| `requires_unlock` | Optional, default `true`. iOS asks the person to unlock the device first. |

Shortcuts are compiled into the app, so a change takes effect on the next
`php artisan native:run` or build. A shortcut that breaks a platform rule, such
as an unknown route or a fifth launcher shortcut, fails the build with a
message, rather than silently not appearing.

A shortcut opens its screen through NativePHP's deep-link path, so route
middleware runs first. A shortcut to a screen behind App Guard's `app-lock`
shows the lock screen first. See
[Build an app lock screen](build-an-app-lock-screen.md).

## 2. Run PHP with an action

An action runs an invokable class instead of opening a screen. Give the
shortcut an `action` in place of a `route`, and a `reply` for when PHP can't
answer straight away:

```php
// config/app-shortcuts.php
'quick-post' => [
    'title' => 'Add a quick post',
    'short_title' => 'Quick post',
    'action' => App\Actions\AddQuickPost::class,
    'reply' => 'Added a quick post',
    'icon' => 'text.badge.plus',
    'phrases' => ['Add a quick post in ${applicationName}'],
],
```

```php
<?php

namespace App\Actions;

use App\Models\Post;
use FirstlightUI\AppShortcuts\ActionInvocation;

class AddQuickPost
{
    public const string TITLE_PREFIX = 'Quick post from';

    public function __invoke(ActionInvocation $invocation): string
    {
        $post = new Post([
            'title' => self::TITLE_PREFIX.' '.$invocation->invokedAt->format('j M, H:i'),
            'excerpt' => 'Added by an app shortcut',
            'body' => 'Added by the "Add a quick post" shortcut, without opening a screen.',
        ]);

        $post->created_at = $invocation->invokedAt;
        $post->save();

        $count = Post::query()->where('title', 'like', self::TITLE_PREFIX.' %')->count();

        return $count === 1 ? 'Added your first quick post.' : "Added a quick post. That's {$count} so far.";
    }
}
```

The class is resolved from the container, so its constructor can ask for
dependencies. `ActionInvocation` carries a unique `id`, the `shortcutId`, and
`invokedAt`, when the person used the shortcut. The string the class returns
is the reply; return nothing to use the configured `reply`.

Where the action was used decides how it runs:

| Used from | iOS | Android |
| --- | --- | --- |
| Siri, Spotlight, the Shortcuts app, the Action button | The class runs straight away and Siri says its reply. The app stays out of sight. When it was closed, iOS starts it in the background and PHP boots there in about two seconds. | Not available |
| The long-press menu | The app opens and the class runs on the screen it shows | The class runs in the background, whether or not the app was running, and the reply shows as a toast |

On Android 13 and later, a toast from an app with no window on screen shows
only if the app may post notifications. The plugin declares
`POST_NOTIFICATIONS` and asks for it the first time anyone runs an action. While
notifications are off, the long-press menu shows a spinner until the reply is
ready, so the toast still shows.

The action is queued instead, and runs when the first screen of the next launch
mounts, when PHP can't start in the background:

- On iOS, before the app has been opened once after installing it or after an
  over-the-air update, and while `NATIVEPHP_APP_VERSION` is unset or `DEBUG`,
  because such a build unpacks its Laravel bundle at every launch.
- In an app using NativePHP's classic runtime mode.
- When PHP fails to boot, or the app takes more than about 10 seconds to boot
  it.

So `invokedAt` can be minutes or days old. Record it, as `AddQuickPost` does,
rather than the time the class runs. Skip actions that only make sense
straight away, such as starting a timer, when it is old.

Each invocation runs at most once. A class that throws is reported through
Laravel's exception handler and not retried.

## 3. Refresh the screen an action changed

After each class that returns, an `ActionRan` event reaches the open screen's
`#[On]` listeners. A screen showing what the action changed can refresh:

```php
use FirstlightUI\AppShortcuts\Events\ActionRan;
use FirstlightUI\Facades\Feedback;
use Native\Mobile\Attributes\On;

#[On(ActionRan::class)]
public function actionRan(string $shortcutId): void
{
    if ($shortcutId === 'quick-post') {
        Feedback::success('A shortcut added a quick post')->send();
    }
}
```

The screen renders again after the listener runs, so a count it reads in
`render()` is current.

## 4. Add shortcuts while the app runs

`AppShortcuts::push()` adds or replaces an entry in the long-press menu, newest
first. It opens a route; actions can't be pushed.

```php
use FirstlightUI\AppShortcuts\Facades\AppShortcuts;

AppShortcuts::push('newest-post', $post->title, 'recipes.post', ['post' => $post], 'doc.text', 'Newest post');
AppShortcuts::remove('newest-post');
AppShortcuts::clear(); // for example on sign-out
```

Each method returns `true` when the native side applied the change. It returns
`false` without a native build, when the plugin is not registered, or when the
native side reported an error. iOS can't add Siri phrases at runtime. A shortcut
someone pinned on Android stays on the home screen after `remove()`.

## 5. Test it

Fake the queue a shortcut would leave, then run it as the first screen of a
launch would. See [Test screens](test-screens.md) for the harness.

```php
use Carbon\CarbonImmutable;
use FirstlightUI\AppShortcuts\Facades\AppShortcuts;
use Native\Mobile\Testing\Native;

$bridge = Native::fakeBridge()->respondTo('AppShortcuts.TakeQueued', ['invocations' => [
    ['id' => 'inv-1', 'shortcut' => 'quick-post', 'at' => CarbonImmutable::parse('2026-11-13 22:05:00 UTC')->getTimestampMs()],
]]);

$screen = Native::visit('/shortcuts')->assertSee('0 quick posts so far');

expect(AppShortcuts::runQueuedActions())->toBe(1);

$screen->assertFeedbackSent('A shortcut added a quick post')
    ->assertSee('1 quick post so far');
```

`push()` and `remove()` call the `AppShortcuts.Push` and `AppShortcuts.Remove`
bridge functions, which `Native::fakeBridge()` can answer and record.

## Rules to keep

- Keep titles generic. They show on a locked device, in search, and in the
  long-press menu, so never put a person's name or other private details in
  one.
- Leave `requires_unlock` on for any screen behind a sign-in.
- Never rename a shipped shortcut's id: automations people built on it stop
  working.
- Record `invokedAt` rather than the time the class runs, because a queued
  action can run much later.
- Don't promise voice shortcuts on Android in your app's copy.
