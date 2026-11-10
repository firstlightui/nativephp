---
title: Use custom icons
description: Install SVGs from Lucide, Heroicons, Font Awesome and other sets with firstlight:icon, and draw them as native icons in Firstlight controls.
type: how-to
audience: consumer
sources:
  - src/Console/InstallIconCommand.php
  - src/FirstlightServiceProvider.php
  - tests/Feature/InstallIconCommandTest.php
  - resources/ios/NativePHPTestShims.swift
  - resources/ios/ButtonControl.swift
  - resources/ios/IconButtonControl.swift
  - resources/ios/ListItemControl.swift
  - resources/ios/PillGroupControl.swift
  - resources/ios/StatusLabelControl.swift
  - resources/ios/TextFieldControl.swift
  - composer.json
---

# Use Custom Icons

SF Symbols and Material Icons cover most actions, but not your brand mark, a
domain object or an icon from your design system. NativePHP Mobile builds
SVG files in `resources/icons` into native icons: a template image in the iOS
asset catalogue and a VectorDrawable on Android. You refer to each one as
`custom:<name>`.

Firstlight draws custom icons wherever it accepts an authored icon: Button,
Icon Button, List Item (leading and trailing icons, swipe actions and context
menus), Pill Group options, Status Label and Text Field. They take the
control's colour, like system icons.

## Install icons

Install icons from any [Iconify](https://icon-sets.iconify.design/) set with
`set:name`:

```bash
php artisan firstlight:icon lucide:git-commit-vertical heroicons:rocket-launch-solid tabler:plant-2
```

Each icon is written to `resources/icons/<name>.svg`, so these become
`custom:git-commit-vertical`, `custom:rocket-launch-solid` and
`custom:plant-2`. Useful sets include `lucide`, `heroicons`, `tabler`, `ph`
(Phosphor), `material-symbols`, `mdi`, and Font Awesome Free as `fa7-solid`,
`fa7-regular` and `fa7-brands`.

You can also install an SVG file or URL:

```bash
php artisan firstlight:icon ~/Downloads/logo.svg --as=brand
php artisan firstlight:icon https://example.com/icons/route.svg
```

| Option | Effect |
| --- | --- |
| `--as=<name>` | Installs a single icon under this name. |
| `--force` | Replaces icons that are already installed. |

The command checks each SVG with NativePHP's own converter before writing it,
so an icon the native build would reject is never installed. The error names
the element and attribute to fix, such as a `transform` or a gradient. An icon
that already exists is skipped without being downloaded. If any icon fails, the
others are still installed and the command exits with status 1.

Names must be lowercase kebab-case. When the source name isn't, such as
`Rocket Launch.svg`, pass `--as`.

The first line of each file records where it came from, such as
`<!-- lucide:git-commit-vertical from Iconify. Set and licence: https://icon-sets.iconify.design/lucide/ -->`.
Check the licence of every set you use, and credit it the way the licence asks.

## Install Font Awesome Pro icons

Create an API token with your Font Awesome account at
[fontawesome.com/account/general](https://fontawesome.com/account/general) and
put it in `.env` as `FA_API_TOKEN`, the variable the Font Awesome CLI uses.
Never commit the token.

```env
FA_API_TOKEN=your-api-token
```

Then name the style and icon, or the family, style and icon:

```bash
php artisan firstlight:icon fontawesome:light/rocket-launch fontawesome:duotone/solid/kiwi-bird fontawesome:sharp/regular/house
```

The family defaults to `classic`. Icons come from the latest Font Awesome 7
release. Font Awesome's API only returns the styles your plan includes.
Duotone icons keep their two layers as one colour at two opacities.

The [Font Awesome Pro licence](https://fontawesome.com/license) lets you embed
Pro icons in your app, but not give others permission to reuse them. Keep Pro
SVGs out of public or openly licensed repositories.

## Use an icon

Pass `custom:<name>` to any icon attribute:

```blade
<firstlight:icon-button icon="custom:rocket-launch-solid" a11y-label="Launch" @press="launch" />

<firstlight:list-item
    headline="Greenhouse"
    leading-icon="custom:plant-2"
    trailing-icon="chevron-right"
    trailing-icon-ios="chevron.right"
    trailing-icon-android="chevron_right"
    @press="openGreenhouse"
/>

<firstlight:text-field label="Branch" native:model="branch" leading-icon="custom:git-commit-vertical" />
```

Platform overrides accept custom icons too, so you can use your icon on one
platform and a system icon on the other:

```blade
<firstlight:list-item headline="Pets" leading-icon="pets" leading-icon-ios="custom:paw-print-fill" @press="openPets" />
```

In PHP, `Native\Mobile\Icon\Icon::custom('plant-2')` returns `'custom:plant-2'`.

## Build the app

Icons are compiled into the native project, so adding or changing an SVG needs
a native build. Run `php artisan native:run` to see it; hot reload does not pick
up icon changes.

A build without the icon, such as Jump or a build made before you installed it,
draws the system icon with the same name instead. `custom:bell` shows the
system bell, so naming an icon after the system icon it replaces gives a
sensible fallback.

## Check that the icon renders

Open the screen on iOS and Android in light and dark appearance. The icon
should take the control's colour and sit at the same size as the system icons
beside it. The [showcase app](https://github.com/firstlightui/showcase) has a
Custom Icons screen with every supported control.

Custom icons are decorative, like system icons: VoiceOver and TalkBack read
the control's label, not the icon. Give icon-only controls an `a11y-label`.
