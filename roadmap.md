# Firstlight UI Roadmap

Firstlight's first public alpha delivers the complete component catalogue in
the order below. Components are finished one semantic contract at a time and
remain subject to the shared alpha release gate.

## Component policy

Every catalogue component is exposed through a public `<firstlight:...>` API.
When `nativephp/mobile-ui` already handles the intent well, Firstlight provides
a thin, documented wrapper over the official primitive, as Button does. When
the primitive cannot satisfy Firstlight's semantic contract, stable-value
model, accessibility requirements, or equal native quality, Firstlight adds
paired iOS and Android renderers through the official SuperNative seams.

Consumers should not need to switch to a `<native:...>` tag for a component in
the Firstlight catalogue. Adapter and renderer decisions are implementation
details behind the same coherent Firstlight API.

## Current state

### On main

- Segmented
- Status Label
- Text Field
- Text Area
- Search Field
- Button
- Icon Button
- Switch
- Checkbox
- Choice Group
- Pill Group
- Select
- Date Picker
- Time Picker
- Slider
- Stepper
- Badge
- Progress
- Activity Indicator
- Callout
- Confirmation Dialog
- Transient Feedback
- Modal
- Bottom Sheet
- List Item
- List
- List Section

## Laravel SuperNative extensions

These are PHP services over existing controls, not extra catalogue tags.
Validation is on main: `ValidatesFields` binds Laravel `MessageBag` text to
field `error` slots. See `docs/how-to/validate-fields.md`.

Approved follow-on extensions (not shipped, not alpha-gate items):

- form submit helper (Button `loading` + `validate()`)
- Gate/Policy helpers on actions
- List paginator binding
- media field (the first follow-on that may add a catalogue tag)
- destructive list actions
- Notification → Feedback/push bridge
- package-owned locale chrome

Do not add layout primitives, navigation chrome, input masks, or a
schema/form builder to the Firstlight catalogue. Mobile UI owns those
foundations. The maintainer record is
`spec/designs/2026-08-23-firstlight-laravel-supernative-extensions-design.md`.

## Completion boundary

A component is complete only when its semantic contract, PHP and EDGE API,
equal-quality iOS and Android implementation or adapter evidence, focused and
full tests, showcase fixtures, documentation, screenshots, accessibility
checks, consumer builds, device evidence, and constitutional review all pass.

No individual component changes the public-alpha status. The alpha remains
unreleased until the complete catalogue passes the shared release gate.

## Shared alpha release gate — 2026-08-04

The catalogue implementation and current automated/host gates are complete:

- package: 748 tests, 2,160 assertions, five model evaluations skipped;
- Android JVM/Paparazzi: 131 tests, zero failures/errors, seven
  controller-gated evidence skips;
- exact-lock showcase: 93 tests, 1,467 assertions at package `b7cb3f9` and
  showcase `a07da05`;
- all ten new development component checks, docs build/check, strict Composer
  validation, plugin validation, and repository diff checks pass;
- Pixel 9 Pro debug install/launch and serialized component interaction pass;
  the same lock passes Android `assembleRelease`;
- iPhone 17 Pro debug reports `BUILD SUCCEEDED`, clean-installs, and launches
  the complete catalogue.

The public-alpha release gate remains blocked by work outside another component
build:

- NativePHP must ship an observable identical-publication epoch for Choice
  Group, Select, Slider, and Stepper;
- exact iOS component XCTest/direct-runtime rows and dated physical-device
  VoiceOver/TalkBack, scaling, contrast, RTL, motion, offline, and
  reconciliation evidence remain open;
- the catalogue must not be described as public-alpha ready until those rows
  close.
