---
title: Code Editor component contract
description: Focused monospaced editing for JSON, YAML, env, and plain text with shared indentation and scroll-anchor rules, key row, follow mode, and the paired-renderer boundary.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/designs/2026-10-19-firstlight-code-editor-design.md
  - spec/reference/package-locale.md
  - src/Elements/CodeEditor.php
  - resources/ios/CodeEditorEditing.swift
  - resources/ios/CodeEditorControl.swift
  - resources/ios/CodeEditorRenderer.swift
  - resources/android/CodeEditorEditing.kt
  - resources/android/CodeEditorControl.kt
  - resources/android/CodeEditorRenderer.kt
  - tests/fixtures/code-editor/editing.json
  - tests/fixtures/code-editor/scroll-anchor.json
---

# Code Editor Component Contract

## Purpose and state class

`<firstlight:code-editor>` edits plain-text configuration and code. It is a
focused-text control with Text Area's state model: native code owns the
buffer, cursor, selection, IME composition, scrolling, and undo history while
focused; PHP owns the published string and validation metadata. The approved
design ([Code Editor design](../designs/2026-10-19-firstlight-code-editor-design.md))
records the catalogue decision, package evaluation, and deferred highlighting.

## Wire contract

Props: `value`, `label`, `helper`, `error`, `required`, `disabled`,
`read_only`, `language` (`plain`, `json`, `yaml`, `env`), `line_numbers`,
`wrap`, `indent` (`2` or `4`), `follow`, `min_lines`, `max_lines`,
`sync_mode`, `debounce_ms`, `a11y_label`, `a11y_hint`, `on_change`, and the
key-row chrome labels listed in [Package locale chrome](../reference/package-locale.md).
Defaults: `plain`, line numbers on, wrap on, indent `2`, follow off, lines
`6`–`20`, `live` sync.

PHP rejects unknown languages, other indents, `follow` without `read-only`,
unordered line bounds, non-strict types, short debounces, and the excluded
props (`placeholder`, autocorrect and keyboard hints, icons, secure or
sensitive modes, highlighting, submit and press events, styling).

## Shared rules

Indentation, outdent, comment toggling, key insertion, and typed Return or
JSON closers are pure engine functions in Swift and Kotlin. Both run every
case in `tests/fixtures/code-editor/editing.json`; offsets are UTF-16. Scroll
anchoring across programmatic replacement is a second pure function run
against `tests/fixtures/code-editor/scroll-anchor.json`. Platform code must
not add rules the fixtures do not cover; changes start in the fixtures.

## Synchronisation

Focused acknowledgements never replace the buffer, selection, composition,
scroll, or undo history. Different focused publications wait until blur.
Unfocused programmatic replacements apply immediately, clear undo history,
emit no `@change`, and request a scroll position: the end when `follow` is
pinned (within one line height of the end) or before the first layout,
otherwise the mapped anchor line, otherwise the clamped previous offset. A
read-only editor holding a non-empty selection defers replacements until the
selection collapses.

## Platform expression

- iOS: TextKit 2 `UITextView` in a `UIViewRepresentable`, TextKit 2 gutter,
  `inputAccessoryView` key row with Done, `UIKeyCommand` Tab and Shift-Tab, and
  a text-view-owned `UndoManager`.
- Android: `BasicTextField` on `TextFieldState` in
  `OutlinedTextFieldDefaults.DecorationBox`, a draw-phase gutter from
  `TextLayoutResult`, an `InputTransformation` for typed Return and closers, a
  non-focusable `Popup` key row at the bottom of the visible window frame,
  `onPreviewKeyEvent` Tab and Shift-Tab, and `TextFieldState.undoState`
  (experimental foundation API, opted in).

## Accessibility

Gutters are hidden from assistive technology. Every key has a published
accessible name and meets 44 pt / 48 dp. Error text replaces helper text
without replacing the field name. Replacements are not announced. Code text is
laid out left-to-right in RTL layouts.

## Evidence still required

Device or emulator verification of the Android key-row popup position above
the keyboard, iOS `follow` and anchoring (Task 6b), showcase fixtures,
screenshots, the 2,000-line performance check, physical-device IME rows, and
constitutional review. See the
[Code Editor implementation plan](../plans/2026-10-19-firstlight-code-editor.md).
