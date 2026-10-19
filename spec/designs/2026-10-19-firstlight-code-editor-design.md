---
title: Firstlight Code Editor design
description: Approved catalogue Code Editor for monospaced configuration editing, with language-aware indentation, a native key row, and deferred syntax highlighting.
status: approved
sources:
  - Constitution.md
  - spec/reference/catalogue-boundary.md
  - spec/designs/2026-08-04-firstlight-alpha-component-system-design.md
  - spec/components/text-area.md
  - resources/ios/SearchFieldControl.swift
  - resources/android/TextAreaControl.kt
  - nativephp.json
  - https://github.com/simonbs/Runestone
  - https://github.com/Rosemoe/sora-editor
  - https://github.com/Qawaz/compose-code-editor
  - https://developer.android.com/develop/ui/compose/text/user-input
---

# Firstlight Code Editor

Date: 2026-10-19

Status: approved by the maintainer on 2026-10-19 for implementation planning. Plan: [Code Editor implementation plan](../plans/2026-10-19-firstlight-code-editor.md).

This dated record is the design authority for Code Editor until a maintained
component contract exists under `spec/components/`.

## Purpose and state class

`<firstlight:code-editor>` edits plain-text configuration and code: JSON, YAML,
dotenv (`.env`) files, and unclassified plain text. It is a focused-text
control with the same state model as Text Area. Native code owns the editing
buffer, focus, cursor, selection, IME composition, scrolling, and undo history
while editing. PHP owns the published string and validation metadata.

Code Editor is not a rich-text editor, an IDE, a secure field, or a file
browser. It does not open, save, or parse files; the application reads the
file, binds the string, and validates it in PHP.

## Catalogue decision

1. Mobile UI's Text Input has no monospaced, indentation, or code-keyboard
   contract, and Text Area deliberately excludes them. The control is a
   semantic field that no existing primitive expresses.
2. No PHP extension over existing tags can change native text input
   behaviour (autocorrect suppression, indentation, key row).
3. It requires no layout, navigation, mask, or schema builder.

A `variant="code"` on Text Area was rejected: language, gutter, wrap, indent,
key row, and undo would widen Text Area's intentionally narrow contract and
couple two different editing behaviours to one renderer.

## Public API

```blade
<firstlight:code-editor
    label="Environment"
    language="env"
    helper="One KEY=value per line."
    native:model.debounce.500ms="environment"
/>

<firstlight:code-editor
    label="Webhook payload"
    language="json"
    :indent="2"
    :min-lines="8"
    :max-lines="24"
    :wrap="false"
    error-for="payload"
    native:model.blur="payload"
/>
```

| Prop or event | Contract |
| --- | --- |
| `value` / `native:model` | Strict string; omission defaults to `''`. Published verbatim. |
| `language` | `plain`, `json`, `yaml`, or `env`. Defaults to `plain`. Selects indentation rules and key-row symbols; it never validates or reformats content. |
| `label`, `helper`, `error`, `required`, `disabled` | Same field metadata family as Text Area. Participates in `ValidatesFields`. |
| `read-only` | Preserves focus, selection, copy, and scrolling; hides the key row. |
| `line-numbers` | Boolean; defaults to `true`. Shows a decorative gutter. |
| `wrap` | Boolean; defaults to `true`. `false` keeps logical lines on one row with horizontal scrolling. |
| `indent` | `2` or `4` spaces; defaults to `2`. Spaces only. |
| `min-lines` / `max-lines` | Positive integers; defaults `6` and `20`; `min-lines` ≤ `max-lines`. |
| `a11y-label`, `a11y-hint` | Explicit accessibility semantics. |
| `@change` | Current string under the standard `live` / `blur` / `debounce` policy. |
| `class` | External EDGE layout only. |

camelCase aliases (`lineNumbers`, `readOnly`, `minLines`, `maxLines`) are
accepted by the PHP element API; Blade documentation uses kebab-case.

### Always-on text behaviour

- System monospaced font, scaled with Dynamic Type and Android font scale.
- Autocorrect, autocapitalization, spell checking, predictive suggestions
  where the platform allows suppression, smart quotes, smart dashes, and
  smart insert/delete are off. There are no props to re-enable them.
- Existing tab characters display at `indent` width and are preserved.
- Inserted line breaks are `\n`. Existing line endings are not rewritten.

## Editing behaviour

### Indentation

- Return keeps the current line's leading whitespace on all languages.
- `json`: Return after an unclosed `{` or `[` adds one level; typing `}` or
  `]` as the first non-space character on a line removes one level.
- `yaml`: Return after a line ending in `:` (outside a comment) adds one level.
- `env`, `plain`: whitespace carry-over only.
- Indent and Outdent act on the current line or every line touched by the
  selection, and preserve the selection.
- No automatic bracket or quote pairing in v1.

Indentation rules are one shared table with shared golden fixtures executed by
both XCTest and Kotlin tests, so both platforms produce byte-identical results.

### Key row

While the field is focused and editable, a native key row sits above the
software keyboard:

| Group | Keys |
| --- | --- |
| Editing | Indent, Outdent, Undo, Redo |
| `json` | `{` `}` `[` `]` `"` `:` `,` |
| `yaml` | `:` `-` `#` `"` `'` `\|` |
| `env` | `=` `#` `"` `$` `{` `}` `_` |
| `plain` | `(` `)` `[` `]` `{` `}` `"` `'` |
| `yaml`, `env` | Toggle comment (`# ` prefix on the current or selected lines) |

Symbols insert at the cursor or replace the selection. Every key insertion is
one undoable edit that preserves IME composition rules (composition is committed
first). Key-row edits publish through the same sync policy as typing.

The row scrolls horizontally rather than shrinking keys at large text sizes.
It is hidden while a hardware keyboard is attached; hardware Tab / Shift-Tab
indent and outdent, and platform undo shortcuts keep working.

### Undo

Undo and Redo use the platform text-undo system. An unfocused programmatic
publication that replaces the value clears undo history so the user cannot
undo into a version the server has superseded. Focused acknowledgements do
not touch history.

## Synchronisation

Identical to Text Area: focused server acknowledgements never replace the
buffer, move the cursor or selection, clear composition, dismiss the
keyboard, reset scrolling, or clear undo. Different focused publications are
retained for reconciliation. Unfocused programmatic publications replace the
value without emitting `@change`. No `@submit`; Return always inserts a line.

## Platform expression

- **iOS:** `UITextView` (TextKit 2) wrapped in `UIViewRepresentable`, the
  same seam as Search Field. SwiftUI `TextEditor` lacks attributed text and
  input-behaviour control on the iOS 18 floor. Apple field composition for
  label, helper, and error. Gutter drawn from TextKit 2 layout fragments.
  Key row is an `inputAccessoryView` with system-styled buttons and a
  trailing Done button that dismisses the keyboard. Undo uses the text view's
  `UndoManager`; hardware Tab / Shift-Tab via `UIKeyCommand`.
- **Android:** Compose `BasicTextField` on `TextFieldState` (for built-in
  undo/redo and input transformations), decorated with Material 3
  `OutlinedTextFieldDefaults.DecorationBox` for label, supporting text, and
  error. Gutter drawn from `TextLayoutResult`. Key row is a Material 3
  surface with compact text buttons, anchored above the IME through
  `WindowInsets.ime`. Hardware Tab / Shift-Tab via `onPreviewKeyEvent`. The
  system back gesture dismisses the IME. The TextFieldState migration is
  local to Code Editor; Text Area is unaffected.

Mobile UI 0.8's Text Input is not an adequate adapter for the reasons
recorded in the Text Area contract, plus the absence of input-behaviour,
gutter, and key-row seams. Code Editor therefore uses paired renderers.

## Third-party editor packages

Evaluated and not adopted for v1:

| Package | Platform | Why not |
| --- | --- | --- |
| Runestone | iOS, MIT, tree-sitter on `UITextInput` | Strong iOS editor, but Android has no matching engine, so behaviour (indentation, tokenization) would diverge (Article IV). |
| sora-editor | Android, LGPL-2.1 | Android View, not Compose or Material 3; LGPL obligations would pass to every consuming app. |
| compose-code-editor (Qawaz) | Android / KMP | Small, highlighting-focused; no iOS counterpart; adds little beyond a `BasicTextField`. |

Firstlight currently ships no third-party native dependencies
(`nativephp.json` `swift_packages`, `pods`, and Gradle `implementation` are
empty). With highlighting deferred, the remaining work (gutter, indentation,
key row) is small on top of the native text views, and owning it keeps both
platforms' behaviour identical and tested by the same fixtures.

## Deferred: syntax highlighting (v2)

`language` is already part of the v1 contract so highlighting can arrive as
an additive change. The leading v2 candidate is tree-sitter on both platforms
(SwiftTreeSitter and the official Kotlin bindings) with the same grammars,
which keeps tokenization identical. Grammars for JSON and YAML exist; dotenv
needs a grammar choice or a Firstlight-owned lexer. Token colours would map to
system semantic colours with light, dark, and increased-contrast variants.
v2 requires its own design approval, licence review of each grammar, and a
size and performance budget.

## Validation and diagnostics

Strict strings and booleans; `language` and `indent` reject unknown values;
line counts as Text Area. A visible `label` or non-empty `a11y-label` is
required. Excluded props (`placeholder`, `autocorrect`, `autocapitalize`,
`keyboard`, `content-type`, icons, `secure`, `@submit`) fail with actionable
diagnostics. `placeholder` is excluded because empty configuration editors
should explain expected content through `helper`.

## Accessibility

- The text view keeps native VoiceOver and TalkBack text navigation by
  character, word, and line.
- The gutter is hidden from assistive technology. Validation errors should
  quote the offending key or content, not only a line number; the docs say so.
- Each key has an accessible name ("Indent", "Insert left brace",
  "Toggle comment"), meets 44 pt / 48 dp, and is reachable by swipe order.
- Error text replaces helper text and is announced without replacing the
  field name or value. Disabled and read-only remain distinguishable.
- Light and dark appearance, increased contrast, Reduced Motion (no animated
  key-row transitions beyond the system keyboard), and RTL chrome are
  platform-owned. Code text itself stays left-to-right.

## Security note

`.env` content often contains secrets. Code Editor is not a secure field: it
does not mask values, block screenshots, or guarantee that keyboards skip
learning. Suppressing suggestions reduces exposure but is not a guarantee on
either platform. Documentation states this, and applications decide whether
editing secrets on-device is acceptable. A `sensitive` capability is deferred
until both platforms can meet the same guarantee.

## Evidence boundary

- Pest 5: props, defaults, aliases, diagnostics, exclusions, sync modes,
  `ValidatesFields` participation, Blade compilation, manifest identifiers.
- Shared golden fixtures (`tests/fixtures/code-editor/`): indentation,
  outdent, comment toggle, and key insertion per language, executed by both
  platform suites.
- XCTest and Kotlin: decoding, focused reconciliation, cursor/selection/IME
  retention, undo clearing on programmatic replacement, key-row presence
  rules, hardware-keyboard handling, semantics, snapshots at default and
  largest text sizes.
- Performance target: smooth typing at 2,000 lines / 100 KB on reference
  devices on both platforms, recorded with dated evidence.
- Showcase screen and `/captures/code-editor` covering each language, empty,
  error, disabled, read-only, no-wrap, no line numbers, large text, and
  rapid key-row input. Screenshots under
  `docs/screenshots/code-editor/{ios,android}-{light,dark}.png`.

## Non-goals (v1)

Syntax highlighting, autocomplete, linting or inline diagnostics, folding,
find/replace (iOS has `UIFindInteraction`, Android has no equivalent),
bracket pairing, multiple cursors, minimap, themes, file I/O, formatting or
pretty-printing, tab-character indentation, and a `sensitive` mode.
