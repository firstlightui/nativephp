---
title: Firstlight Code Editor implementation plan
description: Test-first delivery plan for the Code Editor element, shared editing fixtures, paired UITextView and Compose renderers, key row, documentation, showcase, and review.
status: current
sources:
  - spec/designs/2026-10-19-firstlight-code-editor-design.md
  - Constitution.md
  - spec/reference/catalogue-boundary.md
  - spec/reference/field-validation.md
  - spec/components/text-area.md
  - spec/workflows/adding-components.md
  - spec/workflows/testing.md
  - tests/android/build.gradle.kts
  - Package.swift
---

# Firstlight Code Editor Implementation Plan

> **For agentic workers:** Coordinate through `firstlight-create-component`
> and its required sub-skills (`firstlight-ios-component`,
> `firstlight-android-component`, docs skills, `firstlight-review-component`).
> Work inline in the primary checkout task by task. Steps use checkbox
> (`- [ ]`) syntax for tracking.

**Goal:** Deliver a development-proven `<firstlight:code-editor>` for JSON,
YAML, `.env`, and plain text with monospaced input, language-aware
indentation, a native key row, undo, and no syntax highlighting.

**Architecture:** Paired Firstlight renderers. A pure, platform-local editing
engine (indent, outdent, Return, comment toggle, key insertion) in Swift and
Kotlin is proven against one shared JSON fixture set. iOS wraps `UITextView`
(TextKit 2); Android uses Compose `BasicTextField` on `TextFieldState` inside
Material 3 outlined decoration. The PHP element follows Text Area's
focused-text and `ValidatesFields` model.

**Tech Stack:** PHP 8.4, NativePHP Mobile `^4.6`, Mobile UI `^0.8`, Pest 5,
UIKit/SwiftUI on iOS 18, Jetpack Compose (BOM `2025.12.00` in the test
project) with Material 3, Paparazzi, swift-snapshot-testing.

**Spec:** [Code Editor design](../designs/2026-10-19-firstlight-code-editor-design.md)

## Global constraints

- Public type `firstlight.code-editor`; element `FirstlightUI\Elements\CodeEditor`;
  Blade `FirstlightUI\Components\CodeEditor`; renderers `CodeEditorRenderer`
  (iOS) and `dev.firstlightui.plugins.firstlight_ui.ui.CodeEditorRenderer`
  (Android), per scaffold conventions.
- No third-party native dependencies; `nativephp.json` dependency lists stay empty.
- No syntax highlighting, completion, bracket pairing, find/replace, or
  `sensitive` mode. Excluded props fail loudly.
- Editing-engine behaviour is defined only by the shared fixtures; platform
  code never adds rules the fixtures do not cover.
- No device, simulator, or emulator action without explicit permission for the
  exact target. Do not publish or tag. Preserve unrelated showcase work.
- Keep `roadmap.md` and the gap scratchpad out of git.

## File map

| Path | Responsibility |
| --- | --- |
| `tests/fixtures/code-editor/editing.json` | Shared editing cases: language, indent, text, selection, operation, expected text and selection |
| `src/Elements/CodeEditor.php` | Props, defaults, aliases, diagnostics, sync modes, `FieldErrorBinder`, `@change` |
| `src/Components/CodeEditor.php` | Blade adapter |
| `resources/ios/CodeEditorEditing.swift` | Pure editing engine |
| `resources/ios/CodeEditorControl.swift`, `CodeEditorRenderer.swift` | UITextView representable, gutter, accessory row, field composition |
| `resources/android/CodeEditorEditing.kt` | Pure editing engine |
| `resources/android/CodeEditorControl.kt`, `CodeEditorRenderer.kt` | BasicTextField, decoration, gutter, key row |
| `tests/Feature/CodeEditorElementTest.php`, `CodeEditorFixturesTest.php` | PHP contracts and fixture schema |
| `tests/ios/CodeEditor*Tests.swift` | Engine fixtures, renderer behaviour, snapshots |
| `tests/android/src/test/kotlin/.../ui/CodeEditor*Test.kt` | Engine fixtures, renderer behaviour, Paparazzi |
| `spec/components/code-editor.md`, `docs/components/code-editor.md` | Maintained contract and public docs |
| Sibling `firstlightui/showcase` | Gallery screen and `/captures/code-editor` |

---

### Task 0: Confirm platform prerequisites

- [ ] **Step 1:** Confirm the NativePHP Android host's Compose foundation
  version supports `TextFieldState`, `UndoState`, and `InputTransformation`
  (foundation 1.7+). Record the version in the plan's evidence notes.
- [ ] **Step 2:** If the host is older, stop. The fallback (TextFieldValue
  plus a Firstlight undo stack) changes the design's Android expression and
  needs maintainer approval first.
- [ ] **Step 3:** Confirm `UITextView` TextKit 2 layout-fragment enumeration
  is available on iOS 18 for gutter drawing (it is iOS 16+); no action if so.

### Task 1: Shared editing fixtures

**Files:** create `tests/fixtures/code-editor/editing.json`, `tests/Feature/CodeEditorFixturesTest.php`

Each case: `name`, `language`, `indent`, `text`, `selection` (`[start, end]`
UTF-16 offsets), `operation` (`return`, `indent`, `outdent`,
`toggle-comment`, `insert:<symbol>`), `expected_text`, `expected_selection`.

- [ ] **Step 1:** Write the Pest schema test first: every case has the fields
  above, known languages and operations, valid offsets, unique names, and
  every design rule has at least one case. Run it and confirm it fails because
  the fixture file doesn't exist.
- [ ] **Step 2:** Author cases covering at least:
  - Return whitespace carry-over (all languages, including tab-indented lines).
  - JSON level increase after `{` / `[`, none after `{}` on the same line,
    none when the bracket is inside a string.
  - JSON first-character `}` / `]` outdent; no outdent mid-line.
  - YAML increase after trailing `:`; none after `key: value`; none when
    the colon is inside a `#` comment.
  - Indent/outdent single line, multi-line selection, partial leading
    whitespace (outdent by fewer than `indent` spaces), selection preservation.
  - Comment toggle on, off, mixed selection, indented lines (`yaml`, `env`).
  - Symbol insertion at the cursor and over a selection.
  - Non-ASCII text before the cursor (emoji, CJK) so UTF-16 offsets are proven.
- [ ] **Step 3:** Pest green; commit.

### Task 2: PHP element, Blade, manifest

**Files:** `bin/scaffold-component CodeEditor` (once), `src/Elements/CodeEditor.php`, `src/Components/CodeEditor.php`, `nativephp.json`, `tests/Feature/CodeEditorElementTest.php`

- [ ] **Step 1:** Write failing Pest for the public tag (class missing).
- [ ] **Step 2:** Run `bin/scaffold-component CodeEditor` once.
- [ ] **Step 3:** Expand Pest: defaults (`language=plain`, `line-numbers=true`,
  `wrap=true`, `indent=2`, lines `6`/`20`); strict strings and booleans;
  `language` and `indent` rejection; `min-lines` ≤ `max-lines`; camelCase
  aliases; `live`/`blur`/`lazy`/`debounce` modes, with sub-50 ms rejection;
  `@change` registration; `FieldErrorBinder` (authored `error` wins);
  label-or-`a11y-label` diagnostic; excluded props (`placeholder`,
  `autocorrect`, `autocapitalize`, `keyboard`, `content-type`, icons,
  `secure`, `@submit`, styling attributes); layout-only `class`; real Blade
  compilation; manifest identifiers.
- [ ] **Step 4:** Implement the element by following `TextArea.php`'s structure;
  register in `nativephp.json` and `ValidatesFields` participation if it's
  enumerated.
- [ ] **Step 5:** `vendor/bin/pest tests/Feature/CodeEditor* --compact` green;
  `bin/check-component CodeEditor --development` (expected to block on native
  files until Tasks 3–6); commit.

### Task 3: iOS editing engine

**REQUIRED SUB-SKILL:** `firstlight-ios-component`

- [ ] **Step 1:** XCTest that loads `tests/fixtures/code-editor/editing.json`
  via `#filePath` and runs every case through `CodeEditorEditing.apply`; it
  fails because the engine is missing.
- [ ] **Step 2:** Implement the pure engine on `String` + `NSRange` (UTF-16).
- [ ] **Step 3:** All fixture cases pass; commit.

### Task 4: iOS control and renderer

- [ ] **Step 1:** Failing XCTest for: prop decoding; monospaced scaled font;
  autocorrection, spell checking, smart quotes/dashes/insert off;
  `inputAccessoryView` present only when focused, editable, and no hardware
  keyboard; per-language key sets; accessible key names and 44 pt targets;
  Return/Indent/Outdent/comment routed through the engine as single undoable
  edits; marked text committed before key insertion; Tab/Shift-Tab
  `UIKeyCommand`s; focused acknowledgement leaves text, selection, scroll,
  and undo untouched; unfocused publication replaces text, clears undo, and
  emits no change; live/blur/debounce publication; disabled and read-only;
  gutter hidden from accessibility; wrap on/off.
- [ ] **Step 2:** Implement `UIViewRepresentable` over `UITextView`, Apple field
  composition (label/helper/error as Text Area), TextKit 2 gutter, accessory
  row with trailing Done.
- [ ] **Step 3:** Snapshots: empty, filled per language, error, disabled,
  read-only, no line numbers, no-wrap, light/dark, largest accessibility size.
- [ ] **Step 4:** `xcodebuild … test` on macOS (Debian orbs can't build iOS;
  record the gap if run from an orb); commit.

### Task 5: Android editing engine

**REQUIRED SUB-SKILL:** `firstlight-android-component`

- [ ] **Step 1:** JVM test loading the same fixture file through a relative
  path from `tests/android`; it fails because the engine is missing.
- [ ] **Step 2:** Implement the pure engine on `String` + UTF-16 index ranges.
- [ ] **Step 3:** All fixture cases pass under
  `tests/android/gradlew -p tests/android testDebugUnitTest`; commit.

### Task 6: Android control and renderer

- [ ] **Step 1:** Failing Kotlin tests mirroring Task 4: decoding; monospace
  and font scale 2.0; `KeyboardOptions` (autocorrect off, capitalization none);
  key row only when focused, editable, and IME visible; per-language keys;
  48 dp targets and content descriptions; engine edits as single undo entries
  via `TextFieldState.undoState`; composition committed before insertion;
  hardware Tab/Shift-Tab via `onPreviewKeyEvent`; reconciliation and undo
  clearing; sync modes; disabled and read-only; gutter semantics cleared;
  wrap on/off.
- [ ] **Step 2:** Implement `BasicTextField(state)` in
  `OutlinedTextFieldDefaults.DecorationBox`, `TextLayoutResult` gutter, and a
  Material 3 key row anchored with `WindowInsets.ime`.
- [ ] **Step 3:** Paparazzi states matching Task 4 Step 3.
- [ ] **Step 4:** `testDebugUnitTest` and `verifyPaparazziDebug` with JDK 21; commit.

### Task 7: Maintained contract and public docs

**REQUIRED SUB-SKILL:** `firstlight-docs-write`

- [ ] **Step 1:** Write `spec/components/code-editor.md` from the design and
  implemented API only; index in `spec/index.md`.
- [ ] **Step 2:** Write `docs/components/code-editor.md`: examples for each
  language, props, sync, validation with `ValidatesFields` (including JSON
  and dotenv validation examples in PHP), key row, accessibility guidance
  on quoting content in errors, and the security note for `.env` secrets.
  Index it in `docs/index.md`.
- [ ] **Step 3:** `bin/build-docs-artifacts`; `bin/check-docs --development`
  green; commit.

### Task 8: Showcase and capture fixture

- [ ] **Step 1:** Inspect showcase git status; preserve adjacent work.
- [ ] **Step 2:** Add a `ShowcaseScreen` covering each language, empty, error
  (server-side JSON validation), disabled, read-only, no-wrap, no line
  numbers, long content (2,000-line sample), and programmatic replacement;
  register the exact tag and summary in `ShowcaseHome`; add
  `/captures/code-editor`. Keep `NATIVEPHP_START_URL=/`.
- [ ] **Step 3:** Install the exact package commit; run focused and full
  consumer tests without devices; commit showcase separately when authorized.

### Task 9: Screenshots and performance evidence

**REQUIRED SUB-SKILL:** `firstlight-docs-screenshots` (only with explicit target permission)

- [ ] **Step 1:** Add `code-editor` to `spec/screenshots.json`.
- [ ] **Step 2:** With permission, capture
  `docs/screenshots/code-editor/{ios,android}-{light,dark}.png` from
  already-open simulators/emulators, including the key row; commit PNGs.
- [ ] **Step 3:** With permission, record the 2,000-line / 100 KB typing
  check on both platforms with device, OS, and date. Without it, leave the
  rows honestly deferred.

### Task 10: Constitutional development review

**REQUIRED SUB-SKILL:** `firstlight-review-component`

- [ ] **Step 1:** `bin/check-component CodeEditor --development` PASS.
- [ ] **Step 2:** Write `spec/reviews/code-editor-development.md` with Articles
  I–XII as PASS/FAIL/BLOCKED and exact evidence; physical-device, live
  VoiceOver/TalkBack, and IME-composition rows (Japanese, Chinese, Korean,
  and a swipe keyboard) are release evidence.
- [ ] **Step 3:** Commit review evidence.

## Spec coverage checklist

| Design requirement | Task |
| --- | --- |
| `language` / `indent` / `wrap` / `line-numbers` / line range props | 2, 4, 6 |
| Always-on input behaviour (monospace, no autocorrect) | 4, 6 |
| Indentation and comment rules, byte-identical on both platforms | 1, 3, 5 |
| Key row, per-language keys, hardware-keyboard behaviour | 4, 6 |
| Undo/Redo and history clearing on programmatic replacement | 4, 6 |
| Focused-text sync and reconciliation | 2, 4, 6 |
| `ValidatesFields` and `error` slot | 2, 7 |
| Accessibility (silent gutter, named keys, target sizes) | 4, 6, 10 |
| Excluded props and non-goals | 2 |
| Security note for `.env` | 7 |
| Performance target | 8, 9 |
| Docs, showcase, screenshots, review | 7–10 |
