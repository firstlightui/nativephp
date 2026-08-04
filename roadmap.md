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

### In flight

- Stepper

## Build order

6. **Stepper** — reuses Slider's numeric range contract and Icon Button's
   accessible increment and decrement actions.

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
