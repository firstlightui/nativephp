---
title: Transient Feedback development review
description: Constitutional review of the Firstlight Transient Feedback service, paired native hosts, tests, showcase fixture, and outstanding evidence.
status: blocked
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-08-05-firstlight-transient-feedback-design.md
  - spec/components/transient-feedback.md
  - docs/components/transient-feedback.md
  - nativephp.json
  - src/NativeComponents/FeedbackCenter.php
  - src/Elements/FeedbackCenter.php
  - src/Elements/FeedbackItem.php
  - resources/ios/FeedbackCenterState.swift
  - resources/ios/FeedbackCenterControl.swift
  - resources/ios/FeedbackCenterHost.swift
  - resources/android/FeedbackCenterState.kt
  - resources/android/FeedbackCenterControl.kt
  - resources/android/FeedbackCenterHost.kt
  - tests/Feature/TransientFeedbackApiTest.php
  - tests/Feature/FeedbackCenterTest.php
  - tests/ios/FeedbackCenterTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/FeedbackCenterTest.kt
---

# Transient Feedback Development Review

Reviewed on 2026-08-05 against package implementation revision
`f3fc853a1a41c23528cf9fe46c99df52607e2109` and showcase revision
`cf9345937fea4aade07960cf95411bd415db5112` on `main`. The package started
`main...origin/main [ahead 5]` with no tracked changes and three pre-existing
untracked planning files. The showcase started `main...origin/main [ahead 9]`
and clean.

**Off-device implementation verdict:** FAIL

**Development verdict:** FAIL

**Component-release verdict:** BLOCKED

**Catalogue verdict:** BLOCKED

The public contract, PHP lifecycle, paired production sources, package tests,
Android behaviour tests, iOS test-target compilation, exact generated-host
source identity, showcase tests, and both non-launch consumer builds pass.
The required Android Paparazzi gate does not: 10 Transient Feedback cases
report snapshot mismatches. The review does not diagnose those mismatches as
production defects or stale goldens without further evidence, but a failing
visual-regression gate cannot support a passing implementation or development
verdict. Runtime interaction, screenshots, assistive-technology observation,
and physical-device evidence were explicitly outside this task and remain
blocked.

## Constitutional review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I. SuperNative first | PASS | The service publishes one normalized `firstlight_feedback_center` through NativePHP's official root-child registry. The package uses nested native components, official callback IDs, `NativeRootHostRegistry`, and platform-native SwiftUI/Material 3 hosts. There is no WebView rendering, generated-tree edit, alternate event transport, or `bridge_functions` declaration. |
| II. Familiar and coherent APIs | PASS | `FirstlightUI\Facades\Feedback::{message, success, warning, danger, dismiss}` exposes four semantic tone entry points and dismiss-by-ID. The immutable pending builder adds optional stable ID, one action, held lifetime, and `send()`; sending the same ID replaces that record in place. Validation rejects blank or incomplete authored data. The API excludes arbitrary duration, position, styling, rich content, multiple actions, and external queue mutation. |
| III. Stable values and predictable state | PASS | PHP owns durable authored records while each native host owns transient FIFO presentation state. Same-ID publication refreshes copy and callbacks without reordering or restarting elapsed time; tombstones reject stale frames; action, timeout, and manual dismissal complete once; programmatic removal emits no application event; and stale callbacks cannot affect the next item. Focused PHP and platform behaviour tests exercise those contracts. |
| IV. Equal platform quality | FAIL | Both production hosts implement the same eligibility, FIFO, replacement, timing, lifecycle, action, and dismissal contract, and both generated consumer copies are byte-identical to package sources. However, Android `verifyPaparazziDebug` reports 10 Transient Feedback mismatches across default/dark/tone/action/hold/long-copy/font-scale/RTL cases, so current visual evidence is not passing equally across platforms. |
| V. Native expression over pixel parity | BLOCKED | Source inspection proves genuine SwiftUI material/button primitives and Material 3 `Snackbar`/button primitives, semantic platform colours, safe-area/inset handling, and platform reflow policies. The successful host builds prove integration, not rendered runtime quality; no authorized runtime target was available for observation. |
| VI. Accessibility is correctness | FAIL | Source and automated tests cover native actions, semantic labels, decorative-icon hiding, minimum controls, one announcement per semantic ID, pause while focused, recommended Android timeouts, iOS assistive timing, large text, RTL, and reduced-motion policy. The Android font-scale and RTL snapshots currently mismatch, and VoiceOver, TalkBack, real focus/traversal, high contrast, and touch behaviour were not observed. |
| VII. System-first theming | PASS | Both hosts use semantic system colours, native typography, system material/surface expression, platform insets, and native action controls. The public API exposes no arbitrary colour, elevation, typography, motion, or geometry controls. |
| VIII. Small, proven expansion | PASS | The primitive audit found no official NativePHP tree primitive that provides a global reconciled transient-feedback queue with stable IDs, replacement, one action, lifecycle-aware timing, and application events. The extension is limited to that shared contract, one internal center, one internal item, and paired native root hosts. The sibling showcase dogfoods the public service rather than adding another seam. |
| IX. Evidence-based quality | FAIL | Red-first task evidence, 119 focused PHP tests, 1,077 full PHP tests, 218 Android unit tests, iOS production/test-target compilation, structural/docs gates, exact generated-source checksums, 122 showcase tests, and Android/iOS non-launch host builds are recorded below. The mandatory Paparazzi command exits 1 with 32 mismatches, including 10 owned by Transient Feedback. Runtime, screenshots, manual accessibility, and physical devices remain blocked. |
| X. Public alpha stewardship | BLOCKED | No tag, publication, component-release claim, roadmap closure, screenshot approval, or catalogue claim is supported. The development review preserves the failing and blocked rows and leaves the future release review absent. |
| XI. Skills enforce the constitution | PASS | The Firstlight component-review workflow and verification-before-completion workflow were applied to fresh package and consumer commands. The review records the real Paparazzi failure, allowed development gaps, toolchain warnings, and the explicit device authorization boundary without converting them into release evidence. |

Article XII is not applicable because this work proposes no constitutional
amendment.

## Primitive and architecture audit

NativePHP's available notification/dialog services are imperative operations,
not an EDGE-tree publication seam for a durable, reconciled queue. A screen
local snackbar also cannot persist over navigation or consume a single
root-level authored center. Transient Feedback therefore uses the smallest
official extension seam: PHP normalizes immutable records into one internal
center; each platform registers one root host and consumes those children;
native controls send the existing callback presses; PHP removes the record
before dispatching its typed application event.

`FeedbackItem` always publishes the manual callback, publishes timeout only
for automatic items, and publishes action metadata only as a complete pair.
Native decoders fail closed for missing identity, message, tone, duplicate ID,
or required lifetime callback. iOS and Android queue states preserve authored
FIFO order, same-ID elapsed time, current callback identity, completion
tombstones, next-item clock isolation, and pause/resume accounting. Android
uses `AccessibilityManager.getRecommendedTimeoutMillis`; iOS applies its
assistive-time policy and reduced-motion presentation policy. These facts are
source and automated-test evidence, not runtime observation.

## Fresh package evidence

| Command | Exit | Result and counts |
| --- | ---: | --- |
| `composer validate --strict` | 0 | PASS — `composer.json` is valid. |
| `vendor/bin/pest tests/Feature/TransientFeedbackApiTest.php tests/Feature/FeedbackCenterTest.php tests/Feature/TransientFeedbackDocumentationTest.php tests/Feature/PluginManifestTest.php` | 0 | PASS — 119 tests, 1,325 assertions in 52.44 seconds. |
| `composer test` | 0 | PASS — 1,077 tests, 3,992 assertions; 5 model-backed evals intentionally skipped with the instruction to run `--evals` for a real model. |
| `bin/check-transient-feedback --development` | 0 | PASS — structural checks and PHP callback lifecycle checks passed. Development gaps: package tree dirty only because of the three preserved untracked planning files; four screenshot PNGs absent; future `transient-feedback-alpha.md` release review and all of its required identities/rows/evidence absent. |
| `bin/check-docs --development` | 0 | PASS. |
| `bin/build-docs-artifacts` | 0 | PASS and idempotent — `llms.txt` SHA-256 `b7c3003b935c200f30407450b671b77048cbecb867474334c21eb5098fae51ed`; `llms-full.txt` SHA-256 `7094a38ffcfe52a513f27201ec58d08e8a06244bd40f70f2017cdb19e745cb85`; both byte-identical before and after. |
| `swift build --build-tests --triple arm64-apple-ios18.0-simulator --sdk /Applications/Xcode.app/Contents/Developer/Platforms/iPhoneSimulator.platform/Developer/SDKs/iPhoneSimulator26.5.sdk` | 0 | PASS on the approved cache-access rerun — production and XCTest targets compiled off-device in 5.21 seconds. This did not execute XCTest. |
| `JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home tests/android/gradlew -p tests/android compileDebugKotlin` | 0 | PASS — build successful in 5 seconds; 9 actionable tasks, 1 executed and 8 up-to-date. |
| `JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home tests/android/gradlew -p tests/android testDebugUnitTest` | 0 | PASS — build successful in 11 seconds; XML from 61 suites totals 218 tests, 0 failures, 12 skipped; 35 actionable tasks, 2 executed and 33 up-to-date. |
| `JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12/libexec/openjdk.jdk/Contents/Home tests/android/gradlew -p tests/android verifyPaparazziDebug` | 1 | FAIL — 218 tests, 32 failed, 12 skipped. Ten failures are Transient Feedback snapshot mismatches; 22 are snapshot mismatches in other components. |
| `git diff --check` | 0 | PASS. |

The first sandboxed Swift invocation failed before compilation because the
sandbox denied writes to the user Clang module cache. The exact command was
rerun with scoped cache access and passed; the sandbox denial is not presented
as product evidence. Swift emitted warnings for 23 snapshot PNG resources not
handled by the target and a Clang warning about using the macOS sysroot while
targeting iPhone. Android reported the Kotlin Gradle plugin configuration
check as skipped.

The 10 owned Paparazzi mismatches are:

- `FeedbackCenterPaparazziCases`: `lightDefault`, `darkDefault`, `success`,
  `warning`, `danger`, `action`, `hold`, and `longCopy`;
- `FeedbackCenterFontScalePaparazziCase`: `fontScaleTwo`;
- `FeedbackCenterRtlPaparazziCase`: `rtl`.

The other 22 mismatches are in Callout (3), Confirmation Dialog (3), Icon
Button (3), Segmented (5), Text Area (3), Text Field (1), and Time Picker (4).
Their presence does not excuse the 10 Transient Feedback failures. No golden
was recorded or modified and no cause is asserted by this review.

## Fresh showcase and generated-host evidence

All commands below ran in `/Users/wojt/Code/clinically-au/firstlight-showcase`.
The generated trees were inspected and compiled as read-only evidence.

| Command | Exit | Result and counts |
| --- | ---: | --- |
| `composer validate --strict` | 0 | PASS. |
| `php artisan test` | 0 | PASS — 122 tests, 1,985 assertions in 2.691 seconds. |
| `php artisan native:plugin:validate /Users/wojt/Code/clinically-au/firstlight-ui` | 0 | PASS with the expected UI-only warning: `No bridge_functions defined in manifest`; plugin floors were Android 29 and iOS 18.0. |
| `php artisan native:run android --build=bundle --no-tty` | 0 | PASS — `bundleRelease` produced `nativephp/android/app/build/outputs/bundle/release/app-release.aab`; Gradle build successful in 1 minute 18 seconds with 54 actionable tasks executed. No emulator or device was launched. |
| `php artisan native:build --simulated --no-tty` | 0 | PASS — non-launch iOS archive ended `ARCHIVE SUCCEEDED`. No simulator was launched. |
| `git diff --check` | 0 | PASS; the tracked showcase tree remained clean after both builds. |

The Android build reported an empty `sdk.dir`, the Kotlin plugin configuration
check as skipped, NativePHP's existing deprecated WebView `databaseEnabled`
use, a 454.06 MB runtime bundle, a 474.62 MB app bundle, and a post-build
`open` warning because Finder replacement ForkLift was unavailable. The iOS
archive reported four NativePHP C-bridge compiler warnings (one integer
precision conversion, one malformed documentation escape, and two unused
mutex variables), skipped AppIntents metadata because there was no dependency,
and an always-run NativePHP build script because dependency analysis is
disabled. None of those warnings is treated as runtime evidence.

The generated and package source pairs were byte-identical with these SHA-256
checksums:

| Platform | Source | SHA-256 |
| --- | --- | --- |
| iOS | `FeedbackCenterState.swift` | `54d38053360faa4e297b021ac9e4b98e609de7cf1a22ed9af1a7b9c059c76f5a` |
| iOS | `FeedbackCenterControl.swift` | `e2cefc9c5d4570820c8970fc01b4c98fd6966ac01575c699669f6e78183b6e64` |
| iOS | `FeedbackCenterHost.swift` | `efe0e73533fd172d7b990d5586340c54c4886e915ea0d8114fcbb3c471256cb9` |
| iOS | `FirstlightUIInit.swift` | `656f76a7bf606c682104feea7fc0cc5a9485c25f1da5142a63c0cab4ff87754d` |
| Android | `FeedbackCenterState.kt` | `071d34675fb343ef89ac89b37c3880dcb42109987ba206d32ad9a95e7994117b` |
| Android | `FeedbackCenterControl.kt` | `140a2271f14ee05e33452713a74bde3ed66cc633323b0b35532e43e7474e12e2` |
| Android | `FeedbackCenterHost.kt` | `8be77490125bc4f9026b1f4325ba55b7122a8c492668bae4a1426293bb35b6ef` |
| Android | `FirstlightUIInit.kt` | `765b38d50d269d3960e576c37d2fb4c9360308a3f2f68ab4ce6e8f31b7589cbf` |

## Red-first and regression evidence

The task reports preserve genuine RED evidence rather than reconstructing it
after production code existed:

- The PHP API contract initially failed 16 tests because `FeedbackStore` and
  the related production service, value, event, facade, and publication types
  did not exist.
- The iOS pure-state compile reached the test target and failed for missing
  `FeedbackCenterItemConfiguration`, `FeedbackCenterQueueState`, and timing
  types. Later control, accessibility, stale-event, timing-policy, registry,
  and hit-target tests each failed against the absent contract before passing.
- Android queue/control/host, semantic action, timing, lifecycle, API 29,
  registration, and Paparazzi tests were also authored against missing
  production types and behaviour before implementation.
- Documentation and release-gate negatives proved missing documents,
  manifest paths, release rows, focused-showcase preflight, bounded process
  execution, PNG validation, and parser isolation before their green cases.
- Showcase RED had 8 tests and 0 passes because navigation registration, the
  `/captures/transient-feedback` route, interactive component, and event log
  did not exist. Final Task 6 focused/navigation evidence had 34 passing tests
  and 437 assertions, and its full showcase suite had 121 tests and 1,937
  assertions before this review's fresh 122-test run.

The current focused PHP tests prove publication, replacement, callback
freshness, action-before-dismiss semantics, timeout/manual distinctions,
programmatic silence, navigation refresh, and internal element ownership.
Platform tests prove FIFO/tombstones, elapsed-time preservation, deadline and
lifecycle races, next-item isolation, semantic actions, announcements,
minimum controls, timing adaptation, and exact init registration. Showcase
tests prove all four tones, action, held/manual feedback, FIFO, same-ID
replacement, silent removal, navigation persistence, typed event logging, and
the deterministic capture publication. These automated results do not replace
the blocked runtime rows below.

## Blocked runtime and release evidence

| Evidence | Result | Detail |
| --- | --- | --- |
| iOS simulator execution | BLOCKED | No simulator was listed, launched, installed to, or executed; the Swift and archive commands are compile/build evidence only. |
| Android emulator execution | BLOCKED | No emulator was listed, launched, installed to, or executed; the bundle command is build evidence only. |
| Runtime queue/action/dismiss behaviour | BLOCKED | FIFO, action, timeout, manual dismissal, silent removal, navigation, and background behaviour pass automated state/host tests but were not observed in a running generated app. |
| Documentation screenshots | BLOCKED | None of the four registered iOS/Android light/dark PNGs was captured or approved. |
| VoiceOver | BLOCKED | No VoiceOver announcement, focus, action, dismissal, timing, or navigation behaviour was observed. |
| TalkBack | BLOCKED | No TalkBack announcement, focus, action, dismissal, recommended timing, or navigation behaviour was observed. |
| Accessibility environment matrix | BLOCKED | Dynamic Type/font scaling, RTL, increased/high contrast, dark appearance, reduced motion, keyboard/focus, and touch behaviour were not observed on runtime targets; Android font-scale and RTL snapshots additionally mismatch. |
| iOS physical device | BLOCKED | No physical target was listed or used and no dated device evidence exists. |
| Android physical device | BLOCKED | No physical target was listed or used and no dated device evidence exists. |
| Visual approval | BLOCKED | No human visual approval exists, and the fresh Android snapshot gate fails. |

## Remaining work and release boundary

- Diagnose all 10 Transient Feedback Paparazzi mismatches and either correct
  production rendering or review and deliberately record justified goldens;
  rerun the complete 218-test visual gate to a clean pass.
- On explicitly authorized fixed targets, install and run both generated
  showcase hosts and exercise FIFO, action, automatic/manual dismissal,
  programmatic removal, navigation persistence, background pause/resume,
  duplicate replacement, long copy, and all tones.
- Observe VoiceOver and TalkBack names, roles, one-time announcements, focus,
  traversal, actions, dismissals, timing extension, and navigation behaviour.
- Observe large text/font scaling, RTL, increased/high contrast, dark mode,
  reduced motion, keyboard/focus, insets, and touch targets on both platforms.
- Capture and approve the four registered documentation screenshots, then
  complete the strict release review with real target, reviewer, revision,
  screenshot, runtime, accessibility, and dated physical-device evidence.
- Rerun every package, consumer, checksum, clean-tree, and release-mode gate
  from the final committed revisions.

The maintained component specification was narrowed to distinguish authored
snapshot suites/goldens from a fresh passing snapshot result. No runtime,
screenshot, accessibility, physical-device, component-release, catalogue, or
public-alpha claim is made by this development review.

## Catalogue readiness

Transient Feedback is present in the sibling showcase and its off-device
contract/host integration is largely evidenced, but this does not establish
catalogue or public-alpha readiness. The owned Paparazzi failures, runtime and
accessibility observations, approved screenshots, physical-device rows,
strict release review, complete catalogue evidence, and upstream installation
dependencies remain unresolved.
