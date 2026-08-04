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
- Button
- Icon Button
- Pill Group
- Progress
- Switch
- Badge
- Search Field
- Text Area
- Date Picker
- Choice Group
- Time Picker
- Select
- Slider
- Stepper

### In flight

- None

## Build order

All alpha catalogue components are implemented. Remaining work belongs to the
shared alpha release gate below rather than another component build.

## Dependency spine

- Text Field -> Search Field -> Select
- Text Field -> Text Area
- Pill Group -> Choice Group -> Select
- Button -> Progress
- Status Label -> Badge
- Icon Button -> Search Field and Stepper
- Slider -> Stepper
- Date Picker -> Time Picker

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

Screenshot capture is explicitly waived under the roadmap controller's task
instruction. Transient emulator images are supporting runtime evidence, not
committed canonical documentation screenshots.

The public-alpha release gate remains blocked by work outside another component
build: NativePHP must ship an observable identical-publication epoch for Choice
Group, Select, Slider, and Stepper; exact iOS component XCTest/direct-runtime
rows and dated physical-device VoiceOver/TalkBack, scaling, contrast, RTL,
motion, offline, and reconciliation evidence remain open. The catalogue must
not be described as public-alpha ready until those rows close.
