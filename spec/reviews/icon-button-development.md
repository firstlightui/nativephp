---
title: Icon Button development review
description: Constitutional implementation, test, showcase, and current blocked evidence for Firstlight Icon Button.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/components/icon-button.md
  - spec/reference/icons.md
  - docs/components/icon-button.md
  - nativephp.json
  - src/Elements/IconButton.php
  - resources/ios/IconButtonRenderer.swift
  - resources/android/IconButtonRenderer.kt
  - tests/Feature/IconButtonElementTest.php
  - tests/ios/IconButtonSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/IconButtonTest.kt
---

# Icon Button Development Review

Date: 2026-08-04

Mode: development

Package implementation revision reviewed: `947bad1128ea1ee4c92bb651e624531892cd5b96`

Showcase revision reviewed: `fce8f86`

Implementation verdict: **BLOCKED**. The public contract, PHP/EDGE element,
paired native renderers, focused and full PHP tests, full Android library suite,
showcase fixtures, consumer tests, documentation, and manifest validation pass.
The iOS test target has not been compiled on this exact revision because the
controller retained sole simulator access. Android also decodes and preserves
the shared `icon_variant` wire value, but the installed Mobile UI
`MaterialIcon` API has no font-variant argument, so an authored typed outlined
symbol is not yet visually distinguished from its filled counterpart.

Development milestone verdict: **BLOCKED**. The user authorized bypassing
screenshot failures, but that does not convert missing iOS compilation, manual
accessibility checks, Android variant rendering, or host-build evidence into a
pass.

Component-release and public-alpha verdicts are separately **BLOCKED** by the
same gaps, absent dated physical-device evidence, absent approved screenshots,
and the incomplete catalogue.

## Constitutional verdicts

### Article I — SuperNative first: PASS

The public EDGE element, plugin manifest, shared native tree, standard root
press callback, and paired package renderers use official NativePHP seams. No
WebView, JSON bridge, or parallel transport was introduced.

### Article II — Familiar and coherent APIs: PASS

`icon`, platform overrides, semantic variants, familiar sizes, strict
`disabled` and `loading` booleans, required `a11y-label`, optional
`a11y-hint`, external layout `class`, and standard `@press` form one narrow
compact-action API. Display-only glyphs remain the responsibility of Icon.

### Article III — Stable values and predictable state: PASS

Icon Button has no application value or optimistic model. PHP owns the action
outcome; native renderers own transient press feedback. Automated state tests
prove that disabled, loading, and missing-callback states suppress activation,
while server publication updates a stable node without emitting an event.

### Article IV — Equal platform quality: BLOCKED

Both production renderers implement the same action, semantic variant, size,
state, target, and accessibility contract. Android compiles in the full
library suite. Exact-revision iOS compilation is still absent, and the current
Android icon helper cannot visually consume the preserved Material font
variant.

### Article V — Native expression over pixel parity: PASS

iOS uses SwiftUI `Button`, circular button borders, and `ProgressView`.
Android uses Material 3 `IconButton`, `FilledTonalIconButton`,
`FilledIconButton`, and `CircularProgressIndicator`. Shared semantics do not
force identical platform geometry.

### Article VI — Accessibility is correctness: BLOCKED

Automated contracts cover an explicit non-empty action name, optional hint,
one button node, decorative glyphs, disabled/loading state, 44-point iOS and
48-dp Android targets, and Android font scale 2.0. Manual VoiceOver, TalkBack,
RTL, contrast, large-text, announcement, focus, and physical-device rows were
not performed.

### Article VII — System-first theming: PASS

Both renderers use NativePHP semantic tokens and platform-native control
families. The public contract exposes no arbitrary colour, background, font,
line-height, or geometry escape hatch.

### Article VIII — Small, proven expansion: PASS

The official Button, Icon, and List Item implementations were audited first.
None supplies a standalone compact action with this full disabled, loading,
target, and accessibility contract, so the paired renderer expansion remains
one deliberately narrow component.

### Article IX — Evidence-based quality: BLOCKED

TDD, PHP, Android, showcase, docs, and manifest evidence pass. iOS compilation,
consumer host builds, controller-run interaction checks, and the screenshot
matrix are absent. The review records those gaps instead of inferring parity.

### Article X — Public alpha stewardship: BLOCKED

No release tag, publication, or alpha-readiness claim is authorized. One
component cannot complete the catalogue release gate.

### Article XI — Skills enforce the constitution: PASS

The component, iOS, Android, and review skills were followed. The controller's
exclusive emulator lane was respected, and missing runtime evidence remains
explicitly blocked.

Article XII is not applicable; Icon Button requires no constitutional
amendment.

## Passing evidence

```text
./vendor/bin/pest tests/Feature/IconButtonElementTest.php \
  tests/Feature/PluginManifestTest.php
PASS — 42 tests, 102 assertions

composer test
PASS — 323 tests, 932 assertions; 5 model-backed evals skipped by design

composer validate --strict
PASS

git diff --check
PASS

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android testDebugUnitTest
PASS — BUILD SUCCESSFUL

bin/build-docs-artifacts
PASS

bin/check-component IconButton --development
PASS

php artisan test tests/Feature/IconButtonShowcaseTest.php \
  tests/Feature/IconButtonCaptureTest.php \
  tests/Feature/ShowcaseNavigationTest.php
PASS — 11 tests, 128 assertions

composer test
PASS in firstlight-showcase — 48 tests, 668 assertions

php artisan native:plugin:validate /Users/wojt/Code/clinically-au/firstlight-ui
PASS with expected UI-only warning — no bridge_functions are declared
```

The showcase lock resolves the path package to the full package implementation
revision `947bad1`. Direct hashes for the installed Icon Button PHP element and
paired renderer sources match the package files. Composer's path mirror also
contained unrelated in-flight Badge work, so this review does not claim the
entire installed vendor tree is byte-identical to `947bad1`; that work was not
staged in either Icon Button commit.

## Red test evidence

The scaffold was generated once, then the real PHP contract test was authored
and run before implementation. The first focused run failed all 40 cases
because the scaffold class was not yet an EDGE element and lacked `make()`,
precompiler registration, and manifest registration. The same contract now
passes in both focused and full runs.

## Deferred and blocked evidence

- Exact-revision iOS compile and XCTest/snapshot run. Plain `swift test` cannot
  build this iOS-only package on macOS and failed at the existing UIKit import.
- Android outlined-versus-filled visual expression until the installed
  `MaterialIcon` API accepts a variant or Firstlight gains an equivalent
  supported rendering seam.
- Generated iOS and Android showcase host builds.
- Controller-run simulator interaction, appearance, RTL, and accessibility
  checks.
- Four documentation screenshots and explicit visual approval. Screenshot
  capture was intentionally bypassed under the user's stated exception.
- Dated physical-device VoiceOver, TalkBack, offline, reconciliation, and
  rapid-input evidence required before release.

## Honest milestone

Icon Button is on main with a consumer fixture and broad off-device automated
coverage. It is not implementation-complete, development-proven,
component-release-ready, or alpha-ready until the blocked rows above close.

## Controller runtime evidence — 2026-08-04

This section supersedes the earlier generated-host and Android-runtime pending
rows. The final showcase lock at `a07da05` resolved the complete package at
`b7cb3f9`. Android debug built, installed, and clean-launched on Pixel 9 Pro;
the same lock then passed `assembleRelease`. iOS debug reported `BUILD
SUCCEEDED`, clean-installed, and launched the catalogue on iPhone 17 Pro.

On Android, Icon Button rendered its primary, secondary, destructive,
selected, overflow, disabled, loading, and press-recording cases. The
accessibility tree exposed the authored action names, and tapping Record press
changed the PHP-backed count from 0 to 1. The iOS host compiled the complete
renderer set, but direct component routing and canonical capture were waived
under the task instruction; this is not represented as iOS component-runtime
or screenshot evidence. Exact iOS XCTest, VoiceOver, full TalkBack, RTL,
appearance/scaling matrices, and physical-device evidence remain release
requirements.

Final shared gates passed: package 748 tests/2,160 assertions with five model
evaluations skipped; Android 131 tests with zero failures/errors and seven
controller-gated skips; showcase 93 tests/1,467 assertions; both strict
Composer validations; docs build/check; all ten development component checks;
plugin validation with the expected UI-only no-bridge-functions warning; and
both repository diff checks.

## Development screenshot evidence update — 2026-08-05

Icon Button's current four-image development matrix is present and visually
approved in the [alpha catalogue screenshot evidence](2026-08-05-alpha-screenshot-evidence.md).
This supersedes only earlier pending or absent screenshot statements. Clean
release capture, physical-device, assistive-technology, and component-specific
blocked rows remain unchanged.
