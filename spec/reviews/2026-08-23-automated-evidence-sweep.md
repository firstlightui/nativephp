---
title: Automated evidence sweep — 2026-08-23
description: Off-device verification pass across all on-main catalogue components and Transient Feedback after List/List Section merge and Transient Feedback refresh.
status: blocked
audience: maintainer
sources:
  - spec/workflows/testing.md
  - nativephp.json
  - bin/check-component
  - bin/check-docs
  - bin/check-transient-feedback
---

# Automated Evidence Sweep — 2026-08-23

Reviewed package revision `72d410431f7cdb8a987c1f40fa105f983564d212` on `main`.

**Automated sweep verdict:** BLOCKED

**Manual evidence deferred by maintainer:** VoiceOver, TalkBack, navigation/background lifecycle observation, physical-device review, and doc-screenshot recapture on simulators.

## Summary

| Layer | Verdict | Notes |
| --- | --- | --- |
| PHP (`vendor/bin/pest --compact`) | PASS | 1097 passed, 5 skipped, 1 deprecated |
| PHP (`composer test`) | PASS (re-checked 2026-08-24) | 1097 passed; earlier POSIX session-wrapper flake did not reproduce |
| Android unit + Paparazzi (`testDebugUnitTest` + `verifyPaparazziDebug`) | PASS | Java 21 required; full suite green after golden refresh |
| iOS snapshot suites | FAIL | 5 suites missing on-disk references |
| iOS behavioral suites | PASS | FeedbackCenter (18); SearchField/Segmented/Switch/TextField contracts pass |
| `bin/check-component … --development` (24 components) | PASS | All manifest components |
| `bin/check-docs --development` | PASS | |
| `bin/check-transient-feedback --development` | PASS | Release rows intentionally absent |

## Commands

```text
JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12.1/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android verifyPaparazziDebug
PASS — BUILD SUCCESSFUL

JAVA_HOME=/opt/homebrew/Cellar/openjdk@21/21.0.12.1/libexec/openjdk.jdk/Contents/Home \
  tests/android/gradlew -p tests/android testDebugUnitTest
PASS — BUILD SUCCESSFUL

vendor/bin/pest --compact
PASS — 1097 passed, 5 skipped, 1 deprecated (4053 assertions)

xcodebuild -quiet -scheme FirstlightIOSControls \
  -destination 'platform=iOS Simulator,id=EB44C64E-1579-4C13-A1F9-C44FBD496763' \
  test
FAIL — see iOS section

xcodebuild -quiet -scheme FirstlightIOSControls \
  -destination 'platform=iOS Simulator,id=EB44C64E-1579-4C13-A1F9-C44FBD496763' \
  test \
  -only-testing:FirstlightIOSControlsTests/FeedbackCenterTests \
  -only-testing:FirstlightIOSControlsTests/SegmentedRendererContractTests \
  -only-testing:FirstlightIOSControlsTests/SwitchControlRendererContractTests \
  -only-testing:FirstlightIOSControlsTests/TextFieldRendererContractTests
PASS — 18 FeedbackCenter tests + contract suites (SearchField excluded; see below)

for component in ChoiceGroup PillGroup Segmented StatusLabel Callout Badge Button \
  IconButton ListItem ListContainer ListSection Progress ActivityIndicator TextField \
  SearchField TextArea DatePicker TimePicker Select Slider Stepper SwitchControl \
  Checkbox ConfirmationDialog; do
  bin/check-component "$component" --development
done
PASS — 24/24

bin/check-docs --development
PASS

bin/check-transient-feedback --development
PASS
```

Approved simulator IDs (unchanged): iPhone 17 Pro `EB44C64E-1579-4C13-A1F9-C44FBD496763`, Pixel 9 Pro `emulator-5554`.

## Android Paparazzi

Full `verifyPaparazziDebug` passes after recording stale goldens for Callout, Confirmation Dialog, Icon Button, Segmented, Text Area, Text Field, and Time Picker during this sweep. Twenty-one new or updated PNGs under `tests/android/src/test/snapshots/images/` are included in the commit for this sweep.

Transient Feedback Paparazzi goldens were already recorded and verified at `72d4104`.

## iOS XCTest

### Snapshot suites (per-class run)

| Suite | Verdict | Failure mode |
| --- | --- | --- |
| ActivityIndicatorSnapshotTests | PASS | |
| BadgeSnapshotTests | FAIL | Missing on-disk references |
| CalloutSnapshotTests | FAIL | Missing on-disk references |
| CheckboxSnapshotTests | PASS | |
| ChoiceGroupSnapshotTests | PASS | |
| ConfirmationDialogSnapshotTests | PASS | |
| DatePickerSnapshotTests | PASS | |
| FeedbackCenterSnapshotTests | FAIL | Missing on-disk references |
| IconButtonSnapshotTests | FAIL | Missing on-disk references |
| ListItemSnapshotTests | PASS | |
| PillGroupSnapshotTests | PASS | |
| SearchFieldSnapshotTests | PASS | |
| SegmentedControlSnapshotTests | PASS | |
| SelectSnapshotTests | PASS | |
| SliderSnapshotTests | PASS | |
| StatusLabelSnapshotTests | PASS | |
| StepperSnapshotTests | PASS | |
| SwitchControlSnapshotTests | PASS | |
| TextAreaSnapshotTests | FAIL | Missing on-disk references |
| TextFieldSnapshotTests | PASS | |
| TimePickerSnapshotTests | PASS | |

Only seven snapshot test classes have committed `tests/ios/__Snapshots__/` PNGs on disk (23 images total). Five additional suites fail because references were never committed. Recording requires `FIRSTLIGHT_RECORD_SNAPSHOTS=1` inside the XCTest process; shell and shared-scheme environment variables are not propagated by `xcodebuild` for this SwiftPM test target on the controller used here.

### Behavioral / contract failures

| Test | Verdict | Detail |
| --- | --- | --- |
| SearchFieldRendererContractTests.testUIKitConfigurationUsesNativeSearchAndAccessibilitySemantics | PASS (fixed 2026-08-24) | `FirstlightSearchTextField` enforces `firstlightSearchFieldMinimumHeight` (36) over bare `UISearchTextField`'s ~28pt intrinsic height |

All other executed behavioral suites pass, including `FeedbackCenterTests` (18 tests).

## Component structural gates

All twenty-four manifest components pass `bin/check-component <Name> --development`, including adapter-backed Button, List, List Section, and Progress.

## Transient Feedback

`bin/check-transient-feedback --development` passes. Paparazzi (10 goldens), focused PHP tests, and `FeedbackCenterTests` remain green. Development is still **BLOCKED** for manual accessibility, lifecycle, and physical-device rows per `spec/reviews/transient-feedback-development.md`.

## Catalogue / alpha gate (unchanged)

- All twenty-five on-main components are implemented; no component #26 is defined.
- Shared alpha gate remains blocked on NativePHP **#365** (identical-publication), manual VoiceOver/TalkBack, and physical-device evidence.
- Doc screenshots exist for all twenty-five components under `docs/screenshots/*/`. Recapture on simulators was **not** rerun in this sweep per maintainer direction.

## Follow-up before manual simulator session

1. Record missing iOS snapshot references for Badge, Callout, Feedback Center, Icon Button, and Text Area using a host that propagates `FIRSTLIGHT_RECORD_SNAPSHOTS=1` (local Xcode scheme Test Action environment variables, or run from a machine where the env reaches the test runner).
2. When simulators are available: manual VoiceOver/TalkBack, navigation/background lifecycle, physical-device evidence, and optional doc-screenshot refresh.

### Fixed after the sweep (2026-08-24)

- SearchField tap target: `FirstlightSearchTextField` floors intrinsic height at 36 pt; contract suite green.
- `composer test`: 1097 passed (Transient Feedback documentation gate green in this environment).
