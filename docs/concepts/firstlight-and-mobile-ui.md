---
title: What Firstlight is
description: Firstlight is the app-shell layer for NativePHP. What it provides, what NativePHP and Mobile UI provide, and what Firstlight is not.
type: explanation
audience: consumer
sources:
  - Constitution.md
  - composer.json
  - nativephp.json
  - vendor/nativephp/mobile-ui/nativephp.json
  - spec/architecture/package.md
  - vendor/nativephp/mobile/src/Edge/Layouts/NativeLayout.php
  - resources/boost/guidelines/core.blade.php
  - spec/components/badge.md
  - spec/components/button.md
  - spec/components/date-picker.md
  - spec/components/icon-button.md
  - spec/components/pill-group.md
  - spec/components/progress.md
  - spec/components/select.md
  - spec/components/stepper.md
  - spec/reviews/search-field-development.md
  - spec/reviews/text-area-development.md
  - src/Concerns/ValidatesFields.php
  - src/Concerns/SubmitsForms.php
  - src/Concerns/AuthorizesActions.php
  - src/Concerns/PaginatesLists.php
  - src/Concerns/DestroysListItems.php
  - src/Validation/FieldErrorBinder.php
  - docs/how-to/validate-fields.md
  - docs/how-to/submit-forms.md
  - docs/how-to/authorize-actions.md
  - docs/how-to/paginate-lists.md
  - docs/how-to/destroy-list-items.md
  - docs/how-to/localize.md
  - src/Support/Chrome.php
  - spec/components/transient-feedback.md
---

# What Firstlight Is

Firstlight is the app-shell layer for NativePHP. It covers what a standard
forms app needs (lists and detail, search, forms with Laravel validation,
settings, sheets and dialogs, and feedback) with genuine SwiftUI controls on
iOS and Jetpack Compose Material 3 controls on Android, from one Blade API.

It is built for agentic coding. Four [recipes](../how-to/build-a-list-detail-flow.md)
show each kind of screen end to end, and a Laravel Boost guideline and two
skills point coding agents at them. See
[Build with a coding agent](../getting-started/build-with-a-coding-agent.md).

## Who provides what

Firstlight builds on two NativePHP packages and replaces neither.

| Layer | Provides |
| --- | --- |
| NativePHP Mobile | EDGE, the Element Tree, the event lifecycle, and native navigation chrome: layouts that return `true` from `usesNativeChrome()` get SwiftUI `NavigationStack` and `TabView` on iOS and a Material 3 `Scaffold` and top app bar on Android |
| NativePHP Mobile UI | Layout primitives such as columns, scroll views, and grouped lists, the semantic theme, and the native renderer foundation |
| Firstlight UI | Form, list, selection, overlay, status, and feedback controls with one contract on both platforms; Laravel validation, submission, authorization, pagination, and deletion on screens; Pest screen assertions and a screen generator; recipes, a Boost guideline, skills, and `llms.txt` |

Firstlight depends on Mobile UI, follows its EDGE conventions, inherits its
theme, and uses the same Element Tree and event lifecycle. Mobile UI is the
broad native toolkit; Firstlight decides common form and list behaviour once so
each application does not assemble it again.

## One API, the right pattern on each platform

A Firstlight tag means the same thing on both platforms, and each platform
draws it its own way. Tabs are a segmented control on iOS and Material 3 tabs
on Android. A Confirmation Dialog is a SwiftUI `confirmationDialog` on iOS
and a Material 3 `AlertDialog` on Android. Date Picker opens the graphical
SwiftUI `DatePicker` on iOS and the Material 3 `DatePickerDialog` on Android.
Field errors
appear under the field on iOS and as Material 3 supporting text in the error
state on Android.

Every control is a real platform control. Firstlight draws nothing to imitate
one, and there is no WebView.

## What Firstlight is not

- **Not navigation chrome.** Stacks, title bars, back navigation, and tab bars
  come from NativePHP's native chrome. Firstlight runs inside them. Do not use
  the `custom` attribute on top bars or bottom navs to draw your own.
- **Not a design system.** Firstlight does not define a visual style.
  Controls take colours and fonts from the NativePHP theme and keep each
  platform's geometry, motion, and materials.
- **Not a catalogue of everything.** There are no charts, carousels, or other
  custom-drawn controls. A new tag is added only for a semantic control with an
  equal native contract on iOS and Android.
- **Not a form builder.** Screens are ordinary Blade views with PHP state, not
  schemas.

## Additional dedicated components

Mobile UI provides primitives from which many interfaces can be composed. Firstlight adds dedicated components where one reusable semantic contract is valuable:

- **Icon Button** uses the compact native icon-button family, requires an accessible action name, guarantees minimum interaction targets, and supports disabled and loading states. Mobile UI provides capable Button and Icon primitives, but does not expose this complete contract as a standalone manifest component.
- **Search Field** makes native search behaviour an invariant. It owns the platform search and clear affordances, submit flushing, focused query state, and accessible clear action instead of requiring an application to configure a general text input each time.
- **Stepper** provides exact bounded increment and decrement over integer or floating-point grids. It uses SwiftUI `Stepper` on iOS and an idiomatic Material 3 decrement/value/increment composition on Android.
- **Status Label** provides a display-only status capsule with semantic tones, accessible static-text semantics, text scaling, and contrast protection. It avoids giving non-interactive status metadata the behaviour of a button or selectable chip.

## Higher-level components over existing primitives

Some Firstlight components package several Mobile UI capabilities into one stable application-facing value:

- **Choice Group** presents single-choice radio rows or multiple-choice checkbox rows through one option-array API and one complete scalar-or-list value.
- **Pill Group** composes native chip intents into one single- or multiple-selection field. The group publishes one stable scalar or list rather than requiring a separate Boolean model for every chip.
- **Text Area** turns multiline editing into a dedicated, deliberately narrow field with native focused-state preservation, line bounds, validation feedback, and a genuine SwiftUI `TextEditor` expression on iOS.
- **Time Picker** separates nullable `HH:mm` selection from a combined date/time/datetime API and gives it the same explicit draft, confirmation, validation, and accepted-value rules as the rest of Firstlight.

These are not claims that Mobile UI cannot build the corresponding interface. Firstlight supplies the reusable product contract so each consuming application does not need to design that contract independently.

## Richer contracts around familiar controls

Firstlight also provides opinionated versions of controls that have clear Mobile UI counterparts:

| Firstlight component | Additional Firstlight contract |
| --- | --- |
| **Segmented** | Stable string or integer values instead of renderer indexes, nullable selection, rich options, individual disabled choices, field metadata, strict diagnostics, and PHP-authoritative acceptance. |
| **Badge** | Exactly one count or label, hidden zero, contextual accessibility for numeric counts, five semantic tones, strict authoring diagnostics, and a Material 3 `Badge` on Android. |
| **Text Field** | A single semantic field API with content and autofill hints, platform icon overrides, accessible trailing actions, clear and reveal affordances, and focused editing reconciliation that preserves selection and text composition. |
| **Date Picker** | A strict nullable `YYYY-MM-DD` value, inclusive bound validation, a temporary native draft, explicit confirmation, and a closed trigger that continues to display PHP's accepted value. |
| **Select** | Stable string or integer values, rich and individually disabled options, automatic searchable presentation for larger collections, field validation semantics, and PHP-authoritative selection. |
| **Slider** | Strict finite-number and step-grid validation, helper and error treatment, an optional spoken accessibility value, and explicit live, blur, or debounce synchronisation. |
| **Switch** | Consistent helper and error semantics, strict Boolean values, one accessible setting row, and visible state reconciled from the value accepted by PHP. |

## Server-authoritative application state

For discrete Firstlight controls, a native interaction proposes a semantic value and PHP publishes the accepted state. PHP may accept, transform, or reject the proposal. Reconciliation updates the control without echoing another event, and programmatic updates never masquerade as user interaction.

This differs from treating a local optimistic change as accepted application state. It is useful when permissions, validation, dependent fields, or business rules can reject or replace a choice. Focused text and continuous gestures still retain the native draft state needed for responsive editing and movement; Firstlight applies the state model appropriate to each type of control.

## Consistency and diagnostics

Across the catalogue, Firstlight applies the same rules:

- selection binds stable domain strings or integers rather than display labels or renderer indexes;
- `null` consistently represents no selection;
- rich options can separate values, labels, and disabled state;
- malformed options, duplicate or mixed values, impossible ranges, contradictory props, and unsupported sync modes fail before publication;
- labels, helper text, errors, required metadata, accessibility hints, disabled state, and minimum interaction targets follow familiar conventions;
- the same authored component works on iOS and Android while each platform retains its native geometry, motion, presentation, and accessibility behaviour.

Firstlight deliberately exposes fewer visual escape hatches and platform-only options than a general-purpose primitive library. This narrower surface keeps application code portable and allows the components to behave consistently as the native platforms evolve.

## Laravel-shaped PHP extensions

Some Firstlight behaviour is not a new control. Field validation (`ValidatesFields`) runs Laravel's `Validator` in PHP and publishes the first `MessageBag` message into each field's existing `error` slot. Form submission (`SubmitsForms`) validates, runs a PHP callable once, and optionally sends success Feedback. Action authorization (`AuthorizesActions`) evaluates Laravel Gate/Policy decisions for hide, disable, and action-time guards. List pagination (`PaginatesLists`) binds Laravel paginators to List `@refresh` and `@endReached`. Destructive list actions (`DestroysListItems`) authorize Confirmation Dialog deletion by stable keys and republish `$listItems`. Package locale chrome publishes Firstlight-owned chrome strings from one Laravel language file, and Date/Time pickers inherit omitted locale and timezone from the application. Transient Feedback is an application-level outcome queue, not a Blade field. See [Validate fields](../how-to/validate-fields.md), [Submit forms](../how-to/submit-forms.md), [Authorize actions](../how-to/authorize-actions.md), [Paginate lists](../how-to/paginate-lists.md), [Destroy list items](../how-to/destroy-list-items.md), and [Localize chrome](../how-to/localize.md).

Layout, typography, and generic media stay in Mobile UI, and navigation chrome stays in NativePHP Mobile. Firstlight does not ship columns, bottom tabs, input masks, or a schema/form builder. Compose screens with Blade, Mobile UI layout, and Firstlight fields.

### Custom tappable content and accessibility

NativePHP core owns arbitrary-content `native:pressable` surfaces. In the supported
Mobile 4.6 implementation, Blade preserves their authored accessibility labels,
but the generic native renderers do not consume those labels/hints or explicitly
map a button role and disabled/selected state. Android's clickable supplies implicit
Compose grouping; iOS uses a tap gesture without explicit accessibility grouping.
Adding `a11y-label` to a `native:column` does not turn it into one accessible
measurement either. Do not assume a visual tap target is accessible.

Use [Button](../components/button.md) for labelled actions and
[List Item](../components/list-item.md) for application rows when their content
contracts fit. Use [Metric](../components/metric.md) for a static grouped statistic.
There is no supported arbitrary-content accessible action wrapper in Firstlight;
custom interactive banners need an upstream core accessibility fix. Panels,
dividers and tile grids remain Mobile UI layout, not catalogue controls.

## Reusing Mobile UI when it already fits

Firstlight does not maintain duplicate native code merely to put every control under its own namespace. **Button** and **Progress** currently keep public Firstlight element types while delegating directly to the official Mobile UI SwiftUI and Material 3 renderers.

Firstlight adds strict authoring boundaries, documentation, tests, and catalogue consistency around those primitives, but does not claim new native rendering functionality. If a durable cross-platform requirement later outgrows an adapter, Firstlight can introduce paired renderers without changing consumer markup. Generally useful improvements remain good candidates for contribution to Mobile UI itself.

## One native ecosystem

Firstlight components use ordinary NativePHP UI component extension points. They publish Element Tree data, receive standard semantic events, inherit Mobile UI theme tokens, and require no custom imperative bridge functions or WebView-backed control layer.

The relationship is intentionally complementary: **NativePHP provides the native foundation and chrome; Firstlight provides the app-shell layer of controls, Laravel contracts, and recipes on top of it.**

Review the current [compatibility requirements](../reference/compatibility.md) before releasing an application, particularly where server-authoritative controls depend on unchanged-tree publication acknowledgements from NativePHP Mobile.
