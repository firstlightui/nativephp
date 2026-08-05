---
title: List Item development review
description: Constitutional review of the Firstlight List Item contract, paired native renderers, tests, showcase fixture, and outstanding runtime evidence.
status: blocked
audience: maintainer
sources:
  - Constitution.md
  - spec/components/list-item.md
  - docs/components/list-item.md
  - nativephp.json
  - src/Elements/ListItem.php
  - resources/ios/ListItemControl.swift
  - resources/ios/ListItemRenderer.swift
  - resources/android/ListItemControl.kt
  - resources/android/ListItemRenderer.kt
  - tests/Feature/ListItemElementTest.php
  - tests/ios/ListItemSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/ListItemTest.kt
  - docs/screenshots/list-item/ios-light.png
  - docs/screenshots/list-item/ios-dark.png
  - docs/screenshots/list-item/android-light.png
  - docs/screenshots/list-item/android-dark.png
---

# List Item Development Review

Reviewed on 2026-08-05 against package base revision
`efd0623f746d66520c0713c9b1d624b317bd42bc` and showcase base revision
`a2b0d5c7bea114094e4de9fc2c0fbc70a21d028f` on `main`. The List Item changes
in both repositories are uncommitted, so those revisions identify the reviewed
bases rather than an exact immutable implementation revision.

**Implementation verdict:** PASS

**Development verdict:** BLOCKED

**Component-release verdict:** BLOCKED

The public contract, paired renderers, production compilation, package tests,
focused iOS XCTest suite, Android behaviour and snapshots, sibling showcase
host builds and launches, approved documentation matrix, and structural checks
pass. Development remains blocked because manual VoiceOver and TalkBack rows
have not been observed and the Android icon limitation below remains open.
Android typed icon variants are preserved in the wire contract, but Mobile UI
0.3.0's `MaterialIcon` API does not accept the decoded variant and therefore
cannot yet prove a distinct filled-versus-outlined visual result.

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative first | PASS | The EDGE element and paired renderers use the official plugin manifest, Element Tree, callbacks, and renderer lifecycle. No WebView, alternate bridge, generated-host edit, or parallel transport was introduced. |
| II. Familiar and coherent APIs | PASS | The API is one required `headline`, one required `@press`, optional semantic supporting and edge content, strict `disabled`, accessibility overrides, platform icon overrides, and external-layout-only `class`. Arbitrary children, row styling, navigation, selection controls, swipe actions, and secondary targets are rejected. |
| III. Stable values and predictable state | PASS | List Item is an action/display component. A user press emits once; reconciliation emits nothing; disabled and missing callbacks suppress actions. PHP owns all durable content and action outcomes while native code owns transient press feedback. |
| IV. Equal platform quality | BLOCKED | SwiftUI and Material 3 production sources compile and share the same content, action, disabled, and accessibility contract. The focused iOS XCTest suite, Android Paparazzi suite, generated showcase host builds, native launches, and approved light/dark matrix pass. Android typed icon variants are decoded but cannot be visually applied by the installed `MaterialIcon` API. |
| V. Native expression over pixel parity | PASS | iOS uses a SwiftUI `Button` row with native text styles and a 44-point minimum. Android uses Material 3 `ListItem`, native ripple and typography, and a 48-dp minimum. Shared meaning is stable while platform geometry remains native. |
| VI. Accessibility is correctness | BLOCKED | Source and automated Android semantics prove one button node, an authored or derived name, silent edge content, disabled state, and minimum targets. Manual VoiceOver, TalkBack, focus, Dynamic Type/font scaling, RTL, contrast, and runtime disabled-action observations remain open. |
| VII. System-first theming | PASS | The renderers use SwiftUI and Material theme colours, typography, native press feedback, and system layout direction. The public API exposes no arbitrary colour, elevation, typography, or per-platform geometry controls. |
| VIII. Small, proven expansion | PASS | The component is restricted to a single tappable application row and does not pre-empt List, List Section, selection, navigation, menus, or embedded-control APIs. The primitive audit found a real iOS disabled-semantics gap, justifying paired renderers over a thin adapter. |
| IX. Evidence-based quality | BLOCKED | Red-first PHP contract evidence, full PHP tests, Android compilation/tests/goldens, iOS XCTest, docs checks, showcase tests, native host execution, and approved documentation screenshots pass. Manual accessibility and immutable implementation-revision evidence remain incomplete. |
| X. Public alpha stewardship | BLOCKED | No tag, publication, release-readiness claim, roadmap closure, or public-alpha claim is authorized while runtime, accessibility, physical-device, and immutable-revision evidence remain incomplete. |
| XI. Skills enforce the constitution | PASS | The component, iOS, Android, review, documentation, screenshot, Context7 audit, TDD, and verification workflows were applied. Only the explicitly approved iPhone 17 Pro simulator and Pixel 9 Pro AVD were used; the guarded workflow restored appearance, motion, and installed start-route state. |

Article XII is not applicable because List Item requires no constitutional
amendment.

## Passing evidence

```text
composer test
PASS — 930 tests, 2,615 assertions; 5 model-backed evals skipped by design

vendor/bin/pest tests/Feature/ListItemElementTest.php tests/Feature/PluginManifestTest.php
PASS — 71 tests, 166 assertions

composer validate --strict
PASS

bin/check-component ListItem --development
PASS

bin/check-docs --development --component=ListItem
PASS

bin/build-docs-artifacts
PASS

git diff --check
PASS

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android compileDebugKotlin
PASS

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android testDebugUnitTest
PASS

recordPaparazziDebug and verifyPaparazziDebug for ListItemScreenshotTest
PASS — light, dark, and font-scale-two goldens recorded and verified

swift build --build-tests --triple arm64-apple-ios18.0-simulator \
  --sdk /Applications/Xcode.app/Contents/Developer/Platforms/iPhoneSimulator.platform/Developer/SDKs/iPhoneSimulator26.5.sdk
PASS — production List Item sources and XCTest sources compile off-device

xcodebuild -scheme FirstlightIOSControls \
  -destination 'platform=iOS Simulator,id=EB44C64E-1579-4C13-A1F9-C44FBD496763' \
  test -only-testing:FirstlightIOSControlsTests/ListItemSnapshotTests
PASS — 9 tests, 0 failures, including light, dark, and accessibility-size snapshots

composer test in firstlight-showcase
PASS — 108 tests, 1,803 assertions

composer validate --strict in firstlight-showcase
PASS

php artisan native:plugin:validate /Users/wojt/Code/clinically-au/firstlight-ui
PASS with expected UI-only warning — no bridge_functions are declared

bin/capture-doc-screenshots ListItem \
  --showcase=../firstlight-showcase \
  --ios=EB44C64E-1579-4C13-A1F9-C44FBD496763 \
  --android=emulator-5554
PASS — both hosts built, installed, launched, foreground/readiness checked,
and produced a complete restored four-image matrix
```

The Android goldens were inspected after recording. The font-scale-two image
initially clipped the two-character monogram; the monogram treatment was
corrected and all three images were re-recorded and re-verified. The test icon
shim draws a generic diamond, so these goldens prove placement and layout but
not production glyph artwork.

## Red test evidence

The focused PHP contract first failed 66 tests because List Item was still the
scaffold placeholder: it was not an EDGE element, lacked manifest and
precompiler registration, and published none of the required validation,
callback, icon, or accessibility data. The implementation then made the same
focused contract pass without weakening its assertions.

Android production compilation initially rejected a test-shim-only `tint`
argument on `MaterialIcon`. Removing that unsupported argument aligned the
renderer with the installed Mobile UI API and the production compile passed.
The first font-scale-two golden also exposed monogram clipping; the fixed-size
monogram style removed the clipping before the final goldens were accepted.

## Primitive audit

NativePHP Mobile UI 0.3.0 supplies a broad `list_item` primitive. Its Android
renderer disables its click target correctly. Its iOS renderer only lowers
opacity for `disabled` and then applies the shared click handler, which can
still dispatch a press and does not expose disabled accessibility semantics.
It also exposes embedded controls, independent trailing actions, menus, swipe
actions, colours, and elevation beyond the Firstlight contract. A paired
renderer is therefore the smallest official extension that meets equal action
and accessibility semantics.

## Showcase and documentation evidence

The sibling showcase contains an interactive `/list-item` gallery, isolated
`/captures/list-item` fixture, navigation entry, press-state demonstration,
disabled row, all three leading identities, trailing metadata and icon cases,
and platform icon overrides. The capture fixture is stable and registered for
four documentation outputs in `spec/screenshots.json`.

The maintainer explicitly authorized the iPhone 17 Pro simulator on iOS 26.5,
`EB44C64E-1579-4C13-A1F9-C44FBD496763`, and the Pixel 9 Pro AVD running
Android 16/API 36 as `emulator-5554`. The guarded workflow built, installed,
launched, foreground-checked, and readiness-checked both showcase hosts at the
stable `/captures/list-item` route. It restored the original iOS dark
appearance, disabled Reduced Motion after capture, restored Android light
appearance, removed the temporary animator-scale override, and reset the
installed start route.

The complete matrix is:

- `docs/screenshots/list-item/ios-light.png`
- `docs/screenshots/list-item/ios-dark.png`
- `docs/screenshots/list-item/android-light.png`
- `docs/screenshots/list-item/android-dark.png`

All four images were inspected for full viewport, light/dark appearance,
platform-native row expression, headline/supporting hierarchy, leading and
trailing content, disabled state, clipping, truncation, and accidental data.
The maintainer explicitly approved the complete matrix on 2026-08-05.

## Remaining evidence

- Manually verify VoiceOver and TalkBack name, hint, role, disabled state,
  focus stability, silent edge content, and one-event press behaviour.
- Observe Dynamic Type/font scaling, RTL, Increased Contrast/high contrast,
  offline avatar failure, theme changes, and reconciliation on both platforms.
- Resolve or explicitly constrain typed Android icon variants so filled and
  outlined requests produce distinct production glyphs.
- Commit the package and showcase changes, refresh the path dependency to the
  exact package revision, and rerun the complete gates before release review.
- Complete dated physical-device evidence required for component release.

## Honest milestone

List Item is implemented, documented, installed and running in both generated
showcase hosts, green in the automated simulator/emulator lane, and represented
by an explicitly approved documentation matrix. It is not development-complete,
component-release-ready, or public-alpha-ready until the manual accessibility,
Android icon-variant, immutable-revision, and physical-device gates above are
closed.
