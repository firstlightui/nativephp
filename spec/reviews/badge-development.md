---
title: Badge development review
description: Constitutional implementation, test, showcase, and current blocked evidence for Firstlight Badge.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/components/badge.md
  - docs/components/badge.md
  - nativephp.json
  - src/Elements/Badge.php
  - resources/ios/BadgeRenderer.swift
  - resources/android/BadgeRenderer.kt
  - tests/Feature/BadgeElementTest.php
  - tests/ios/BadgeSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/BadgeTest.kt
---

# Badge Development Review

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-08-04 |
| Component | `Badge` / `<firstlight:badge>` |
| State class | Action/display; display-only and inert |
| Implementation | Paired SwiftUI and Material 3 renderers |
| Package revision reviewed | `9a5d5b46a482c1ca395c957b73b1b682b00aad44` |
| Showcase revision reviewed | `e868ffc` |
| Android snapshot environment | Paparazzi Pixel 5 configuration; no emulator used |

Implementation verdict: **BLOCKED**. The public contract, PHP/EDGE element,
paired native renderer source, full PHP suite, Android compilation and
Paparazzi evidence, exact-revision showcase, documentation, and plugin
validation pass. The iOS test target has not yet compiled on the controller's
new exact simulator target, and consumer host builds and manual accessibility
checks are not yet recorded.

Development milestone verdict: **BLOCKED**. The user authorized bypassing
screenshot capture issues, but that exception does not convert absent iOS
compilation, host-build, or manual accessibility evidence into a pass.

Component-release and public-alpha verdicts are separately **BLOCKED** by the
same gaps, absent dated physical-device evidence, absent approved screenshots,
and the incomplete catalogue.

## Constitutional verdicts

### Article I — SuperNative first: PASS

The public EDGE element and paired package renderers use the official NativePHP
manifest, Element Tree, stable node identity, and current-tree reconciliation
seams. No WebView, JSON bridge, or parallel transport was introduced.

### Article II — Familiar and coherent APIs: PASS

Exactly one strict `count` or `label`, semantic tones, accessibility metadata,
and external layout `class` form one narrow display-only API. Field,
interaction, arbitrary colour, and platform escape props fail explicitly.

### Article III — Stable values and predictable state: PASS

PHP normalizes count formatting once, including hidden zero and the `99+`
boundary. Badge owns no native proposal or mutable application state; later
server publications replace display metadata without emitting callbacks.

### Article IV — Equal platform quality: BLOCKED

Both renderers consume the identical primitive label, tone, and accessibility
contract. Android compiles and verifies its goldens. Exact-revision iOS
compilation is still absent.

### Article V — Native expression over pixel parity: PASS

iOS uses SwiftUI text in a compact capsule because SwiftUI's badge API is a
contextual modifier. Android uses the genuine Material 3 `Badge` primitive.
The shared semantics do not force identical platform geometry.

### Article VI — Accessibility is correctness: BLOCKED

Automated contracts prove contextual names for numeric counts, label fallback,
optional hints, inert semantics, hidden-zero output, native text scaling, and
theme contrast fallback. Manual VoiceOver, TalkBack, RTL, increased-contrast,
large-text, and physical-device rows have not been performed.

### Article VII — System-first theming: PASS

Both renderers reuse NativePHP semantic tokens and the shared Status Label tone
resolver. The public contract exposes no arbitrary colour, background, font,
line-height, or geometry escape hatch.

### Article VIII — Small, proven expansion: PASS

The installed Mobile UI Badge was audited first. Its destructive-only default,
visible zero, missing hint support, and custom Android composition cannot
satisfy Firstlight's strict neutral-capable display contract, so paired
renderers are justified.

### Article IX — Evidence-based quality: BLOCKED

TDD, PHP, Android, showcase, docs, manifest, and plugin evidence pass. iOS
compilation, generated consumer host builds, controller-run interaction checks,
and the screenshot matrix are absent and remain explicit gaps.

### Article X — Public alpha stewardship: BLOCKED

No release tag, publication, or alpha-readiness claim is authorized. The full
catalogue and shared release evidence remain incomplete.

### Article XI — Skills enforce the constitution: PASS

The component, platform, documentation, and review workflows were applied.
The controller's exclusive emulator lane was respected.

Article XII is not applicable because Badge requires no constitutional
amendment.

## Passing evidence

```text
vendor/bin/pest tests/Feature/BadgeElementTest.php \
  tests/Feature/PluginManifestTest.php \
  tests/Feature/DocumentationToolingTest.php
PASS — 51 tests, 89 assertions

composer test
PASS — 323 tests, 932 assertions; 5 model-backed evals skipped by design

composer validate --strict
PASS

git diff --check
PASS

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android recordPaparazziDebug \
  --tests dev.firstlightui.plugins.firstlight_ui.ui.BadgeScreenshotTest
PASS — BUILD SUCCESSFUL

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android verifyPaparazziDebug \
  --tests dev.firstlightui.plugins.firstlight_ui.ui.BadgeScreenshotTest
PASS — BUILD SUCCESSFUL

bin/check-docs --component=Badge --development
PASS

composer test
PASS in firstlight-showcase — 54 tests, 813 assertions

php artisan native:plugin:validate /Users/wojt/Code/clinically-au/firstlight-ui
PASS with expected UI-only warning — no bridge_functions are declared
```

The showcase lock resolves `firstlightui/nativephp` to the package revision
above. Its Badge gallery and isolated `/captures/badge` fixture are covered by
consumer-tree and accessibility tests. Pill Group and Switch were also restored
to the catalogue navigation without changing their component contracts.

## Red test evidence

The real PHP contract test was authored before implementation and initially
failed because Badge did not exist in the package, manifest, or precompiler.
The scaffold command then refused to run while the failing authored test path
existed. The test was temporarily removed, the scaffold was run exactly once,
and the authored test was immediately restored over the placeholder before any
implementation. All 38 initial contract failures now pass.

## Deferred and blocked evidence

- Exact-revision iOS compile and XCTest/snapshot run on the controller-approved
  current simulator target.
- Generated iOS and Android showcase host builds.
- Controller-run appearance, RTL, and accessibility checks.
- Four documentation screenshots and explicit visual approval. Capture may be
  bypassed if it fails under the user's stated exception, but the bypass must
  remain recorded rather than represented as passing visual evidence.
- Dated physical-device VoiceOver, TalkBack, offline, theme, and host-stability
  evidence required before release.

## Honest milestone

Badge is on main with exact consumer integration and broad off-device automated
coverage. It is not development-proven, component-release-ready, or alpha-ready
until the blocked rows above close or, for screenshot capture only, are
explicitly waived under the user's instruction.

## Controller runtime evidence — 2026-08-04

This section supersedes the earlier generated-host and Android-runtime pending
rows. The final showcase lock at `a07da05` resolved the complete package at
`b7cb3f9`. Android debug built, installed, and clean-launched on Pixel 9 Pro;
the same lock then passed `assembleRelease`. iOS debug reported `BUILD
SUCCEEDED`, clean-installed, and launched the catalogue on iPhone 17 Pro.

On Android, Badge rendered the zero/single/double/maximum/overflow counts,
semantic tones, and accessible-label override. The accessibility tree exposed
`1 unread message`, `9 unread messages`, `99 pending items`, and `100 pending
items` without making the display-only markers actionable. The iOS host
compiled the complete renderer set, but direct component routing and canonical
capture were waived; this is not iOS component-runtime or screenshot evidence.
Exact iOS XCTest, VoiceOver, full TalkBack, RTL, appearance/scaling matrices,
and physical-device evidence remain release requirements.

Final shared gates passed: package 748 tests/2,160 assertions with five model
evaluations skipped; Android 131 tests with zero failures/errors and seven
controller-gated skips; showcase 93 tests/1,467 assertions; both strict
Composer validations; docs build/check; all ten development component checks;
plugin validation with the expected UI-only no-bridge-functions warning; and
both repository diff checks.
