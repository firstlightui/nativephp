---
title: Build with a coding agent
description: What the Boost guideline, the firstlight-init and building-firstlight-screens skills, and llms.txt give your coding agent, and how to set them up.
type: how-to
audience: consumer
sources:
  - resources/boost/guidelines/core.blade.php
  - resources/boost/skills/firstlight-init/SKILL.md
  - resources/boost/skills/building-firstlight-screens/SKILL.md
  - llms.txt
  - llms-full.txt
  - src/Console/MakeScreenCommand.php
---

# Build with a Coding Agent

Firstlight is written to be built with by coding agents. The package ships a
[Laravel Boost](https://laravel.com/docs/boost) guideline and two Boost skills,
and its documentation is published as `llms.txt` and `llms-full.txt`. Together
they tell an agent which screens to build from which recipe, which control to
use for each value, and which mistakes to avoid.

## Set up Boost

Install Firstlight as described in [Installation](installation.md), then add
Laravel Boost to the application and run its installer:

```bash
composer require laravel/boost --dev
php artisan boost:install
```

Boost finds the guideline and skills in Firstlight's `resources/boost`
directory and writes them into your agent's files, such as `AGENTS.md`,
`CLAUDE.md`, and its skills directory. If Boost is already installed, pick up
Firstlight with:

```bash
php artisan boost:update --discover
```

Run `php artisan boost:update` after each Firstlight update so the guideline
and skills match the installed version.

## What your agent gets

| Resource | When the agent reads it | What it contains |
| --- | --- | --- |
| Guideline | Always, as part of the agent's instructions | The rules every Firstlight screen follows, the six recipes, the screen generator, and a tested example |
| `firstlight-init` skill | When asked to set up or scaffold a Firstlight app | An interview, an install check, the theme, the native-chrome layout, and the chosen recipe screens with tests |
| `building-firstlight-screens` skill | When adding or changing a screen, form, list, or settings page | Which recipe to start from, which control fits each value, the workflow, and what never to do |
| [`llms.txt`](https://firstlightui.dev/llms.txt) | When the agent needs a page | Every documentation page with a one-line summary |
| [`llms-full.txt`](https://firstlightui.dev/llms-full.txt) | When the agent needs the whole reference | The full text of every documentation page in one file |

The package also includes `llms-full.txt` at
`vendor/firstlightui/nativephp/llms-full.txt`, so an agent can read the
reference for the installed version without network access.

### The guideline's rules

The guideline keeps an agent on the same path as the recipes:

- Screens extend `FirstlightUI\NativeComponent`.
- Controls are `<firstlight:*>` tags, with one Blade view for both platforms.
- Navigation chrome comes from NativePHP: layouts return `true` from
  `usesNativeChrome()`. No custom-drawn tab bars, drawers, charts, or carousels.
- Validation is Laravel validation, with `$rules` or a Form Request passed to
  `$this->validate()`. Fields show their own errors.
- Destructive actions open a Confirmation Dialog and authorize in PHP.
- Colours come from the NativePHP theme, not from markup.

## Ask for an app

With Boost installed, describe the app rather than the controls. For example:

> Set up this app with Firstlight. The main record is a job. I want a
> searchable list of jobs, a form to create one, and a settings screen.

The `firstlight-init` skill asks about the record, the recipes, brand colours,
tabs, deletion rules, and locale one question at a time, and makes no change
you have not agreed to. It then scaffolds each chosen
[recipe](../how-to/build-a-list-detail-flow.md) with a Pest test and runs the
test suite.

For later screens, ask for the outcome, such as "add a screen to edit a job".
The `building-firstlight-screens` skill picks the matching recipe, or starts
from the generator:

```bash
php artisan make:firstlight-screen EditJob --form --test
```

## What still needs a person

An agent can write screens and run their Pest tests. It cannot see the app on
a device. Both skills end by asking you to run the app on an iOS simulator and
an Android emulator and check navigation, field errors, and deletion on both.
Check accessibility too. See [Accessibility](../concepts/accessibility.md) for
what Firstlight covers and what your app still owns.
