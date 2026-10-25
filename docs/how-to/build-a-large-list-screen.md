---
title: Build a large list screen
description: Recipe for a list of thousands of local records with native:virtual-list, Firstlight List Item rows, and one query per visible window.
type: how-to
audience: consumer
sources:
  - src/Elements/ListItem.php
  - src/NativeComponent.php
  - vendor/nativephp/mobile/src/Edge/Concerns/HasVirtualListWindow.php
  - vendor/nativephp/mobile/src/Edge/NativeTagPrecompiler.php
  - vendor/nativephp/mobile-ui/src/Elements/NativeVirtualList.php
---

# Build a Large List Screen

This recipe shows thousands of records in one scrolling list without building
them all. Native renders the full scroll extent, PHP builds only the rows near
the visible window, and the next slice is built as the user scrolls.

This recipe runs unchanged in the
[showcase app](https://github.com/firstlightui/showcase), where
[`LargeListRecipeTest`](https://github.com/firstlightui/showcase/blob/main/tests/Feature/Recipes/LargeListRecipeTest.php)
exercises it against 5,000 contacts.

## Virtualise or paginate?

| The data… | Use |
| --- | --- |
| Is local (SQLite on the device) or has a cheap count and offset query | This recipe: `native:virtual-list` |
| Comes from a remote API with cursors or no known total | [Paginate lists](paginate-lists.md) with `@endReached` |
| Is short and fixed, or needs sections and pull-to-refresh | [List](../components/list.md) |

Prefer virtualisation for large local collections. A paginated List keeps
every loaded row in the published tree, so a long scroll keeps growing what
PHP renders and sends. A virtual list stays at about 80 rows however far the
user scrolls, and its scroll bar reflects the whole collection.

## 1. Register the routes

```php
use App\NativeComponents\ContactScreen;
use App\NativeComponents\ContactsScreen;
use App\NativeComponents\Layouts\AppLayout;
use Illuminate\Support\Facades\Route;

Route::native('/contacts', ContactsScreen::class)
    ->name('recipes.contacts')
    ->layout(AppLayout::class);

Route::native('/contacts/{contact}', ContactScreen::class)
    ->name('recipes.contact')
    ->layout(AppLayout::class);
```

`AppLayout` is defined in [Build a list and detail flow](build-a-list-detail-flow.md).

## 2. Write the screen

NativePHP's `HasVirtualListWindow` trait holds the window
(`$virtualWindowFrom`, `$virtualWindowTo`, rows 0–79 at first) and its
`setVirtualWindow()` handler. The screen loads the window's rows with one
query and shares them, keyed by absolute index, with the row view.

```php
<?php

namespace App\NativeComponents;

use App\Models\Contact;
use FirstlightUI\NativeComponent;
use Illuminate\Support\Facades\View as ViewFactory;
use Illuminate\View\View;
use Native\Mobile\Edge\Concerns\HasVirtualListWindow;

class ContactsScreen extends NativeComponent
{
    use HasVirtualListWindow;

    public function openContact(int $id): void
    {
        $this->navigate('/contacts/'.$id);
    }

    public function navTitle(): string
    {
        return 'Contacts';
    }

    public function render(): View
    {
        // One query for the rows in the current window, keyed by their
        // absolute index so each row view can find its own contact.
        $from = $this->virtualWindowFrom;

        $window = Contact::query()
            ->orderBy('name')
            ->orderBy('id')
            ->skip($from)
            ->take($this->virtualWindowTo - $from + 1)
            ->get()
            ->values()
            ->keyBy(fn (Contact $contact, int $offset) => $from + $offset);

        ViewFactory::share('contactWindow', $window);

        return view('native.contacts', [
            'total' => Contact::query()->count(),
        ]);
    }
}
```

Do not query inside the row view. The row view renders once per index, so a
query there runs about 80 times for every window change.

## 3. Write the views

The screen view is one self-closing tag. `item` names the row view.

```blade
{{-- resources/views/native/contacts.blade.php --}}
<native:virtual-list
    class="w-full flex-1"
    :count="$total"
    :from="$virtualWindowFrom"
    :to="$virtualWindowTo"
    item="native.rows.contact"
    on-window-change="setVirtualWindow"
    :estimated-row-height="\Native\Mobile\Platform::isIos() ? 64 : 72"
/>
```

The row view receives `$index` and renders one ordinary List Item:

```blade
{{-- resources/views/native/rows/contact.blade.php --}}
@php($contact = $contactWindow[$index])

<firstlight:list-item
    :headline="$contact->name"
    :supporting="$contact->email"
    trailing-icon="chevron-right"
    trailing-icon-ios="chevron.right"
    trailing-icon-android="chevron_right"
    @press="openContact({{ $contact->id }})"
/>
```

The detail screen is the same as in the
[list and detail flow](build-a-list-detail-flow.md), with
`public Contact $contact` bound from the route.

## 4. Test it

`setVirtualWindow()` is a public method, so a test moves the window the way
native does:

```php
<?php

use App\Models\Contact;
use FirstlightUI\Testing\ScreenAssertions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Native\Mobile\Testing\Native;

uses(RefreshDatabase::class);

beforeEach(fn () => ScreenAssertions::register());

it('builds only the rows in the first window', function () {
    Native::visit('/contacts')
        ->assertListItemCount(80)
        ->assertListItem('Contact 0001')
        ->assertListItem('Contact 0080')
        ->assertMissingListItem('Contact 0081');
});

it('builds the new slice when the native window moves', function () {
    Native::visit('/contacts')
        ->call('setVirtualWindow', 4000, 4079)
        ->assertListItemCount(80)
        ->assertListItem('Contact 4001')
        ->assertListItem('Contact 4080')
        ->assertMissingListItem('Contact 0001');
});

it('clips the last window to the end of the collection', function () {
    Native::visit('/contacts')
        ->call('setVirtualWindow', 4960, 5039)
        ->assertListItemCount(40)
        ->assertListItem('Contact 5000');
});

it('opens the detail screen for the pressed row', function () {
    Native::visit('/contacts')
        ->call('setVirtualWindow', 2000, 2079)
        ->pressListItem('Contact 2042')
        ->assertNavigatedTo('/contacts/'.Contact::where('name', 'Contact 2042')->value('id'));
});
```

## Rules to keep

- One virtual list per screen. This is a NativePHP limit.
- Rows outside the window are fixed-height placeholders sized by
  `estimated-row-height`. Set it close to the real row height so the scroll
  bar does not jump. A List Item with supporting text is 64 points on iOS and
  72 dp on Android at the default text size, so the example picks the value
  per platform.
- Order by a unique column last (`orderBy('id')`) so a window always holds the
  same rows.
- Pass model keys, not `$index`, into `@press` and other callbacks.
- Virtual lists have no sections, separators, pull-to-refresh, or
  end-reached event. Use [List](../components/list.md) when you need those.
- Returning from the detail screen rebuilds the list at the top. NativePHP
  navigation does not restore scroll position, for a virtual list or a List.

## Adapt the recipe

- **Swipe and context-menu actions:** add `trailing-actions` or `context-menu`
  to the row's List Item exactly as on a List row.
