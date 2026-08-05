---
title: Confirmation Dialog development review
description: Constitutional review of the Firstlight Confirmation Dialog contract, paired native renderers, tests, showcase fixture, and outstanding runtime evidence.
status: blocked
audience: maintainer
sources:
  - Constitution.md
  - spec/components/confirmation-dialog.md
  - docs/components/confirmation-dialog.md
  - nativephp.json
  - src/Elements/ConfirmationDialog.php
  - resources/ios/ConfirmationDialogControl.swift
  - resources/ios/ConfirmationDialogRenderer.swift
  - resources/android/ConfirmationDialogControl.kt
  - resources/android/ConfirmationDialogRenderer.kt
  - tests/Feature/ConfirmationDialogElementTest.php
  - tests/ios/ConfirmationDialogSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/ConfirmationDialogTest.kt
---

# Confirmation Dialog Development Review

Reviewed on 2026-08-05 against package base revision `a711dcb` and showcase
base revision `f5eae8f` on `main`. Those revisions identify the parent
snapshots; the commits containing this report identify the reviewed
Confirmation Dialog implementation in each repository.

**Off-device implementation verdict:** PASS

**Development verdict:** BLOCKED

**Component-release verdict:** BLOCKED

The strict public contract, paired renderers, production host compilation,
package tests, Android behaviour tests, sibling showcase, documentation, and
structural checks pass. Development remains blocked because no iOS simulator,
Android emulator, or physical-device target was authorized for this work.
Native dialog interaction, iOS XCTest execution, documentation captures, and
manual accessibility evidence have therefore not been observed.

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative first | PASS | The EDGE element and paired renderers use NativePHP's official plugin manifest, Element Tree, callback transport, and renderer lifecycle. No WebView, alternate bridge, generated-host edit, or parallel transport was introduced. |
| II. Familiar and coherent APIs | PASS | The API has strict `visible`, `title`, `message`, `confirm-label`, `cancel-label`, and `tone` props; standard `@press` confirms and established NativePHP `@dismiss` cancels. Arbitrary actions, children, icons, loading, disabled, model bindings, and styling controls are rejected. |
| III. Stable values and predictable state | PASS | Confirmation Dialog is an action/presentation component. Confirm and user dismissal each emit once; programmatic closure and reconciliation emit nothing; copy-only updates cannot reopen a user-dismissed dialog; a later `false` to `true` transition can present it again. PHP owns durable presentation intent while native code owns transient dismissal state. |
| IV. Equal platform quality | PASS | SwiftUI `confirmationDialog` and Material 3 `AlertDialog` implement the same copy, action-role, tone, dismissal, and reconciliation contract. Both exact generated showcase hosts compile with package-source checksums matching the installed renderer and control files. Runtime parity remains part of Articles V, VI, and IX. |
| V. Native expression over pixel parity | BLOCKED | Source inspection proves genuine SwiftUI and Material 3 dialog primitives with native ordering, surfaces, motion, and destructive expression. A permitted runtime target is still required to inspect actual platform presentation and interaction. |
| VI. Accessibility is correctness | BLOCKED | Authored title, message, and action labels map directly to native dialog controls, and Android automated rendering covers representative font scaling. VoiceOver, TalkBack, modal focus, Dynamic Type, high contrast, RTL, reduced motion, and touch-target behaviour have not been observed on runtime targets. |
| VII. System-first theming | PASS | Both renderers use native dialog controls, platform typography and action ordering, and semantic destructive colours. The public API exposes no arbitrary colour, elevation, typography, motion, or geometry controls. |
| VIII. Small, proven expansion | PASS | The component is restricted to one confirmation and one cancellation outcome. The primitive audit established that NativePHP's imperative `Dialog::alert()` cannot reconcile in the EDGE tree and Mobile UI's generic Modal lacks fixed decision roles, justifying the smallest paired-renderer extension. |
| IX. Evidence-based quality | BLOCKED | Red-first PHP contract evidence, full PHP tests, Android compilation/tests, off-device iOS production and test-target compilation, docs checks, exact consumer host builds, and showcase tests pass. Native runtime, executed iOS XCTest, approved screenshots, and manual accessibility evidence remain incomplete. |
| X. Public alpha stewardship | BLOCKED | No tag, publication, roadmap closure, component-release claim, or public-alpha claim is authorized while runtime, accessibility, screenshot, and physical-device evidence remain incomplete. |
| XI. Skills enforce the constitution | PASS | The component, iOS, Android, review, documentation, screenshot-registration, Context7 audit, TDD, and verification workflows were applied. Device control stopped at the explicit permission boundary. |

Article XII is not applicable because Confirmation Dialog requires no
constitutional amendment.

## Passing evidence

```text
composer test
PASS — 930 tests, 2,615 assertions; 5 model-backed evals skipped by design

vendor/bin/pest tests/Feature/ConfirmationDialogElementTest.php tests/Feature/PluginManifestTest.php
PASS — 36 tests, 102 assertions

composer validate --strict
PASS

bin/check-component ConfirmationDialog --development
PASS

bin/check-docs --development
PASS

composer docs:build
PASS

git diff --check
PASS in both package and showcase repositories

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android testDebugUnitTest --tests '*ConfirmationDialog*'
PASS

swift build --build-tests --triple arm64-apple-ios18.0-simulator \
  --sdk /Applications/Xcode.app/Contents/Developer/Platforms/iPhoneSimulator.platform/Developer/SDKs/iPhoneSimulator26.5.sdk
PASS — production Confirmation Dialog and XCTest sources compile off-device

php artisan test in firstlight-showcase
PASS — 108 tests, 1,803 assertions

php artisan native:plugin:validate /Users/wojt/Code/clinically-au/firstlight-ui
PASS with expected UI-only warning — no bridge_functions are declared

php artisan native:run android --build=bundle --no-tty in firstlight-showcase
PASS — release app bundle built from the generated host

php artisan native:build --simulated --no-tty in firstlight-showcase
PASS — iOS simulator archive built from the generated host
```

The generated Android and iOS host copies of all four Confirmation Dialog
control and renderer sources have the same SHA-1 checksums as the package
sources. This proves that the successful consumer builds compiled the current
implementation rather than stale generated files.

## Red test evidence

The generated focused PHP contract failed before implementation because
Confirmation Dialog was only a scaffold placeholder: it was not an EDGE
element and had no manifest, precompiler, prop, callback, validation, or
renderer contract. The completed implementation made that focused contract
pass without weakening its assertions.

The ordinary host-macOS `swift test --filter ConfirmationDialog` attempt could
not compile the package because existing UIKit-based renderer sources are not
available for a macOS destination. The explicit iOS Simulator SDK cross-build
then compiled both production and test targets successfully. It did not
execute XCTest and is not reported as runtime evidence.

## Primitive audit

NativePHP Mobile's `Dialog::alert()` is an imperative service, not an EDGE
element, so it cannot participate in server-published tree reconciliation.
Mobile UI's Modal is an arbitrary content container and does not provide a
fixed confirmation action, cancellation role, or destructive action semantic.
Paired renderers through the official component seam are therefore the
smallest architecture that preserves one public Firstlight contract.

## Showcase and documentation evidence

The sibling showcase contains an interactive `/confirmation-dialog` gallery,
isolated `/captures/confirmation-dialog` fixture, navigation entry, default,
destructive, long-copy, repeat-use, cancellation, and programmatic-closure
states. Focused and full showcase tests prove tree publication and callback
routing. The exact local package was installed before both native hosts were
regenerated and compiled; generated trees were not edited manually.

No simulator or emulator was listed, launched, or targeted. Consequently the
registered files below have not been captured and must not be treated as
present evidence:

- `docs/screenshots/confirmation-dialog/ios-light.png`
- `docs/screenshots/confirmation-dialog/ios-dark.png`
- `docs/screenshots/confirmation-dialog/android-light.png`
- `docs/screenshots/confirmation-dialog/android-dark.png`

## Warnings and upstream assumptions

- Plugin validation reports the expected UI-only warning because this plugin
  declares no `bridge_functions`.
- The Android consumer build reports an unrelated deprecation in NativePHP's
  existing `WebviewRenderer.kt`.
- The iOS consumer build reports warnings in NativePHP's embedded PHP C
  sources; the archive still succeeds.
- SwiftUI and Material 3 own native dialog ordering, focus containment,
  dismissal motion, and platform appearance. Runtime evidence must verify
  those upstream behaviours in the generated application.

## Remaining evidence

- Execute the focused Confirmation Dialog XCTest suite on an explicitly
  authorized fixed iOS simulator.
- Build, install, launch, and readiness-check both generated showcase hosts on
  explicitly authorized native targets.
- Exercise confirm, cancel, outside/back dismissal, duplicate suppression,
  programmatic closure, repeated presentation, destructive tone, and long copy
  on both platforms.
- Capture and approve the four registered documentation screenshots.
- Manually verify VoiceOver and TalkBack names, roles, focus containment,
  traversal, announcements, and one-event action behaviour.
- Observe Dynamic Type/font scaling, RTL, Increased Contrast/high contrast,
  dark appearance, and reduced motion on both platforms.
- Rerun the complete package and consumer gates from clean committed checkouts
  before release review.
- Complete dated physical-device evidence required for component release.

## Catalogue readiness

This passing off-device component implementation does not establish catalogue
or alpha readiness. The complete catalogue, every component review, both
showcase runtime hosts, documentation screenshots, accessibility coverage,
physical-device rows, and upstream installation dependencies remain governed
by the shared alpha gate.

## Honest milestone

Confirmation Dialog is implemented, documented, installed in the sibling
showcase, and green in the available off-device verification lane. It is not
development-complete, component-release-ready, or public-alpha-ready until the
runtime, accessibility, screenshot, and physical-device gates above are
closed.
