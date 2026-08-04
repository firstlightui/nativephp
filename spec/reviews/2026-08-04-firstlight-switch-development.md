---
title: Firstlight Switch development review
description: Constitutional implementation, test, consumer, and host-build evidence for Switch with visual evidence deferred.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-switch-design.md
  - docs/components/switch.md
  - nativephp.json
  - src/Elements/SwitchControl.php
  - resources/ios/SwitchRenderer.swift
  - resources/android/SwitchRenderer.kt
---

# Firstlight Switch Development Review

Date: 2026-08-04

Mode: development

Package revision reviewed: `cadcfab`

Showcase revision reviewed: `66985e0`

Overall development verdict: **BLOCKED**. Implementation, package gates,
consumer tests, and both showcase host builds pass. The maintainer deferred the
four screenshots. Manual VoiceOver, TalkBack, appearance, accessibility-setting,
and interaction rows were therefore not performed. The iOS 18.6 XCTest runner
also stalled after compilation while starting its first test and was stopped.

Component-release readiness is **BLOCKED** by those gaps, missing dated
physical-device evidence, and the unreleased NativePHP identical-publication
prerequisite. Catalogue and public-alpha readiness are separately **BLOCKED**;
this review covers one component, not the complete catalogue.

## Constitutional verdicts

### Article I — SuperNative first: PASS

The EDGE element, renderer registry, shared-memory tree, and official native
toggle-change event are used. There is no WebView, JSON bridge, or parallel
state transport.

### Article II — Familiar and coherent APIs: PASS

The public API uses strict `value`, `native:model`, field metadata, accessibility
props, external `class`, and `@change`. Unsupported props and deferred bindings
fail actionably. The reserved-word `SwitchControl` name remains internal.

### Article III — Stable values and predictable state: PASS

Both renderers keep the last published boolean, emit one inverse proposal per
publication, clear pending state after every publication, preserve rejection,
and never echo programmatic changes. PHP, Swift, Kotlin, and showcase tests cover
these branches.

### Article IV — Equal platform quality: PASS

Both production renderers implement the shared contract. The exact package
revision compiled into successful iOS and Android showcase host builds.

### Article V — Native expression over pixel parity: PASS

iOS uses SwiftUI `Toggle`; Android uses Material 3 `Switch`. Shared behaviour
does not impose shared geometry.

### Article VI — Accessibility is correctness: BLOCKED

Automated evidence covers labels, hints, state, disabled/error semantics,
accessibility-size layout, Android 48-dp targeting, one `Role.Switch` focus
target, and 2.0 font scale. Manual VoiceOver, TalkBack, increased contrast,
Reduced Motion, RTL, and announcement inspection were deferred.

### Article VII — System-first theming: PASS

Both renderers use host semantic tokens and platform typography. No arbitrary
control styling API is exposed.

### Article VIII — Small, proven expansion: PASS

The approved persistent-boolean intent is implemented as one component and
dogfooded through broad automated showcase fixtures; the manual RTL and
accessibility rows remain blocked below.

### Article IX — Evidence-based quality: BLOCKED

Automated and host-build evidence passes, but the required screenshot matrix,
manual native-feel/accessibility rows, and fresh complete iOS runtime pass are
absent. Earlier focused Switch iOS contract and iOS 18.6 snapshot runs passed,
but they do not replace a complete run on this exact revision.

### Article X — Public alpha stewardship: BLOCKED

The API discipline passes. Release readiness does not: no tag, publication, or
alpha claim is authorized by this review.

### Article XI — Skills enforce the constitution: PASS

The recorded component, platform, docs, screenshot, and review workflow used
fresh reviews for each implemented layer, and missing evidence was not softened
into a pass.

## Passing evidence

```text
composer test
PASS — 245 tests, 788 assertions; 5 model-backed evals skipped by design

composer validate --strict
PASS

bin/check-component SwitchControl --development
PASS

composer run docs:check
PASS — development mode explicitly defers absent images

git diff --check
PASS

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android testDebugUnitTest --no-daemon
PASS — BUILD SUCCESSFUL

php artisan test tests/Feature/SwitchShowcaseTest.php tests/Feature/SwitchCaptureTest.php
PASS — 3 tests, 66 assertions

php artisan test
PASS — 44 tests, 610 assertions

php artisan native:plugin:validate /private/tmp/clinically-au/firstlight-ui
PASS with expected warning — no custom bridge_functions; Switch uses the
official native UI event bridge

php artisan native:build --target=1EB9538D-1622-432A-AD54-0654EACCEA13 \
  --simulated --no-tty
PASS — iPhone 16 Pro / iOS 18.6 host BUILD SUCCEEDED without app launch

php artisan native:run android emulator-5554 --no-tty --start-url=/switch
PASS — Pixel 9 Pro / API 36 host built, installed, launched, and was confirmed
foreground; the headless emulator was then shut down
```

The Composer path package was forcibly reinstalled. Its stable path-package
lock reference is not presented as a Git revision; installed Switch PHP,
manifest, iOS renderer, and Android renderer files were byte-identical to the
reviewed package revision. NativePHP's copied-app iOS build otherwise cannot
resolve the development-only sibling path after bundling.

## Deferred and blocked evidence

- All four `docs/screenshots/switch/` images and visual approval.
- Manual simulator accessibility, appearance, and interaction matrix.
- Physical-device iOS and Android evidence required before release.
- Fresh complete iOS XCTest runtime pass: the runner stalled during its first
  test after compiling successfully.
- Publication of NativePHP identical-tree response revisions.

## Honest milestone

Switch is implementation-complete, consumer-tested, and host-build-proven on
both platforms. It is not development-proven, component-release-ready, or
alpha-ready until the deferred evidence and upstream dependency are closed.

## Development screenshot evidence update — 2026-08-05

Switch's current four-image development matrix is present and visually
approved in the [alpha catalogue screenshot evidence](2026-08-05-alpha-screenshot-evidence.md).
This supersedes only earlier pending or absent screenshot statements. Clean
release capture, physical-device, assistive-technology, and component-specific
blocked rows remain unchanged.
