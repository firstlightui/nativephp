---
title: Build a list and detail flow
description: Recipe for an app layout with native navigation chrome, a list screen that pushes a detail screen, and native back navigation on iOS and Android.
type: how-to
audience: consumer
sources:
  - src/NativeComponent.php
  - vendor/nativephp/mobile/src/Edge/Layouts/NativeLayout.php
  - vendor/nativephp/mobile/src/Edge/Layouts/Builders/NavBar.php
  - vendor/nativephp/mobile/src/Edge/NativeComponent.php
---

# Build a List and Detail Flow

This recipe gives an app its shell: a layout with the platform's own title bar
and back navigation, a list screen, and a detail screen it pushes. Every other
recipe runs inside this layout.

This recipe runs unchanged in the
[showcase app](https://github.com/firstlightui/showcase), where
[`ListDetailRecipeTest`](https://github.com/firstlightui/showcase/blob/main/tests/Feature/Recipes/ListDetailRecipeTest.php)
exercises it.

Firstlight does not draw navigation. NativePHP Mobile renders it natively when
a layout opts in with `usesNativeChrome()`:

| Behaviour | iOS | Android |
| --- | --- | --- |
| Stack | SwiftUI `NavigationStack` | Material 3 `Scaffold` |
| Title | Large title that collapses to inline on scroll | `LargeTopAppBar` / `TopAppBar` |
| Back | System back button and edge swipe | Top app bar back arrow and system back |

PHP stays in charge of the screen stack. A back swipe or back press pops PHP's
stack, so `mount()` and screen state behave the same on both platforms.

## 1. Write the layout

Save this as `app/NativeComponents/Layouts/AppLayout.php`:

```php
<?php

namespace App\NativeComponents\Layouts;

use FirstlightUI\NativeComponent as FirstlightScreen;
use Native\Mobile\Edge\Layouts\Builders\NavBar;
use Native\Mobile\Edge\Layouts\NativeLayout;
use Native\Mobile\Edge\NativeComponent;

class AppLayout extends NativeLayout
{
    public function usesNativeChrome(): bool
    {
        return true;
    }

    public function navBar(NativeComponent $screen): ?NavBar
    {
        $isRoot = $screen instanceof FirstlightScreen && $screen->isRootScreen();

        return NavBar::make()
            ->title($screen->navTitle())
            ->displayMode($isRoot ? 'large' : 'inline')
            ->back(! $isRoot);
    }
}
```

`isRootScreen()` is true when nothing is below the screen on PHP's stack. The
first screen gets a large title and no back button; every pushed screen gets an
inline title and a back button. Android shows a back arrow only when the bar
asks for one, so pushed screens must pass `back(true)`. Each screen supplies its
title through `navTitle()`.

## 2. Register the routes

```php
use App\NativeComponents\Layouts\AppLayout;
use App\NativeComponents\PostScreen;
use App\NativeComponents\PostsScreen;
use Illuminate\Support\Facades\Route;

Route::native('/posts', PostsScreen::class)->layout(AppLayout::class);
Route::native('/posts/{post}', PostScreen::class)->layout(AppLayout::class);
```

## 3. Write the list screen

Use the [searchable list recipe](build-a-searchable-list-screen.md). Its row
press pushes the detail screen:

```php
public function openPost(int $id): void
{
    $this->navigate('/posts/'.$id);
}
```

`navigate()` pushes. Use `replace()` to swap the current screen without adding
to the stack, and `back()` to pop it from PHP, for example after saving.

## 4. Write the detail screen

```php
<?php

namespace App\NativeComponents;

use App\Models\Post;
use FirstlightUI\NativeComponent;
use Illuminate\View\View;

class PostScreen extends NativeComponent
{
    public Post $post;

    public function navTitle(): string
    {
        return $this->post->title;
    }

    public function render(): View
    {
        return view('native.post');
    }
}
```

NativePHP fills a typed public property from the route parameter with the same
name, using Laravel route model binding: `{post}` becomes a `Post` loaded by id.
No `mount()` is needed.

Save the view as `resources/views/native/post.blade.php`:

```blade
<native:scroll-view class="w-full flex-1 bg-theme-background">
    <native:column class="w-full p-4 gap-3">
        <native:text class="text-sm text-theme-on-surface-variant">{{ $post->created_at->diffForHumans() }}</native:text>
        <native:text class="text-base text-theme-on-surface">{{ $post->body }}</native:text>
    </native:column>
</native:scroll-view>
```

## Rules to keep

- Return `true` from `usesNativeChrome()`. Without it, NativePHP falls back to
  drawn bars that do not support large titles or the iOS back swipe.
- Do not add the `custom` attribute to `<native:top-bar>` or
  `<native:bottom-nav>`. It keeps the drawn bar.
- Pass stable ids in URLs, never list positions.
- Let the platform draw the back button. Do not add your own back Button.

## Known limits

- Tabs added with `tabBar()` share PHP's single stack. Switching tabs replaces
  the screen, so each tab does not keep its own history.
- Android receives back only when the gesture completes. It does not show the
  predictive-back preview while the user drags.
