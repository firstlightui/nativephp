---
title: Activity Indicator development review
description: Constitutional review of the Firstlight Activity Indicator implementation, native hosts, and approved documentation evidence.
status: blocked
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-05-firstlight-activity-indicator-design.md
  - spec/components/activity-indicator.md
  - docs/components/activity-indicator.md
  - nativephp.json
  - src/Elements/ActivityIndicator.php
  - resources/ios/ActivityIndicatorRenderer.swift
  - resources/android/ActivityIndicatorRenderer.kt
  - tests/Feature/ActivityIndicatorElementTest.php
  - tests/ios/ActivityIndicatorSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/ActivityIndicatorTest.kt
---

# Activity Indicator Development Review

Reviewed on 2026-08-05 against package revision
`e86b422` and showcase revision `f5eae8f` on `main`. The guarded native
capture ran against package revision `e1c18ea` and the same showcase revision;
`e86b422` adds only the four approved output images.

**Off-device implementation verdict:** PASS

**Development verdict:** BLOCKED

**Component-release verdict:** BLOCKED

Both production renderers, exact iOS tests, current Android tests, generated
showcase host builds, light/dark native runtime captures, documentation, and
visual approval pass. Development remains blocked because manual VoiceOver and
TalkBack announcement/focus behaviour, Increased Contrast, RTL, and Reduced
Motion behaviour have not been observed. Dated physical-device evidence also
remains mandatory for component release.

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative first | PASS | The EDGE element and paired renderers use the official manifest, Element Tree, shared publication, and renderer lifecycle. No WebView, JSON bridge, generated-host edit, or alternate transport was introduced. |
| II. Familiar and coherent APIs | PASS | The public API is required `a11y-label`, semantic `sm\|md\|lg` size with `md` default, and external layout `class`. Presence is the activity state, so conditional Blade or Livewire rendering controls lifecycle without `loading`, `active`, `visible`, or `wire:loading` props. |
| III. Stable values and predictable state | PASS | The display-only indicator has no value, proposal, or callback. Reconciliation updates presentation without repeating the iOS mount announcement; removal and remount create a new lifecycle. |
| IV. Equal platform quality | PASS | SwiftUI and Material 3 implementations consume the same size, theme, and accessibility contract. Both generated showcase hosts built, installed, launched, reached the capture route, and produced approved light/dark evidence. |
| V. Native expression over pixel parity | PASS | iOS uses SwiftUI `ProgressView` and native control sizes. Android uses Material 3 `CircularProgressIndicator` with platform-appropriate 20/32/48 dp geometry. The approved captures show intentional native visual differences. |
| VI. Accessibility is correctness | BLOCKED | The contract requires a non-empty accessible name; Android exposes a polite live region with no action, and iOS guards one announcement per mount without an interaction role. Automated semantics pass, but manual VoiceOver/TalkBack announcement, focus retention, remount, Increased Contrast, RTL, and Reduced Motion rows remain open. |
| VII. System-first theming | PASS | Both renderers use the current NativePHP theme primary colour and native control families. No public colour, stroke, animation, or arbitrary style escape hatch exists. |
| VIII. Small, proven expansion | PASS | Activity Indicator remains separate from determinate Progress and is restricted to indeterminate busy feedback. The paired renderers address the installed primitive's unequal announcement behaviour. |
| IX. Evidence-based quality | BLOCKED | PHP, iOS, Android, docs, manifest, structural, showcase, native-host, and approved screenshot evidence pass. Manual accessibility-mode observations and physical-device release rows remain incomplete. |
| X. Public alpha stewardship | BLOCKED | No tag, publication, release-readiness claim, or catalogue-wide alpha claim is authorized. The remaining accessibility and physical-device gates still apply. |
| XI. Skills enforce the constitution | PASS | The component, iOS, Android, screenshot, documentation, TDD, debugging, verification, and constitutional-review workflows were applied. Device control used only the explicitly authorized iPhone and Pixel emulator lane. |

Article XII is not applicable because Activity Indicator requires no
constitutional amendment.

## Passing evidence

```text
composer test
PASS — 832 tests, 2,385 assertions; 5 model-backed evals skipped by design

composer validate --strict
PASS

bin/check-component ActivityIndicator --development
PASS

bin/build-docs-artifacts
PASS

bin/check-docs --development
PASS

php vendor/bin/pest tests/Feature/DocumentationScreenshotCaptureTest.php
PASS — 14 tests, 82 assertions

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android testDebugUnitTest
PASS — 152 test cases, 12 controller-gated skips, 0 failures, 0 errors

recordPaparazziDebug and verifyPaparazziDebug for ActivityIndicatorScreenshotTest
PASS — small, medium, and large indicators recorded and verified

xcodebuild focused ActivityIndicatorSnapshotTests on iPhone 17 Pro, iOS 26.5
PASS — 8 tests, 0 failures

php artisan test --compact in firstlight-showcase
PASS — 100 tests, 1,604 assertions

php artisan test tests/Feature/ActivityIndicatorCaptureTest.php --compact
PASS — 1 test, 7 assertions

php artisan native:plugin:validate /Users/wojt/Code/clinically-au/firstlight-ui
PASS with expected UI-only warning — no bridge_functions are declared
```

The full iOS package target also compiled and executed 146 tests after an
unrelated existing Swift 6 explicit-capture issue in the Icon Button snapshot
fixture was corrected. Remaining full-suite failures were pre-existing Badge,
Icon Button, Pill Group, Search Field, and Text Area snapshot/search evidence
failures; the focused Activity Indicator suite passed all eight tests.

## Native host and documentation evidence

The maintainer explicitly authorized exclusive control of:

- iPhone 17 Pro simulator, iOS 26.5,
  `EB44C64E-1579-4C13-A1F9-C44FBD496763`;
- Pixel 9 Pro Android emulator, `emulator-5554`.

The generated iOS host built, installed, and launched the stable
`/captures/activity-indicator` route. The guarded paired workflow also built,
installed, launched, foreground-checked, and readiness-checked the Android
host. The first Android retry exposed `INSTALL_FAILED_INSUFFICIENT_STORAGE`
for the 975 MB debug APK. Only the replaceable `dev.firstlightui.showcase` app
and emulator caches were removed; the emulator was not wiped. The retry
completed successfully with 2.6 GB free.

The guarded command completed atomically:

```text
bin/capture-doc-screenshots ActivityIndicator \
  --showcase=../firstlight-showcase \
  --ios=EB44C64E-1579-4C13-A1F9-C44FBD496763 \
  --android=emulator-5554
PASS
```

The capture guard now compares each frame with a bounded recent history while
retaining the existing 0.01% visual-difference threshold. Its regression test
proved a recurring animated phase is accepted even when adjacent frames keep
changing. In the real run, iOS settled on recurring native spinner phases;
Android light was byte-stable and Android dark settled visually.

The four published outputs are:

- `docs/screenshots/activity-indicator/ios-light.png`
- `docs/screenshots/activity-indicator/ios-dark.png`
- `docs/screenshots/activity-indicator/android-light.png`
- `docs/screenshots/activity-indicator/android-dark.png`

All four were inspected for full viewport, light/dark appearance, native
platform expression, semantic size ordering, labels, clipping, and truncation.
The maintainer explicitly approved the complete matrix on 2026-08-05.

## Red test evidence

The PHP contract failed first because the component, element, precompiler, and
manifest entries did not exist. Android contract and manifest tests likewise
failed before their paired renderer existed. Showcase tests failed before the
gallery, stable capture route, and navigation entry were added.

The screenshot-guard regression failed against the adjacent-frame-only
implementation by requesting a fourth frame after a three-frame recurring
cycle. The bounded-history implementation then passed the focused regression
and the complete 14-test capture suite.

## Remaining evidence

- VoiceOver: one polite announcement on mount, no focus steal, no repeat on
  reconciliation, and a new announcement after removal and remount.
- TalkBack: authored name, polite live-region announcement, no action, stable
  focus, and correct mount/remount behaviour.
- Manual Increased Contrast, RTL, and Reduced Motion observations on both
  approved runtime targets.
- Dated physical-device VoiceOver, TalkBack, offline, theme, reconciliation,
  motion, and host-stability rows required for component release.

## Honest milestone

Activity Indicator is implemented, documented, installed in the showcase,
running on both approved native hosts, and represented by an explicitly
approved documentation matrix. It is not development-complete until the
manual accessibility rows pass, and it is not component-release-ready or
public-alpha-ready until the physical-device and catalogue-wide gates pass.
The roadmap hit-list item therefore remains open.
