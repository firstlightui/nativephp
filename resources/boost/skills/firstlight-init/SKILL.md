---
name: firstlight-init
description: Sets up a new NativePHP Mobile app with Firstlight UI by interviewing the developer, verifying the install, configuring the theme and native-chrome layout, and scaffolding chosen recipe screens with tests. Use when asked to initialise, bootstrap, or scaffold a Firstlight app, or right after installing firstlightui/nativephp.
---

# Initialise a Firstlight App

Get a developer from `composer require` to a running, themed, tested app built from Firstlight recipes. Ask before writing; make no change the developer has not agreed to.

## 1. Check the install

Confirm, and fix with the developer's agreement:

- `composer.json` requires `nativephp/mobile` `^4.6`, `nativephp/mobile-ui` `^0.8` and `firstlightui/nativephp`. Before 1.0 stable, the constraint needs `@RC`, for example `"firstlightui/nativephp:^1.0@RC"`.
- The plugin is registered: `php artisan native:plugin:register`.
- PHP `^8.4`, iOS 18+ and Android API 29+ targets. See https://firstlightui.dev/reference/compatibility.

## 2. Interview

Ask these one at a time, offering choices where you can:

1. What does the app do, and what is the main record (for example posts, jobs, patients)? Use it to name models and routes.
2. Which recipes should be scaffolded? Offer: list and detail flow (always included, it is the app shell), searchable list, form screen, settings screen.
3. Brand colours: a primary colour, and whether to tune dark mode or let NativePHP derive it.
4. Does the first screen need tabs? If so, which (two to five)?
5. Should deletion be allowed from lists, and who may delete (Policy rule)?
6. Which locale and timezone does the app use?

## 3. Theme

Publish the theme with `php artisan vendor:publish --tag=native-ui-config`, then set `primary`/`on-primary` for light (and dark if requested) in `config/native-ui.php`. Check each pair is at least 4.5:1 contrast and suggest an adjusted shade when it is not. Leave other tokens at their defaults unless asked. See https://firstlightui.dev/getting-started/theming.

## 4. Scaffold

1. Create `app/NativeComponents/Layouts/AppLayout.php` exactly as in https://firstlightui.dev/how-to/build-a-list-detail-flow. Add `tabBar()` with `Tab::link()` entries only if tabs were requested.
2. Create the model, migration and Policy for the main record if they do not exist.
3. For each chosen recipe, copy its screen, view and route from the recipe page, renamed to the app's record. Register routes with `->layout(AppLayout::class)`; register `/records/create` before `/records/{record}`.
4. Add `FirstlightUI\Testing\ScreenAssertions::register();` to `tests/Pest.php` and one Pest test per screen covering its main path and its failure path (validation error, cancelled delete).
5. Set `APP_LOCALE` and `APP_TIMEZONE`; Date and Time pickers follow them.

## 5. Verify and hand over

- Run `php artisan migrate` and the test suite; fix failures before continuing.
- Ask the developer to run the app on an iOS simulator and an Android emulator (`php artisan native:run`), and check the large title, back navigation, field errors and deletion on both.
- Summarise what was created, which recipe each screen came from, and the next screen to build. From here, follow the `building-firstlight-screens` skill.
