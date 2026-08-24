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

### On main — V1 alpha catalogue (17)

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

### On main — V2 additions (4)

- Activity Indicator
- Checkbox
- List Item
- Confirmation Dialog

### In progress — V2 service (not a manifest component)

- **Transient Feedback** — `FirstlightUI\Facades\Feedback` with package-owned
  root chrome (`firstlight.feedback-center`). Implementation, PHP/platform
  tests, showcase dogfood, and Android Paparazzi goldens are on `main`. Release
  capture, four documentation screenshots, and dated physical-device
  accessibility evidence remain open. See [roadmap-v2.md](roadmap-v2.md).

Structural and collection work (List, Modal, app chrome, layout adapters) is
tracked in [roadmap-v3.md](roadmap-v3.md).

## Completion boundary

A component is complete only when its semantic contract, PHP and EDGE API,
equal-quality iOS and Android implementation or adapter evidence, focused and
full tests, showcase fixtures, documentation, screenshots, accessibility
checks, consumer builds, device evidence, and constitutional review all pass.

No individual component changes the public-alpha status. The alpha remains
unreleased until the complete catalogue passes the shared release gate.

## Shared alpha release gate

The catalogue implementation and current automated/host gates are largely
complete on `main`, but the public-alpha release gate remains blocked by work
outside another component build:

- NativePHP must ship an observable identical-publication epoch for server-
  authoritative controls (Choice Group, Pill Group, Segmented, Select, Slider,
  Stepper, Switch). Until [NativePHP/mobile-air#365](https://github.com/NativePHP/mobile-air/issues/365)
  lands, consumers may need the documented `libphp.a` publication workaround on
  supported NativePHP 4.2.x runtimes.
- Clean **release-mode** captures and dated physical-device evidence for
  VoiceOver, TalkBack, scaling, contrast, Reduced Motion, right-to-left
  layout, offline behaviour, reconciliation, and rapid input remain open for
  the catalogue as a whole.
- **Transient Feedback** still needs the four registered documentation
  screenshots and `spec/reviews/transient-feedback-alpha.md` before its release
  row closes.

The catalogue must not be described as public-alpha ready until those rows
close.
