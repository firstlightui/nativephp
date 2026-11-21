---
name: firstlight-init
description: Starts a new NativePHP Mobile app with Firstlight UI, or sets up an existing one, by interviewing the developer about the app they are building, proposing a layout, recipe screens and NativePHP plugins (free ones included, paid ones only offered), collecting the app name, app ID, brand colour, icon, locale and minimum OS, then scaffolding the confirmed plan with tests. Use when asked to start, initialise, bootstrap or scaffold a Firstlight app, to suggest plugins for an app idea, or right after installing firstlightui/nativephp. For adding one screen to an app that is already set up, use building-firstlight-screens instead.
---

# Start a Firstlight App

Get a developer from an app idea to a running, themed, tested NativePHP app built from Firstlight recipes and the plugins that fit it. You decide what to propose; the developer decides what is built. Write nothing until they have confirmed the plan in step 5.

## Sources of truth

- **Plugins:** `vendor/firstlightui/nativephp/resources/starter/plugins.json`. Propose plugins only from its `plugins` list, and never propose a package in its `covered_by_firstlight` list unless the developer's need matches that entry's `unless`. Read it before step 3.
- **Recipes:** the how-to pages in `vendor/firstlightui/nativephp/docs/how-to/`, also at https://firstlightui.dev/docs/how-to. A recipe id such as `how-to/build-a-form-screen` is that page.
- **Versions:** take Composer constraints for `nativephp/mobile` and `nativephp/mobile-ui` from `vendor/firstlightui/nativephp/composer.json`. Never write a package version, and never set `NATIVEPHP_APP_VERSION` or `NATIVEPHP_APP_VERSION_CODE`; those belong to the developer's release process.

## Plugin rules

These hold in every step.

- **Status.** Propose only `available` plugins. Mention a `preview` plugin as coming, with its repository or marketplace link and no install step. Leave out `unreleased` plugins.
- **Free plugins** that the app needs go in the proposal's *Included* group.
- **Paid plugins** go only in *Offered*, never in *Included*, each marked **Paid** with its marketplace link and its `without` text, so the developer sees what the app does if they decline. Install one only after the developer says yes to that plugin by name.
- **Community plugins** (`source: community`) carry this notice every time you propose one: "Community plugin. Neither Firstlight nor NativePHP maintains or warrants it. Check its licence and repository before you ship it."
- **Prices.** When the NativePHP docs tools `search_plugins` and `get_plugin` are available, look up each paid plugin you offer and show its current price. Otherwise write "Paid, see the marketplace for the price". Never take a price from memory.
- **Gaps.** When the app needs something no index entry covers, such as geolocation, search the marketplace with `search_plugins` if you have it and list matches under *Unreviewed* with their links. Add an unreviewed plugin to the plan only when the developer asks for it by name, and give it the community notice.

## 1. Check the starting point

- **No app yet:** suggest a new Laravel app with Firstlight, Pest and Boost:

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

  The generated tests are Pest tests, so the app needs Pest. NativePHP loads `routes/mobile.php` itself. Don't start from `nativephp/mobile-starter`: it requires NativePHP Mobile 3, and Firstlight needs Mobile 4.8.
- **Existing app:** confirm `nativephp/mobile`, `nativephp/mobile-ui` and `firstlightui/nativephp` are required and registered (`php artisan native:plugin:list`), PHP is `^8.4`, and targets are iOS 18.2+ and Android API 29+ (https://firstlightui.dev/docs/reference/compatibility). Note what is already set (app ID, theme, layout, screens). Keep it unless the developer asks you to change it, and say so in the proposal.
- Native screens are registered in whichever routes file the app already uses for `Route::native()`; in a new app that is `routes/mobile.php`.

## 2. Ask what the app is

Ask one open question: what the app does, who uses it, and what the main records are (for example jobs, entries, attendees). Then restate in a few lines what you understood: the records, whether the data is private, whether it must work without signal, whether people sign in, and what it needs from the device, such as photos, documents, sharing or charts. Ask the developer to correct anything wrong before you propose.

## 3. Propose the plan

Send one message with three parts. The developer edits it by reply, such as "drop settings" or "yes to biometrics"; repeat the changed parts until they accept.

1. **Layout.** Tabs when the app has three to five equal top-level areas, as Apple and Material 3 recommend, each tab named with an icon; for only two, one screen with a segmented control; one stack when a single list is the app. Every layout uses native chrome.
2. **Screens.** One line per screen naming its recipe, such as "Jobs: searchable list (`how-to/build-a-searchable-list-screen`)". Every app gets a list and detail flow, the app shell, plus a form screen for each record people create and a settings screen. Add others when the description calls for them:
   - Many records to find or filter: `build-a-searchable-list-screen`
   - Thousands of local records: `build-a-large-list-screen`
   - Photo or document on a record: `build-a-photo-upload-screen`
   - Long edits people may leave mid-way: `keep-drafts`
   - Cached or fetched data: `show-data-freshness`
   - Works without signal, or queues changes: `show-connectivity`
   - Private data: `build-an-app-lock-screen` and `guard-screens`
   - Delete from lists: `destroy-list-items` and `authorize-actions`; ask who may delete
3. **Plugins**, in four groups: *Included (free)*, *Offered (paid)*, *Unreviewed* (only when step 2 found a gap) and *Not needed*. Under *Not needed*, name each `covered_by_firstlight` package the developer might expect and what replaces it, for example "Photo fields don't need `nativephp/mobile-camera`; Media has its own camera and library pickers." Give each plugin one line on why this app needs it.

Firstlight has no charts. When the app needs charts, offer `smronju/nativephp-charts` from the index, with the community notice, and say charts come from that plugin.

## 4. Collect the essentials

Show one table of proposed values and let the developer change any row by reply.

- **App name** (`APP_NAME`): from the description. If it is longer than about 12 characters, suggest a short name, because the home screen truncates it.
- **App ID** (`NATIVEPHP_APP_ID`): `com.<vendor>.<app>`, from the Composer vendor and the app name. NativePHP uses this one ID as both the iOS bundle ID and the Android application ID, so it must suit both: lowercase letters, digits and dots only, at least two segments, each starting with a letter. Say that the stores fix it at the first upload.
- **Deep link scheme** (`NATIVEPHP_DEEPLINK_SCHEME`): the app slug, lowercase letters and digits. Plugins whose `setup` mentions it need it.
- **Brand colour:** offer three colours that suit the description, or take the developer's. Pick `on-primary` and check the pair is at least 4.5:1 contrast; when it fails, suggest the nearest shade that passes. Dark mode is derived unless the developer asks to tune it.
- **Appearance** (`NATIVEPHP_APPEARANCE`): `system`.
- **Icon:** the path to a square PNG of at least 1024 × 1024 with no transparency, or none for a placeholder in the brand colour that the developer replaces before release.
- **Splash:** a placeholder in the brand colour, which the developer can replace. Offer an animated splash plugin only when the developer asks for one.
- **Locale:** `APP_LOCALE` and `APP_FALLBACK_LOCALE`. Firstlight's own chrome ships in de, en, es, fr, it, ja, nl, pl, pt_BR and zh_CN; for another locale, say its chrome strings need translating (https://firstlightui.dev/docs/how-to/localize).
- **Timezone:** the developer's IANA timezone, such as `Australia/Sydney`. Date and Time pickers follow it.
- **Minimum OS:** iOS 18.2 and Android API 29 (`NATIVEPHP_ANDROID_MIN_SDK=29`), raised to the highest `ios.min` or `android.min` among the chosen plugins.
- **Permission text:** one sentence for each usage description the chosen controls and plugins need, worded for this app, such as "Fieldwork uses the camera to add photos to a job." Media's camera needs `NSCameraUsageDescription`; plugins list theirs under `usage_descriptions`. These go in `permissions` in `config/nativephp.php`.

## 5. Confirm the plan

Write the plan to `firstlight-starter.json` in the app root, in the shape of `vendor/firstlightui/nativephp/resources/starter/firstlight-starter.example.json`:

- One `records` entry per main record, named as a singular StudlyCase model such as `WorkOrder`. Avoid names whose table Laravel already uses, such as `Job`, `User` or `Session`.
- One `screens` entry per screen, giving its recipe id and, for record screens, its record. Give each list both `how-to/build-a-searchable-list-screen` and `how-to/build-a-list-detail-flow`.
- `accepted: true` only on plugins the developer said yes to.

Show the plan and wait for the developer to confirm it before you write anything else. Keep the file in the app as the record of what was chosen.

## 6. Build it

Run `php artisan firstlight:starter`. It checks the plan and lists every problem at once; fix the plan with the developer and run it again. Then it:

- Installs and registers each accepted plugin. A paid plugin downloads only for an account that has bought it, with the buyer's email and licence key in Composer's auth. Never ask for the key, read it, print it or write it to a file. When Composer refuses, the command leaves the plugin out, restores `composer.json` and `composer.lock`, and puts the purchase steps in `STARTER.md`.
- Writes the app name, app ID, colours, locale, timezone and minimum SDK to `.env` and `.env.example`, makes `config/app.php` read `APP_TIMEZONE`, sets `primary` and `on-primary` in `config/native-ui.php`, and adds the permission text to `config/nativephp.php`.
- Draws a placeholder icon and splash when none exist, and copies the plan's `icon` when it is a square PNG of at least 1024 × 1024.
- Writes `AppLayout` with native chrome and the tabs, then each record's model, migration, factory, Policy and seeder, and each screen's class, view and Pest test from its recipe. It registers the routes in `routes/mobile.php` with create routes before record routes.
- Writes `STARTER.md` with each screen and its recipe, the installed plugins (with the community notice), the screens to build next, and what is left for the developer.

Running it again is safe: it leaves files the developer has changed alone and lists them, unless you pass `--force`. Use `--skip-plugins` when you have installed the plugins yourself.

Then build each screen under "Build next" in `STARTER.md` by hand from its recipe, renamed to its record, with a Pest test in `tests/Feature/NativeComponents/` covering its main path and its failure path. Wire each accepted plugin that no generated screen uses as its recipe shows, for example App Guard's `app-lock` middleware and lock screen.

## 7. Verify and hand over

- Run `php artisan migrate --seed` and the test suite. Fix failures before continuing.
- Add to `STARTER.md` anything the command couldn't know: the back end and sign-in, and push keys if they chose push.
- Ask the developer to run the app on an iOS simulator and an Android emulator with `php artisan native:run` and check the large title, back navigation, tabs, field errors and deletion on both.
- Summarise what was created and suggest the next screen to build. From here, follow the `building-firstlight-screens` skill.

## Out of scope

Generating a back end, API or authentication, submitting to the stores, and installing any plugin the developer did not accept by name.
