---
title: Text Area development review
description: Constitutional review of the off-device Firstlight Text Area implementation and its remaining controller-owned evidence.
status: blocked
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - spec/components/text-area.md
  - docs/components/text-area.md
  - nativephp.json
  - src/Elements/TextArea.php
  - resources/ios/TextAreaControl.swift
  - resources/ios/TextAreaRenderer.swift
  - resources/android/TextAreaControl.kt
  - resources/android/TextAreaRenderer.kt
---

# Text Area Development Review

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-08-04 |
| Component | `TextArea` / `<firstlight:text-area>` |
| State class | Focused text |
| Implementation path | Paired SwiftUI and Material 3 renderers |
| Package revision reviewed | `0f8f59cb5121db487a22f8d3ff7d53373b4c9b71` |
| Showcase revision reviewed | `afa5cb7` |
| Permitted device execution | Controller only; this implementation agent used no simulator, emulator, or physical device |

**Off-device implementation verdict:** PASS

**Development verdict:** BLOCKED

**Component-release verdict:** BLOCKED

The public contract, PHP/EDGE element, paired production renderer source,
focused state tests, complete Android JVM/Paparazzi suite, exact-revision
showcase, documentation, structural gate, and plugin validation pass. The
controller-owned iOS XCTest run, runtime visual/accessibility review, host
builds, and screenshot attempt are not yet recorded. The full PHP suite is
also temporarily red only because the concurrent uncommitted Date Picker test
expects its own manifest/precompiler registration.

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative first | PASS | `TextArea` is an ordinary EDGE element registered in `nativephp.json`. Both renderers consume `NativeUINode`, observe the official current-tree publication, and send standard text-change wire events. No WebView, JSON bridge, generated-tree edit, or alternate state system exists. |
| II. Familiar and coherent APIs | PASS | The API uses strict `value`/`native:model`, field metadata, line bounds, standard accessibility names, `class`, and `@change`; it rejects submit, press, icon, secure, search, and styling escape APIs. |
| III. Stable values and predictable state | PASS | Swift and Kotlin state contracts retain the native focused draft, defer different server publications, preserve Android `TextFieldValue` selection/composition, distinguish live/blur/debounce, suppress disabled/read-only edits, and apply unfocused programmatic updates without echo. |
| IV. Equal platform quality | BLOCKED | Production SwiftUI and Compose renderers implement the same contract, and Android production/test compilation passes. The exact iOS XCTest target has not yet run under controller-owned simulator access. |
| V. Native expression over pixel parity | BLOCKED | iOS source uses genuine SwiftUI `TextEditor`; Android uses genuine Material 3 `OutlinedTextField` with multiline bounds. Runtime native-feel and visual review remain controller evidence. |
| VI. Accessibility correctness | BLOCKED | Strict visible/explicit naming, hints, error feedback, disabled/read-only distinctions, Dynamic Type/font scaling fixtures, and Material semantics are implemented. VoiceOver, TalkBack, contrast, RTL, and physical-device rows remain pending. |
| VII. System-first theming | PASS | Both implementations inherit NativePHP semantic tokens and platform-native field treatment. Public `class` is external layout only; no style bag, radius, animation, or platform escape prop is exposed. |
| VIII. Small, proven expansion | PASS | The contract and installed Mobile UI primitive audit preceded scaffolding. The paired path is justified by Mobile UI's Material-styled iOS outlined renderer and its broader secure/submit/icon/single-line public surface. |
| IX. Evidence-based quality | BLOCKED | Focused PHP, structural/docs, Android, installed showcase, plugin validation, source parsing, and exact-revision checks pass. iOS execution, host builds, runtime capture/accessibility, and a clean full PHP rerun after Date Picker registration remain pending. |
| X. Public alpha stewardship | PASS | The additive pre-alpha API is documented without publication, tag, release, or alpha-readiness claims. |
| XI. Skills enforce the constitution | PASS | The create, iOS, Android, and review skills were followed; an initial Pest contract failed before `bin/scaffold-component TextArea` ran exactly once, and `bin/check-component TextArea --development` passes. |
| XII. Amendment | PASS | Text Area requires no constitutional amendment. |

## Implementation path and official primitive audit

NativePHP Mobile 4 documents `native:model` live, blur, and debounce behavior,
and Mobile UI 0.3.0 exposes multiline mode through its generic text-input
primitive. That primitive is not an adequate adapter for this contract. Its iOS
outlined renderer intentionally recreates Material field chrome instead of an
Apple-native TextEditor composition, while its public API includes submit,
secure, icons, keyboard/content hints, prefix/suffix, loading, and other
single-line affordances deliberately absent from Text Area. The package
therefore uses paired renderers through the official SuperNative seams.

iOS uses SwiftUI `TextEditor` with a native local string binding. Android uses
Material 3 `OutlinedTextField`, `singleLine = false`, `minLines`/`maxLines`, and
`TextFieldValue` so cursor, selection, and IME composition remain native.

## TDD and scaffold evidence

The initial `TextAreaPublicContractTest` described the approved public element
and failed because `FirstlightUI\Elements\TextArea` did not exist. The temporary
red-contract filename was then removed so the non-overwriting scaffolder could
run exactly once. Its generated test placeholder was replaced immediately with
the complete Pest contract before implementation proceeded. No existing file
was overwritten by the scaffolder.

## Passing evidence

```text
vendor/bin/pest tests/Feature/TextAreaElementTest.php \
  tests/Feature/PluginManifestTest.php
PASS — 48 tests, 139 assertions

bin/check-component TextArea --development
PASS

bin/build-docs-artifacts
bin/check-docs --component=TextArea --development
PASS

xcrun swiftc -parse resources/ios/TextAreaControl.swift \
  resources/ios/TextAreaRenderer.swift tests/ios/TextAreaSnapshotTests.swift
PASS — syntax parsing only; not a substitute for XCTest/typechecking

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android testDebugUnitTest \
  --tests dev.firstlightui.plugins.firstlight_ui.ui.TextAreaContractTest \
  --tests dev.firstlightui.plugins.firstlight_ui.ui.TextAreaScreenshotTest \
  --tests dev.firstlightui.plugins.firstlight_ui.ui.TextAreaLargeTextScreenshotTest
PASS — BUILD SUCCESSFUL

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android testDebugUnitTest
PASS — BUILD SUCCESSFUL

php artisan test tests/Feature/TextAreaShowcaseTest.php \
  tests/Feature/TextAreaCaptureTest.php tests/Feature/ShowcaseNavigationTest.php
PASS — 17 tests, 213 assertions

composer test
PASS in firstlight-showcase — 63 tests, 957 assertions

php artisan native:plugin:validate /Users/wojt/Code/clinically-au/firstlight-ui
PASS with the expected UI-only warning — no bridge_functions are declared

composer validate --strict
PASS in package and showcase

git show --check 0f8f59c
git show --check afa5cb7
PASS
```

The showcase lock resolves `firstlightui/nativephp` exactly to package revision
`0f8f59cb5121db487a22f8d3ff7d53373b4c9b71`. Its interactive page extends
`ShowcaseScreen`, appears in `ShowcaseHome`, and dogfoods live, blur, debounce,
error, required, disabled, read-only, rejected, and programmatic publication
states. `/captures/text-area` remains isolated from the interactive gallery.

## Shared full-PHP gate

The latest full package command completed **459 tests and 1,327 assertions**
with five model evals skipped and two failures. Both failures belong exclusively
to the concurrent, uncommitted Date Picker work: its public precompiler and
manifest tests run before Date Picker has been registered in those shared files.
Text Area's focused suite and manifest row pass. The full PHP gate must still be
rerun cleanly after Date Picker reaches its own registration step; this review
does not relabel the shared red command as passing.

## Pending controller evidence

- Run `TextAreaSnapshotTests` on the exact permitted iOS simulator target to
  typecheck production Swift against the package shims and execute focused
  editing and snapshot cases.
- Attempt the stable `/captures/text-area` light/dark matrix on the permitted
  iOS and Android targets. If capture fails, record the user's explicit
  screenshot bypass and continue without representing absent images as a pass.
- Build both generated showcase hosts at the exact package/showcase revisions.
- Perform VoiceOver, TalkBack, Dynamic Type/font scale, increased contrast,
  RTL, Reduced Motion, rapid multiline input, cursor/selection, marked-text and
  IME composition, scrolling, focus, reconciliation, and offline checks.
- Complete dated physical-device evidence before any component release.

## Honest milestone

Text Area is implemented and consumer-proven off-device at exact package and
showcase revisions. It is not development-complete, component-release-ready, or
alpha-ready until the blocked iOS, host, shared-suite, and controller-owned
runtime rows close. Screenshot failure alone may be bypassed under the user's
instruction; the other evidence remains required.
