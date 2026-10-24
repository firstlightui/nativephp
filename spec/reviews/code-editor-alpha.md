---
title: Code Editor alpha review evidence
description: Component-release evidence, showcase audit and remaining device and approval gates for Firstlight Code Editor.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/workflows/testing.md
  - spec/workflows/showcase-and-screenshots.md
  - spec/screenshots.json
  - docs/components/code-editor.md
  - spec/components/code-editor.md
  - spec/reviews/code-editor-development.md
  - nativephp.json
  - src/Components/CodeEditor.php
  - src/Elements/CodeEditor.php
  - tests/Feature/CodeEditorElementTest.php
  - resources/ios/CodeEditorControl.swift
  - resources/ios/CodeEditorRenderer.swift
  - resources/android/CodeEditorControl.kt
  - resources/android/CodeEditorRenderer.kt
  - tests/ios/CodeEditorEditingTests.swift
  - tests/ios/CodeEditorRendererTests.swift
  - tests/ios/CodeEditorScrollAnchorTests.swift
  - tests/ios/CodeEditorSnapshotTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/CodeEditorEditingTest.kt
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/CodeEditorScrollAnchorTest.kt
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/CodeEditorTest.kt
---

# Code Editor Alpha Review Evidence

## Review identity

| Field | Evidence |
| --- | --- |
| Date | 2026-10-25 |
| Review mode | Component-release (`bin/check-component CodeEditor` without `--development`) |
| Component | `CodeEditor` / `<firstlight:code-editor>` |
| State class | Focused text; native buffer, undo, key row and follow scroll anchoring |
| Implementation | Paired Firstlight SwiftUI and Material 3 renderers |
| Package revision captured | `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` (`v1.0.0-RC6`), clean |
| Package revision tested | `461335df2bcb51e87d092bbdf3a90e6b2a4e7114` on `release/component-reviews`, clean; later commits on the branch change only reviews and documentation |
| Showcase revision | `c7ed28ef489e1caae305fc84961af9f549eb409d` (`firstlightui/showcase` `main`), clean; its lock installs `firstlightui/nativephp` at `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` |
| iOS simulator | Firstlight iPhone 17 Pro iOS 26.5 (`758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC`); XCTest also on Firstlight iPhone 17 Pro iOS 27.0 (`731CC3F2-1651-4522-A5A3-A781710EE166`) |
| Android emulator | `emulator-5554`, AVD Medium_Phone, Android 17 (API 37), `google_apis_playstore_ps16k` (not a Pixel 9 Pro) |
| Visual reviewer | Pending maintainer approval (row below) |
| Physical devices | None used; both device rows remain BLOCKED |

## Release capture

| Result | Command and evidence |
| --- | --- |
| PASS | `bin/capture-doc-screenshot-batch Stepper StatusLabel TextField CodeEditor TextArea --showcase=../showcase --ios=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC --android=emulator-5554 --release` — exit 0 at 2026-10-25 07:14:48 AEDT (batch 5, attempt 2; attempt 1 stopped before publishing because the Android showcase was not foregrounded after an upstream host crash on cold launch, and published nothing). The batch built and launched each host once, ran the focused showcase capture tests, cold-launched `/captures/code-editor` per appearance, waited for stable frames, rejected identical light/dark pairs and published the matrix. It then restored the showcase `.env`, both appearances, iOS Reduce Motion and the Android animator scale. |
| PASS | The orchestrator reported `Package: 19d12ff9d48baeddd312a6ef5d1a749bdba585e8` and `Showcase: c7ed28ef489e1caae305fc84961af9f549eb409d` with no `(dirty)` marker. It also verified that the showcase's installed `firstlightui/nativephp` reference equals the package `HEAD`. Published images were moved out of the tree between batches so that each batch started clean, and were committed unchanged. |
| PASS | The agent inspected the four images below for documented states, theme, crop, labels, truncation, system chrome and accidental data. No layout, theme, truncation or chrome defects seen. |
| BLOCKED | Maintainer visual approval of this release matrix is not yet recorded. |

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | `docs/screenshots/code-editor/ios-light.png` | `docs/screenshots/code-editor/ios-dark.png` |
| Android | `docs/screenshots/code-editor/android-light.png` | `docs/screenshots/code-editor/android-dark.png` |

Thirty matrices were captured with `bin/capture-doc-screenshot-batch` in six five-component batches rather than 31 separate `bin/capture-doc-screenshots` runs. Release mode requires both repositories to be clean before launch, and each single-component run publishes into the package tree. The batch entrypoint uses the same `DocumentationScreenshotCapture` guards.

## Automated and consumer evidence

| Result | Command and evidence |
| --- | --- |
| PASS | `vendor/bin/pest --log-junit ../evidence/pest.xml` at `461335df2bcb51e87d092bbdf3a90e6b2a4e7114` — 1465 passed, 5 skipped, 1 deprecation, 4 warnings, exit 0 (49.7 s). |
| PASS | `xcodebuild -scheme FirstlightIOSControls -destination 'platform=iOS Simulator,id=758B55C3-34B0-49D6-B78B-3C9DFE8FCFBC' -resultBundlePath ../evidence/ios26.xcresult test` (iPhone 17 Pro, iOS 26.5) — TEST SUCCEEDED: 304 tests, 298 passed, 0 failed, 6 skipped. |
| PASS | Same command on `id=731CC3F2-1651-4522-A5A3-A781710EE166` (iPhone 17 Pro, iOS 27.0) — TEST SUCCEEDED: 304 tests, 298 passed, 0 failed, 6 skipped. |
| PASS | `JAVA_HOME=<jdk-21> tests/android/gradlew -p tests/android testDebugUnitTest verifyPaparazziDebug` — BUILD SUCCESSFUL; 309 tests in 87 classes, 0 failures, 0 errors. |
| PASS | `bin/check-docs --development` — "Documentation checks passed.", exit 0. |
| PASS | `bin/check-component <Name> --development` for all 31 manifest components — exit 0 each. |
| PASS | Showcase `php artisan test` at `c7ed28e` — 205 passed (4194 assertions). |
| PASS | Showcase `php artisan native:plugin:validate ../nativephp` — exit 0, with one warning: "No bridge_functions defined in manifest". |

Component-specific tests within those runs:

| Result | Test file and evidence |
| --- | --- |
| PASS | `tests/Feature/CodeEditorElementTest.php` — included in the full Pest run. |
| PASS | `tests/ios/CodeEditorEditingTests.swift` — iOS 26.5: 2 passed, 0 failed; iOS 27.0: 2 passed, 0 failed. |
| PASS | `tests/ios/CodeEditorRendererTests.swift` — iOS 26.5: 34 passed, 0 failed; iOS 27.0: 34 passed, 0 failed. |
| PASS | `tests/ios/CodeEditorScrollAnchorTests.swift` — iOS 26.5: 2 passed, 0 failed; iOS 27.0: 2 passed, 0 failed. |
| PASS | `tests/ios/CodeEditorSnapshotTests.swift` — iOS 26.5: 3 passed, 0 failed; iOS 27.0: 3 passed, 0 failed. |
| PASS | `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/CodeEditorEditingTest.kt` — 2 tests, 0 failed, 0 skipped (`testDebugUnitTest` / Paparazzi verify). |
| PASS | `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/CodeEditorScrollAnchorTest.kt` — 2 tests, 0 failed, 0 skipped (`testDebugUnitTest` / Paparazzi verify). |
| FAIL | `tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/CodeEditorTest.kt` — not found in JUnit results. |

Focused showcase tests: `tests/Feature/CodeEditorCaptureTest.php`, `tests/Feature/CodeEditorShowcaseTest.php`. They ran in the full showcase suite above and, for the capture fixture, again inside the guarded capture.

## Showcase demo audit

Audited `firstlightui/showcase` `c7ed28ef489e1caae305fc84961af9f549eb409d` against `docs/components/code-editor.md`. Covered on the interactive page:

- JSON live editor with a PHP-derived error and `required`
- YAML blur editor, env debounced editor with line bounds
- no-wrap JSON with indent 4, read-only env without line numbers, `disabled`
- programmatic replace/reset
- read-only `follow` live log with append, fill 1,500 lines (trim) and clear

Gaps (listed, not invented):

- `.lazy` and the `plain` language are not demonstrated.
- An editor named only by `a11y-label` is not demonstrated.
- A programmatic update arriving while the editor is focused is not instrumented.

## Constitution review

- **Article I — PASS.** `bin/check-component CodeEditor --development` passes the manifest, renderer-mapping and prohibited-bridge checks; no WebView or bridge call exists in component source.
- **Article II — PASS.** The public API matches `docs/components/code-editor.md` and is enforced by `tests/Feature/CodeEditorElementTest.php`.
- **Article III — PASS for applicable state.** State class: Focused text; native buffer, undo, key row and follow scroll anchoring. Contract tests cover the applicable rejection, programmatic and no-echo rules; the showcase gaps above are not counted as evidence.
- **Article IV —** PASS for simulator/emulator evidence; BLOCKED for physical devices. The production renderers built into the generated hosts and rendered `/captures/code-editor` on iOS 26.5 and Android API 37 in the clean release capture.
- **Article V — PASS by implementation audit.** Paired Firstlight SwiftUI and Material 3 renderers; see the development review and documentation platform section. Native feel on hardware is part of the device rows.
- **Article VI — BLOCKED.** Automated accessibility semantics pass, but live VoiceOver/TalkBack, Dynamic Type/font scale, Reduce Motion, increased contrast and RTL on physical devices are not yet recorded.
- **Article VII — PASS.** Semantic theme tokens only; no style-bag or platform escape props (contract tests).
- **Article VIII — PASS.** Unchanged from the development review.
- **Article IX — BLOCKED.** PHP, iOS (26.5 and 27.0), Android, showcase, documentation and release captures pass; physical-device checks (IX.4) and maintainer visual approval are outstanding.
- **Article X — BLOCKED for catalogue readiness.** A component review does not complete the catalogue.
- **Article XI — PASS.** The review skill, structural gate and documentation gate were applied; missing evidence is recorded as BLOCKED.

Article XII is not applicable.
## Release checklist

- [x] Clean `--release` capture from `19d12ff9d48baeddd312a6ef5d1a749bdba585e8` and `c7ed28ef489e1caae305fc84961af9f549eb409d` published the four-image matrix (2026-10-25).
- [x] Full package suites (Pest, iOS 26.5 and 27.0 XCTest, Android unit/Paparazzi), `bin/check-component CodeEditor --development`, `bin/check-docs --development`, showcase suite and plugin validation pass at `461335df2bcb51e87d092bbdf3a90e6b2a4e7114`.
- [x] Showcase demo audited against every documented state; gaps listed above.
- [ ] BLOCKED — Maintainer visual approval of the four release images listed above (record reviewer, date and verdict per image).
- [ ] BLOCKED — Physical iOS device row:
  - [ ] Device: record model, iOS version, date and tester. Install the showcase built from `461335df2bcb51e87d092bbdf3a90e6b2a4e7114` or later and open **Code Editor** from the showcase home (`/components/code-editor`).
  - [ ] JSON: Return after `{` indents; Return between `{}` splits onto three lines; typing `}` outdents; error updates per change.
  - [ ] YAML: Return after `key:` indents; Toggle comment adds/removes `# `.
  - [ ] Key row: appears above the software keyboard, hidden with a hardware keyboard where Tab/Shift-Tab indent; Undo/Redo reverse each key press.
  - [ ] Live log: stays at the newest line while appending; scroll up and it holds position while lines arrive and old lines are trimmed; scroll back down to resume follow.
  - [ ] Hold a selection in the read-only log while appending; the update waits until the selection collapses.
  - [ ] Rapid input: type and undo quickly; no lost characters or cursor jumps.
  - [ ] VoiceOver: editor named by its label; line numbers silent; every key-row key named; validation messages readable.
  - [ ] Dynamic Type at the largest accessibility size (AX5): monospaced text follows the platform text size; key-row keys keep 44 pt / 48 dp.
  - [ ] Reduce Motion on: follow scrolling respects Reduce Motion / removed animations.
  - [ ] Increase Contrast on (and Smart Invert off): gutter, selection and error colours remain legible.
  - [ ] Right-to-left (device language Arabic or Hebrew, or the Right-to-Left pseudolanguage): code text stays left-to-right inside a right-to-left layout; field chrome mirrors.
  - [ ] Light and dark appearance both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.
- [ ] BLOCKED — Physical Android device row:
  - [ ] Device: record model, Android version, date and tester. Install the showcase built from `461335df2bcb51e87d092bbdf3a90e6b2a4e7114` or later and open **Code Editor** from the showcase home.
  - [ ] JSON: Return after `{` indents; Return between `{}` splits onto three lines; typing `}` outdents; error updates per change.
  - [ ] YAML: Return after `key:` indents; Toggle comment adds/removes `# `.
  - [ ] Key row: appears above the software keyboard, hidden with a hardware keyboard where Tab/Shift-Tab indent; Undo/Redo reverse each key press.
  - [ ] Live log: stays at the newest line while appending; scroll up and it holds position while lines arrive and old lines are trimmed; scroll back down to resume follow.
  - [ ] Hold a selection in the read-only log while appending; the update waits until the selection collapses.
  - [ ] Rapid input: type and undo quickly; no lost characters or cursor jumps.
  - [ ] TalkBack: editor named by its label; line numbers silent; every key-row key named; validation messages readable.
  - [ ] Font size at maximum plus Display size largest (font scale 2.0 where available): monospaced text follows the platform text size; key-row keys keep 44 pt / 48 dp.
  - [ ] Remove animations on (Accessibility) or animator scale 0: follow scrolling respects Reduce Motion / removed animations.
  - [ ] High-contrast text / contrast level high: gutter, selection and error colours remain legible.
  - [ ] Right-to-left (Arabic or Hebrew locale, or Developer options → Force RTL layout direction): code text stays left-to-right inside a right-to-left layout; field chrome mirrors.
  - [ ] Light and dark theme both match the approved captures; airplane mode on: the screen still loads and every interaction round-trips locally.

## Verdicts

- **Automated, consumer and simulator/emulator evidence: PASS.**
- **Component-release readiness: BLOCKED.** Only the maintainer visual approval and the two physical-device rows remain; simulator evidence does not substitute for them.
- **Catalogue readiness: BLOCKED.** Every component review, physical-device evidence and the shared alpha gate must pass.
