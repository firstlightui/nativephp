---
title: Start a new app
description: Describe your app to a coding agent, confirm the plan it proposes, and get a themed NativePHP app with Firstlight screens, the plugins it needs, and Pest tests.
type: how-to
audience: consumer
sources:
  - resources/boost/skills/firstlight-init/SKILL.md
  - resources/starter/plugins.json
  - resources/starter/firstlight-starter.example.json
  - src/Console/StarterCommand.php
  - src/Starter/StarterPlan.php
  - src/Starter/StarterBuilder.php
  - bin/check-starter-apps
  - tests/fixtures/starter/private-journal.json
  - tests/Feature/StarterCommandTest.php
---

# Start a New App

Describe the app you are building, and Firstlight's `firstlight-init` skill
proposes a layout, screens built from the recipes, and the NativePHP plugins
that fit. It then collects the app's name, ID, colours, icon and locale. When
you confirm the plan, `php artisan firstlight:starter` builds it: a NativePHP
app with native-chrome navigation, a list, detail, form and settings screen for
each record, your brand colour, and a Pest test for every screen.

## Create the Laravel app

Start from a new Laravel app, then add Firstlight, Pest and
[Laravel Boost](https://laravel.com/docs/boost):

```bash
composer create-project laravel/laravel my-app
cd my-app
composer require firstlightui/nativephp
php artisan vendor:publish --tag=nativephp-plugins-provider
php artisan native:plugin:register nativephp/mobile-ui
php artisan native:plugin:register firstlightui/nativephp
composer remove phpunit/phpunit --dev
composer require pestphp/pest pestphp/pest-plugin-laravel --dev -W
vendor/bin/pest --init
composer require laravel/boost --dev
php artisan boost:install
```

Firstlight brings NativePHP Mobile and Mobile UI with it. The generated tests
are Pest tests, so the app needs Pest. Boost installs the `firstlight-init`
skill into your coding agent; see
[Build with a coding agent](build-with-a-coding-agent.md) for what else it
installs.

## Describe the app

Ask your agent to start the app, and say what it does, who uses it and what
it keeps. For example:

> Start a Firstlight app: a private journal for my own dated entries. Nobody
> else should read them if they pick up my phone.

The skill restates what it understood: the records, whether the data is
private, whether the app must work without signal, and what it needs from the
device. Correct anything it got wrong.

## Change the proposal by reply

The skill sends one proposal with three parts:

- **Layout:** tabs for two to five equal areas, or one stack when a single list
  is the app.
- **Screens:** one line for each screen, naming the recipe it is built from.
- **Plugins:** *Included* free plugins, *Offered* paid plugins, and *Not
  needed* plugins that Firstlight already covers, such as a camera plugin for
  photo fields.

Reply with changes, such as "drop settings" or "yes to biometrics". Paid
plugins are installed only when you say yes to one by name. NativePHP serves a
paid plugin only to an account that has bought it, so buy it first, or the
app is built without it and `STARTER.md` gives you the steps to add it. Each
community plugin comes with a notice that neither Firstlight nor NativePHP
maintains or warrants it.

## Check the essentials

Next the skill shows one table of proposed values, and you change any row by
reply:

- The app name, and the app ID, which NativePHP uses as both the iOS bundle ID
  and the Android application ID. The stores fix the ID at the first upload.
- The deep link scheme and appearance.
- The brand colour and the text colour on it, checked for 4.5:1 contrast.
- The icon: a square PNG of at least 1024 × 1024, or a placeholder in the
  brand colour.
- The locale, fallback locale and timezone.
- The minimum OS: iOS 18.2 and Android API 29, raised by any plugin that needs
  more.
- One sentence for each permission the app asks for, worded for this app.

## Confirm the plan

The skill writes the plan to `firstlight-starter.json` in the app and shows it
to you. Nothing is built until you confirm it. For the journal, the plan looks
like this:

```json
{
    "description": "Private journal with an app lock: my own dated entries, and nobody else should read them if they pick up my phone.",
    "app": {"name": "Daybook", "id": "com.example.daybook", "deeplink_scheme": "daybook", "appearance": "system"},
    "theme": {"light": {"primary": "#6D28D9", "on-primary": "#FFFFFF"}, "dark": null},
    "icon": null,
    "locale": {"locale": "en", "fallback": "en", "timezone": "Europe/London"},
    "android_min_sdk": 29,
    "layout": {"type": "stack"},
    "records": [{"model": "Entry", "deletable": true, "delete_rule": "Only the person who wrote the entry."}],
    "screens": [
        {"recipe": "how-to/build-a-searchable-list-screen", "record": "Entry"},
        {"recipe": "how-to/build-a-list-detail-flow", "record": "Entry"},
        {"recipe": "how-to/build-a-form-screen", "record": "Entry"},
        {"recipe": "how-to/keep-drafts", "record": "Entry"},
        {"recipe": "how-to/build-an-app-lock-screen"},
        {"recipe": "how-to/guard-screens"},
        {"recipe": "how-to/build-a-settings-screen"}
    ],
    "plugins": [
        {"package": "firstlightui/app-guard", "accepted": true},
        {"package": "nativephp/mobile-biometrics", "accepted": false}
    ],
    "permissions": {}
}
```

## Build it

After you confirm, the skill runs:

```bash
php artisan firstlight:starter
php artisan migrate --seed
vendor/bin/pest
```

`firstlight:starter` checks the whole plan first and lists every problem, such
as an app ID that iOS or Android would reject, a colour pair under 4.5:1, or a
tab that opens no screen in the plan. Fix the plan and run it again.

It then installs and registers the accepted plugins and writes:

- The app name, ID, colours, locale, timezone and minimum SDK in `.env` and
  `.env.example`, the brand colour in `config/native-ui.php`, and the
  permission text in `config/nativephp.php`.
- A placeholder icon and splash in `public/`, unless you gave an icon.
- `app/NativeComponents/Layouts/AppLayout.php` with native chrome, and the tab
  bar in the brand colour when the layout has tabs.
- For each record, its model, migration, factory, Policy and seeder with
  example records.
- Each screen with a template: the searchable list, detail, form, edit with
  kept drafts, and settings, each with its view, its route in
  `routes/mobile.php`, and its Pest test.
- `STARTER.md`, which lists each screen and its recipe, the installed plugins,
  the screens with no template yet, and what is left for you.

The skill then builds each screen listed under "Build next" in `STARTER.md`
from its recipe, such as the journal's app lock screen, and wires up the
plugins those screens use.

`firstlight:starter` is safe to run again after you change the plan: it keeps
files you have edited and lists them, unless you pass `--force`. Pass
`--skip-plugins` when you have installed the plugins yourself.

## Run it on a device

Build and run the app on an iOS simulator and an Android emulator:

```bash
php artisan native:install both --no-interaction
php artisan native:run ios
php artisan native:run android
```

The seeders fill your local database, not the database on the device, so a
fresh install opens on the list's empty state. Add a record with the list's
New button. Check the large title, back navigation, tabs, field errors and
deletion on both platforms.

## Without an agent

You can write the plan yourself. Copy
`vendor/firstlightui/nativephp/resources/starter/firstlight-starter.example.json`
to `firstlight-starter.json`, change it, and run `php artisan firstlight:starter`.
Plugins must be entries in
`vendor/firstlightui/nativephp/resources/starter/plugins.json`, which says what
each one does on iOS and Android and whether it is free or paid.

## What is left for you

`STARTER.md` lists what the starter can't do for you:

- Replace the placeholder icon with the real one.
- Write who may delete each record in its Policy. Until you do, the Policy
  allows everyone.
- Set up signing and builds, with [Bifrost](https://bifrost.nativephp.com) or
  `php artisan native:credentials`.
- Create the App Store Connect app record and the Play Console listing.
- Buy and add any paid plugin you accepted that couldn't be installed.
- Build the back end, sign-in and APIs the app talks to.
- Set `NATIVEPHP_APP_VERSION` and `NATIVEPHP_APP_VERSION_CODE` when you
  release.

The generated screens are written in English. To translate the app, see
[Localize chrome](../how-to/localize.md) for Firstlight's own labels, and
translate your screens' text with Laravel's
[localization](https://laravel.com/docs/localization).

From here, ask your agent for each new screen by what it does, such as "add a
screen to tag entries". The `building-firstlight-screens` skill picks its
recipe.
