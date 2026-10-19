---
title: Code Editor development review
description: Constitution review and simulator, emulator, and automated evidence for the Firstlight Code Editor build.
status: incomplete
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-10-19-firstlight-code-editor-design.md
  - spec/plans/2026-10-19-firstlight-code-editor.md
  - spec/components/code-editor.md
  - docs/components/code-editor.md
  - nativephp.json
  - src/Elements/CodeEditor.php
  - src/Components/Concerns/DecodesBladeAttributes.php
  - resources/ios/CodeEditorEditing.swift
  - resources/ios/CodeEditorControl.swift
  - resources/ios/CodeEditorRenderer.swift
  - resources/android/CodeEditorEditing.kt
  - resources/android/CodeEditorControl.kt
  - resources/android/CodeEditorRenderer.kt
  - tests/fixtures/code-editor/editing.json
  - tests/fixtures/code-editor/scroll-anchor.json
  - tests/Feature/CodeEditorElementTest.php
  - tests/Feature/BladeAttributeDecodingTest.php
  - tests/ios/CodeEditorRendererTests.swift
  - tests/android/src/test/kotlin/dev/firstlightui/plugins/firstlight_ui/ui/CodeEditorTest.kt
---

# Code Editor Development Review

**Implementation verdict:** PASS

**Development milestone verdict:** PASS (simulator and emulator evidence)

**Component-release verdict:** BLOCKED

Reviewed on 2026-10-20 against package commit `cfc784a` and showcase commit
`3b14d8c`. The showcase ran against the package working tree through its path
repository. iOS evidence comes from an iPhone 17 Pro simulator (iOS 27.0) on
the Mac mini runner; Android evidence comes from a Pixel 9 Pro emulator
(Android 16) and the JVM suite. No physical device was used.

## Requirement review

| Requirement | Verdict | Evidence |
| --- | --- | --- |
| Catalogue boundary | PASS | A semantic text control with an equal iOS and Android contract. Layout and highlighting are excluded; the design records why no syntax-highlighting package gives equal behaviour on both platforms. |
| Official primitive decision | PASS | Mobile UI's multiline text input has no monospaced mode, indentation rules, key row, line numbers, or undo control, and exposes single-line, secure, and icon APIs this contract excludes. Paired renderers use the official SuperNative seams. |
| Public EDGE contract | PASS | `tests/Feature/CodeEditorElementTest.php`: 64 tests cover defaults, translated key-row chrome, verbatim values, aliases, sync modes, `ValidatesFields`, strict types, `follow` requiring `read-only`, excluded APIs, the label warning, precompilation, the manifest row, and the publication size limit. |
| Shared editing rules | PASS | Swift `CodeEditorEditingTests` and Kotlin `CodeEditorEditingTest` run all 63 cases in `tests/fixtures/code-editor/editing.json`; Pest validates the fixture schema. |
| Scroll anchoring and `follow` | PASS | Both platforms run all 14 cases in `scroll-anchor.json`. Renderer tests cover start pinned, pinned head trims, held anchors when scrolled up, re-pinning, deferral during a read-only selection, and silent replacement. Simulator and emulator live-log runs confirm the same. |
| Focused-text state | PASS | Swift and Kotlin renderer tests prove that focused acknowledgements keep buffer, selection, scroll, and undo; that blur and debounce flush; and that unfocused replacement clears undo and emits no `@change`. |
| Undo across typing and key row | PASS | Fixed in `8a0046e`. `testTypedTextAndKeyRowEditsUndoInTheOrderTheyHappened` passes. On the simulator, real keyboard taps undo `c`, `]`, `ab`, `[` in order. On the emulator, `[` + `ab` + `]` undoes in order and redo works. |
| Key row placement | PASS | Android sits directly above the IME (`f35ef96`, covered by `key row bottom meets the keyboard below the status bar`). iOS uses `inputAccessoryView` on system glass (`cfc784a`); captures show page text does not show through. |
| Gutter | PASS | Android clips to the text viewport (`9c0b1ee`, confirmed during emulator scroll and fling). iOS redraws after pinned replacements (`a41831f`) and stays drawn when unwrapped content widens the gutter (`37be154`). |
| Blade attribute fidelity | PASS | `tests/Feature/BladeAttributeDecodingTest.php`: bound and `native:model` values containing `" & ' < >` and a literal `&amp;` publish unchanged through Code Editor, Text Area, and Text Field, and every adapter uses `DecodesBladeAttributes`. The documentation screenshots show real quotes. |
| Publication size | PASS (with upstream limit) | NativePHP's bridge stores each node's props with a u16 length, so values above about 63.5 KB reach both platforms as empty props. `CodeEditor` throws with the size and a remedy instead. See Upstream assumptions. |
| Large text | PASS | Android Paparazzi `fontScaleTwo` with the key row; iOS `testFontIsMonospacedAndScalesWithDynamicType` and accessibility-size snapshots. |
| Public documentation | PASS | `bin/check-component CodeEditor --development` and `bin/check-docs --component=CodeEditor --development` pass. The page covers the security note for `.env` content and the size limit. |
| Runtime screenshot matrix | PASS | `docs/screenshots/code-editor/{ios,android}-{light,dark}.png` (`ab10f37`, iOS re-captured in `cfc784a`), inspected by both threads. |
| Exact-package showcase | PASS | `/code-editor` and `/captures/code-editor` exist. The showcase suite passes (168 tests). `native:plugin:validate` passes; its only warning is the intentional absence of bridge functions. |
| Live-log performance | PASS (emulator and simulator only) | 1,500 lines, the largest round showcase cap under the size limit. Android: fill pinned in about 167 ms, appends in 83–114 ms, each in one frame. iOS: fill in 0.40 s, appends in 0.38–0.41 s, including XCUITest overhead. |
| VoiceOver and TalkBack | BLOCKED | Automated semantics pass: named field, error, hidden gutter, named keys at 44 pt / 48 dp, silent replacements. A runtime screen-reader pass is outstanding. |
| Physical-device rows | BLOCKED | IME composition (Japanese, Chinese, Korean, swipe), hardware keyboards, rapid input, increased contrast, RTL, and performance on reference devices are outstanding. |

## Constitution review

| Article | Verdict | Evidence |
| --- | --- | --- |
| I SuperNative first | PASS | An ordinary EDGE element with paired renderers and standard text-change events. No WebView, bridge function, or generated-tree edit. |
| II Familiar APIs | PASS | `value`, `native:model` with live, blur, and debounce, `label`, `helper`, `error`, `disabled`, `read-only`, `a11y-label`, and `@change`. `placeholder` is deliberately rejected because code fields use helper text, as recorded in the design. No SwiftUI or Compose terms. |
| III Stable values | PASS | The published string is exact, including after the Blade decoding fix. Programmatic replacement never emits `@change`. Invalid types, languages, indents, bounds, `follow` without `read-only`, and oversized values fail loudly. |
| IV Equal platforms | PASS | Both renderers pass the same fixtures and contract. The iOS suite runs 272 tests with only the 9 baseline failures in other components. The Android suite runs 274 tests and passes; its 3 AlertDialog Paparazzi failures came from goldens that were never committed and are fixed in the following commit. |
| V Native expression | PASS | iOS uses a TextKit 2 `UITextView`, an `inputAccessoryView` key row on `UIGlassEffect`, and `UIKeyCommand`. Android uses `BasicTextField` in Material 3 outlined decoration, a Material key row, and `TextFieldState` undo. |
| VI Accessibility | PASS (automated), BLOCKED (runtime) | Names, error, hidden gutter, key names, targets, Dynamic Type, font scaling, and dark mode are covered. Screen-reader, increased-contrast, and RTL runtime checks are outstanding. |
| VII Theming | PASS | NativePHP tokens and system field colours. Read-only now uses the same `secondarySystemBackground` as Text Area. No style bag; `class` is external layout. |
| VIII Small expansion | PASS | One control with a concrete consumer, Forge Control's configuration editing and deployment log. Highlighting is deferred. |
| IX Evidence | BLOCKED | Automated, simulator, emulator, screenshot, and showcase evidence pass. Physical-device rows are missing. |
| X Stewardship | PASS | An additive pre-alpha API. Nothing published or tagged. |
| XI Skills | PASS | The create, iOS, Android, and review skills and the structural gate were followed. |
| XII Amendment | PASS | No amendment required. |

## Commands

```text
composer test                                   1,366 passed; 3 pre-existing failures (ComponentTooling skills, FeedbackCenter, PluginManifest Media)
bin/check-component CodeEditor --development    Constitution checks passed
bin/check-docs --component=CodeEditor --development   Documentation checks passed
composer validate --strict                      valid (package and showcase)
tests/android/gradlew testDebugUnitTest verifyPaparazziDebug   274 tests, 0 failed after the missing AlertDialog goldens were committed
iOS full suite on iPhone 17 Pro simulator       272 tests, 9 baseline failures outside Code Editor
showcase: vendor/bin/pest                       168 passed
showcase: php artisan native:plugin:validate    passed; warning: no bridge_functions
```

## Warnings

- Android shows the top of the log for one frame after the first fill before
  pinning to the end. The scroll waits for the first layout.
- Android `gfxinfo` reported 73% janky frames (median 48 ms) during emulator
  flings. Emulator rendering is not evidence either way; measure on a device.
- Paparazzi does not run the scroll effect, so the scrolled gutter is verified
  on the emulator only. The iOS glass key row renders black off screen, so its
  snapshot hides the material and the test asserts the effect type.
- On the showcase `/code-editor` page the Dark mode switch does not darken the
  page; dark captures come from `/captures/code-editor`.

## Upstream assumptions

- NativePHP's element bridge limits one node's props to 65,535 bytes
  (`NativeElementBridge.swift` and `.kt` read a u16 size). Large configuration
  files and long logs cannot be published until NativePHP raises the limit.
- Laravel HTML-escapes bound attributes on class components. Firstlight
  adapters decode them; NativePHP's own Blade components may share the issue.

## Catalogue readiness

This review does not support an alpha claim. Physical-device evidence and the
shared alpha gate remain outstanding.
