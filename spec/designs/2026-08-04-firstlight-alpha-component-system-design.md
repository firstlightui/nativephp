---
title: Firstlight alpha component system design
description: Approved catalogue, shared API, native expression, evidence, and release boundary for the first public alpha.
status: historical
sources:
  - Constitution.md
  - README.md
  - nativephp.json
  - src/Elements/Segmented.php
---

# Firstlight Alpha Component System Design

Date: 2026-08-04

Status: approved

## Objective

Firstlight UI will provide a coherent, community-facing catalogue of SuperNative controls for NativePHP. Each public component describes durable user intent through a shared Laravel and EDGE API while iOS and Android express that intent with their own native controls, interaction patterns, motion, accessibility behaviour, and presentation.

The first public alpha is a complete control system rather than a sequence of partially released components. Components are still designed, implemented, tested, and dogfooded one at a time, but the package remains unreleased until the entire agreed catalogue passes its shared release gate.

## Product position

Firstlight is a semantic control library, not a cross-platform skin and not a general layout framework.

- Shared APIs guarantee meaning, capability, state behaviour, and quality.
- Platforms retain control of geometry, native presentation, motion, focus, keyboard behaviour, and interaction feedback.
- Component names describe user intent rather than SwiftUI, UIKit, Compose, or Material implementation details.
- The showcase is the exhaustive public reference consumer. Unnamed production applications may provide additional internal evidence where they have genuine uses for a component.
- Navigation, sheets, modals, lists, typography, and structural layout primitives are outside the alpha boundary.

## Alpha catalogue

### Choice

| Component | Intent |
| --- | --- |
| `segmented` | Choose one value from a small, compact set. |
| `pill-group` | Choose one or more values from compact, individually shaped options. |
| `select` | Choose one value from a collapsed option collection. |
| `choice-group` | Show all choices inline, with single- and multiple-selection modes. |

### Entry

| Component | Intent |
| --- | --- |
| `text-field` | Enter one line of text using semantic keyboard and content hints. |
| `text-area` | Enter and edit multiline text. |
| `search-field` | Enter a query with native search, clear, focus, and submission behaviour. |

### Boolean

| Component | Intent |
| --- | --- |
| `switch` | Turn a persistent setting or capability on or off. |

Firstlight does not expose a universal checkbox merely to reproduce the same visual control on both platforms. Boolean settings use `switch`; visible single or multiple choices use `choice-group`, whose renderer chooses the appropriate platform-native expression.

### Actions

| Component | Intent |
| --- | --- |
| `button` | Trigger a labelled semantic action. |
| `icon-button` | Trigger a compact action whose icon has an explicit accessible name. |

### Values

| Component | Intent |
| --- | --- |
| `date-picker` | Choose a calendar date using native presentation. |
| `time-picker` | Choose a time using native presentation. |
| `slider` | Choose a value by moving continuously or in steps across a range. |
| `stepper` | Increment or decrement a bounded numeric value. |

### Status and feedback

| Component | Intent |
| --- | --- |
| `badge` | Show a compact count or short status marker. |
| `status-label` | Show display-only semantic metadata or status text. |
| `progress` | Communicate determinate or indeterminate work. |

An interactive capsule belongs to `pill-group`; a display-only capsule belongs to `status-label`. This distinction keeps behaviour in the component name rather than a visual shape prop.

## Shared public contract

Every applicable field uses the same vocabulary:

- `value` and `native:model` bind public values.
- `label` supplies the visible field label.
- `helper` supplies supporting guidance.
- `error` supplies visible and accessible validation feedback.
- `required` communicates required state.
- `disabled` disables the complete control.
- `a11y-label` and `a11y-hint` provide explicit accessibility semantics.
- `class` controls external EDGE layout only.
- Semantic `tone` and `variant` props may express durable intent; arbitrary platform styling is excluded.

Choice components share one option normalisation and stable-value encoding contract. Values are domain values, never renderer indexes or display labels. Component-specific specifications may narrow allowed value types when a platform capability requires it, but equivalent choice controls must not invent incompatible option shapes.

Firstlight uses standard SuperNative events:

- `@change` for changed values.
- `@submit` for text or search submission where meaningful.
- `@press` for actions.

Firstlight does not invent parallel `@input` or `@commit` events. `native:model`, `native:model.live`, `native:model.blur`, and `native:model.debounce.*` use the standard EDGE compilation and sync-mode contract.

## State model

### Discrete controls

Selection controls, switches, dates, steppers, and other discrete choices are server-authoritative. Interaction emits immediately, but the accepted value enters the renderer through the next published element tree. A rejected change therefore never becomes accepted visual state. Programmatic updates do not emit user events, and reconciliation must not echo changes or replay interaction feedback.

### Text entry

Text controls keep a native editing buffer while focused so typing, selection, composition, cursor movement, and keyboard behaviour remain smooth. The selected binding modifier determines when changes reach PHP:

- `live` is the SuperNative default.
- `blur` also covers the `lazy` alias and publishes on focus loss or submission.
- `debounce` publishes after the configured quiet period and on blur or submission.

Server updates reconcile without unnecessary focus loss, keyboard movement, or cursor jumps. Validation feedback must not destroy the user's current editing context.

### Continuous values

Sliders and similar gestures update on the native UI thread during interaction. Standard binding modifiers determine wire-event frequency: `live` may publish during movement, `blur` publishes on release, and `debounce` coalesces changes. SharedValues are reserved for genuine frame-rate gesture and animation work supported by SuperNative, not used as a competing model-binding system.

## Platform expression

| Intent | iOS | Android |
| --- | --- | --- |
| Segmented choice | Native segmented picker or control | Material 3 segmented buttons |
| Pill choices | SwiftUI capsule-shaped native buttons | Material 3 filter chips |
| Select | Menu or picker for short sets; searchable sheet for larger sets | Exposed dropdown; searchable dialog or sheet for larger sets |
| Single choice | Checkmarked choice rows | Radio-button rows |
| Multiple choice | Checkmarked selectable rows | Checkbox rows |
| Text entry | `TextField`, `SecureField`, or `TextEditor` with iOS field composition | Material 3 text fields |
| Search | Native `UISearchTextField` behaviour and affordances | Material 3 search field or bar |
| Boolean | SwiftUI `Toggle` | Material 3 `Switch` |
| Actions | Native SwiftUI button styles | Material 3 button styles |
| Date and time | SwiftUI pickers and native presentations | Material date and time pickers |
| Slider | SwiftUI `Slider` | Material 3 `Slider` |
| Stepper | SwiftUI `Stepper` | Idiomatic minus, value, and plus control |
| Status | Native text, capsule, and badge treatments | Material badge and chip treatments |
| Progress | SwiftUI `ProgressView` | Material progress indicators |

Long or explicitly searchable option sets receive a larger native presentation. Exact automatic thresholds are renderer policy and must be consistent, documented, and tested; `searchable` expresses a semantic capability rather than selecting a platform widget.

At accessibility text sizes, compact horizontal choices may reflow into vertical rows instead of clipping or shrinking labels. Platform-native focus, keyboard, haptics, motion, pressed states, contrast behaviour, and accessibility semantics are retained. Where a platform has no named stock control, Firstlight composes the intent from that platform's native primitives rather than copying the other platform's appearance.

## Package architecture

Shared foundations are built before the catalogue expands:

1. Field semantics for labels, helper text, errors, required state, and accessibility.
2. Stable-value encoding and option normalisation.
3. Reconciliation and sync-mode rules.
4. Semantic tones and variants backed by the host `native-ui` theme.
5. Actionable development diagnostics.
6. Reusable PHP, iOS, and Android test harnesses.

Each public tag produces an ordinary EDGE element that participates in the official element tree, shared-memory frame, native reader, renderer, callback, reconciliation, identity, and hot-reload lifecycle. The package introduces no WebView, JSON bridge, parallel renderer, or parallel state system.

Before implementing a renderer, its component specification audits the corresponding `nativephp/mobile-ui` primitive. An adequate official primitive is reused or thinly adapted. Firstlight adds a renderer only where the shared semantic contract, stable values, state behaviour, accessibility, or native quality cannot otherwise be met.

The package repository owns public contracts, EDGE elements, paired platform sources, contract tests, component documentation, and repository skills. The separate showcase repository proves real Composer installation and plugin registration and owns the public interaction gallery and representative visual evidence.

## Component workflow

Every component follows the same sequence:

1. Approve its semantic contract and example API.
2. Implement the PHP and EDGE element contract.
3. Implement equal-quality iOS and Android renderers.
4. Add Pest 5 contract tests and platform tests.
5. Add complete showcase coverage.
6. Exercise it in a production application where a genuine internal use exists.
7. Run the constitutional component review.

Work is committed component by component so failures remain attributable and reviewable. Completion of one component does not create a public release while the alpha catalogue remains incomplete.

## Showcase and evidence matrix

The showcase contains one screen per component family plus a full state gallery. Every applicable component demonstrates and verifies:

- Default, selected, empty, disabled, loading, and error states.
- Light and dark appearance.
- Increased contrast.
- Dynamic Type and Android font scaling.
- VoiceOver and TalkBack names, values, roles, hints, and state.
- Reduced Motion.
- Right-to-left layout.
- Long labels and large option collections.
- Rapid repeated interaction.
- Server rejection and programmatic state changes.

The release gate additionally requires both host builds, representative simulator or device evidence, physical-device interaction review, complete documentation, and constitutional compliance. Physical-device review covers motion, focus, keyboard behaviour, accessibility, offline behaviour, reconciliation, and rapid input; native feel cannot be inferred from file existence or screenshots alone.

## Failure behaviour

Firstlight is strict when authored incorrectly and calm at runtime.

Development-time failures include actionable diagnostics for duplicate or mixed-type option values, invalid ranges or steps, invalid variants, unsupported binding modes, incompatible props, and missing visible or accessibility labels. A capability that cannot meet equal platform quality remains unreleased rather than degrading one renderer.

At runtime:

- Empty option collections render an inert disabled control.
- A selected value absent from its options remains visibly unselected.
- Firstlight never silently chooses, clamps, or substitutes a different value unless that behaviour is an explicit documented part of the component contract.
- Disabled controls and disabled choices emit nothing.
- Rejected server changes leave the accepted state visible.
- Programmatic updates never emit user events.
- Errors are visible and announced accessibly.
- Loading actions prevent duplicate activation and expose a native busy state.
- Platform readers and renderers defensively handle malformed frame data without crashing the host application.

## Compatibility and alpha release gate

Every release documents supported NativePHP, `mobile-ui`, iOS, and Android versions. During `0.x`, patch releases do not knowingly break public APIs. Intentional breaking changes occur only in minor releases and include migration guidance.

The first public alpha requires all of the following:

- The complete catalogue in this design.
- Equal production iOS and Android implementations.
- Passing shared and platform-specific tests.
- Passing iOS and Android showcase host builds.
- Complete state and accessibility coverage in the showcase.
- Representative simulator and physical-device evidence.
- Public component documentation and examples.
- A passing constitutional review for every component.
- Resolution of upstream dependencies required for supported installation.

Until that gate passes, development and evidence remain local and no partial package is presented as the public alpha.

## Constitutional amendment record

Article IX.6 is amended to read:

> The separate Firstlight showcase dogfoods every public component and documented state before release.

Rationale: Firstlight is a community library and may include legitimate controls that no particular production adopter currently needs. Requiring artificial production usage would distort applications without increasing confidence. The showcase is the exhaustive, reproducible consumer for the complete public catalogue, while genuine internal production uses remain additional evidence.

Affected principles: Article VIII's small, proven expansion; Article IX's evidence-based quality; and Article X's public-alpha stewardship.

Migration impact: none. This amendment changes release evidence and public wording; it changes no published component API or consumer behaviour.

Approval: explicitly approved by the maintainer on 2026-08-04 before the amendment was applied.
