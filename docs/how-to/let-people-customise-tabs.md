---
title: Let people customise their tabs
description: Recipe for a tab bar people rearrange themselves, with a More screen and a drag-and-drop editor, within the tabs, locks and limits you declare.
type: how-to
audience: consumer
sources:
  - src/Elements/ReorderList.php
  - src/NativeComponent.php
  - vendor/nativephp/mobile/src/Edge/Layouts/Builders/Tab.php
  - vendor/nativephp/mobile/src/Edge/Layouts/Builders/TabBar.php
  - vendor/nativephp/mobile/src/Testing/Native.php
---

# Let People Customise Their Tabs

This recipe gives an app a tab bar that people arrange themselves. You declare
which tabs exist, which stay put and how many fit. Tabs that don't fit go to a
More screen, and an editor lets people drag tabs between the bar and More, like
the iPhone's own More tab editing.

This recipe runs in the
[showcase app](https://github.com/firstlightui/showcase), where
[`CustomTabsRecipeTest`](https://github.com/firstlightui/showcase/blob/main/tests/Feature/Recipes/CustomTabsRecipeTest.php)
exercises it.

## Before you start

The recipe uses
[`firstlightui/custom-tabs`](https://github.com/firstlightui/custom-tabs),
free and MIT licensed. It is not released yet: it is planned alongside
Firstlight 1.2 and needs Firstlight's [Reorder List](../components/reorder-list.md),
which is on Firstlight's `main` branch. Until then, install both from `main`:

```json
// composer.json
"repositories": [
    { "type": "vcs", "url": "https://github.com/firstlightui/custom-tabs" }
],
```

```bash
composer require firstlightui/nativephp:dev-main firstlightui/custom-tabs:dev-main
```

Custom Tabs is PHP only, so it needs no `native:plugin:register` and no
rebuild beyond Firstlight's own.

## How the pieces fit

- `CustomTabs::define()` declares one bar's tabs and rules, once, in a service
  provider.
- `CustomTabs::bar()` returns the person's bar as a NativePHP `TabBar`, ready
  for a layout's `tabBar()`.
- `Route::customTabs()` adds the More screen at the More tab's URL and the
  editor below it.
- The person's choice is saved in a JSON file in the app's storage, so it
  needs no migration and goes away with the app. Bind your own
  `TabPreferenceStore` to keep it somewhere else.

## 1. Declare the tabs

```php
// app/Providers/AppServiceProvider.php
use FirstlightUI\CustomTabs\Facades\CustomTabs;
use FirstlightUI\CustomTabs\TabSet;
use Native\Mobile\Edge\Layouts\Builders\Tab;

public function boot(): void
{
    CustomTabs::define('main', fn (TabSet $tabs) => $tabs
        ->tab('home', Tab::link('Home', '/home', icon: 'home', ios: 'house', android: 'home'))->locked()
        ->tab('inbox', Tab::link('Inbox', '/inbox', icon: 'inbox', ios: 'tray', android: 'inbox'))
        ->tab('calendar', Tab::link('Calendar', '/calendar', icon: 'calendar_month', ios: 'calendar', android: 'calendar_month'))
        ->tab('reports', Tab::link('Reports', '/reports', icon: 'bar_chart', ios: 'chart.bar', android: 'bar_chart'))->can('view-reports')
        ->tab('files', Tab::link('Files', '/files', icon: 'folder', ios: 'folder', android: 'folder'))->inMoreByDefault()
        ->more(Tab::link('More', '/more', icon: 'more_horiz', ios: 'ellipsis', android: 'more_horiz')));
}
```

Declaration order is the default order. Each modifier applies to the tab
added just before it:

| Method | Meaning |
| --- | --- |
| `tab($id, $tab)` | Adds a tab. The id is what saved choices refer to: lowercase letters and digits joined by hyphens. Keep it stable; it replaces the id `Tab::link()` derives from the label, which changes when the label is translated. `more` is reserved. |
| `locked()` | Keeps the tab in the bar at its declared place. Locked tabs must be declared before or after every movable tab. |
| `inMoreByDefault()` | Starts the tab in More instead of the bar. A locked tab can't. |
| `can($ability, $arguments)` | Shows the tab only to people the Gate allows. While hidden it keeps its saved place. |
| `more($tab)` | The tab that opens the More screen. It shows only while More holds a tab. Required when any tab can move. |
| `maxInBar($count)` | Most tabs in the bar, counting More: 2 to 5. Defaults to 5, the most Material 3 and the iPhone tab bar allow. |
| `minInBar($count)` | Fewest tabs in the bar, not counting More. Defaults to 2, and must leave room for More. |

A definition the rules can't fill, such as more locked tabs than fit, throws
at the first render rather than drawing a broken bar.

## 2. Show the bar and add its screens

Return the person's bar from the layout. Every `TabBar` option still works on
it:

```php
use FirstlightUI\CustomTabs\Facades\CustomTabs;
use Native\Mobile\Edge\Layouts\Builders\TabBar;
use Native\Mobile\Edge\NativeComponent;

public function tabBar(NativeComponent $screen): ?TabBar
{
    return CustomTabs::bar('main')->presentation('firstlight:tabs');
}
```

`presentation('firstlight:tabs')` is optional; see
[Tabs](../components/tabs.md). Then register the More screen and the editor in
`routes/native.php`, with the layout that shows the bar:

```php
Route::customTabs('main', TabsLayout::class);
```

With the declaration above, the More screen opens at `/more` and the editor at
`/more/edit`, so the editor opens inside the More tab. While a screen from
More is open, the bar highlights More.

## 3. What people see

- **More screen.** Lists the tabs that are in More, in the person's order, with
  Edit in the navigation bar.
- **Editor.** Shows the bar and More as two sections of a Reorder List. People
  drag tabs with the handles, or use the VoiceOver and TalkBack actions: Move
  up, Move down, and Move to the other section. Locked tabs show a lock and
  don't move.
- **Rules apply at once.** Each move is checked against the declaration
  immediately, without saving. A tab dropped into a full bar pushes the last
  movable tab into More.
- **Done saves.** The real bar changes only on Done. Leaving without Done
  discards the draft. Reset to default asks first, then puts the declared
  order back.

## Use your own settings screen

To put the editor in an existing settings screen, extend
`FirstlightUI\CustomTabs\Screens\EditorScreen`, or build it from the facade:

| Method | Returns |
| --- | --- |
| `CustomTabs::arrangement($name)` | Where each tab the person may see sits now: `bar`, `more`, and where More goes. |
| `CustomTabs::preview($name, $bar, $more)` | What a choice would become under the rules, without saving it. |
| `CustomTabs::save($name, $bar, $more)` | Checks and saves a choice, and dispatches `TabsCustomized`. |
| `CustomTabs::reset($name)` | Forgets the choice and returns the declared defaults. |
| `CustomTabs::tabs($name)` | Each visible tab's id, label, icon, badge, URL and whether it is locked. |

Bind `preview()` to a `<firstlight:reorder-list>`'s `@change` so every drop
shows the corrected arrangement, and call `save()` from your own Done action.
Listen for `FirstlightUI\CustomTabs\Events\TabsCustomized` to react to a new
choice.

The strings are in the package's `lang/en/tabs.php`. Publish them to
translate or reword them:

```bash
php artisan vendor:publish --tag=custom-tabs-lang
```

## Test the bar

Visit a screen with `Native::visit()` and read the labels in its tab bar:

```php
use FirstlightUI\CustomTabs\Facades\CustomTabs;
use Native\Mobile\Testing\Native;

beforeEach(fn () => CustomTabs::reset('main'));

it('changes the bar only when the editor saves', function () {
    $editor = Native::visit('/more/edit');
    $editor->call('move', ['bar' => ['home', 'files', 'inbox', 'calendar'], 'more' => ['reports']]);
    $editor->call('done');

    expect(CustomTabs::arrangement('main')->bar)->toBe(['home', 'files', 'inbox', 'calendar']);
});
```

Reset the bar before each test, because the choice is saved in storage. See
[Test screens](test-screens.md) for more of `Native::test()` and
`Native::visit()`.
