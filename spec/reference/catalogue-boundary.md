---
title: Firstlight catalogue boundary
description: What belongs in the Firstlight component catalogue, what belongs as a PHP SuperNative extension, and what remains NativePHP Mobile UI.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/architecture/package.md
  - spec/reference/field-validation.md
  - spec/reference/form-submit.md
  - spec/reference/action-authorization.md
  - spec/reference/list-pagination.md
  - spec/reference/destructive-list-actions.md
  - spec/components/transient-feedback.md
  - spec/workflows/adding-components.md
  - docs/concepts/firstlight-and-mobile-ui.md
  - nativephp.json
---

# Firstlight Catalogue Boundary

Firstlight is a curated form, selection, display, and action layer on NativePHP SuperNative. It is not a second Mobile UI, a layout framework, or a Filament-style schema builder.

Article VIII requires small, proven expansion. New public `<firstlight:...>` tags are for semantic controls that need a durable, equal-quality iOS and Android contract. Laravel familiarity is delivered by PHP services that publish through those existing controls.

## Catalogue (public tags)

The catalogue is the set of types registered in `nativephp.json`. Add a tag only when the work is a control with a public contract, paired or adapted native expression, tests, documentation, and review.

Typical catalogue members: fields, choices, actions, status, lists, and presentation (Alert Dialog, Confirmation Dialog, Modal, Bottom Sheet).

## PHP SuperNative extensions (no new tag required)

Extensions reuse existing elements, chrome, or facades. Current examples:

- **Transient Feedback** — `Feedback` facade, process-local store, package chrome, native queue. No consumer-authored host tag.
- **Field validation** — `ValidatesFields` plus `FieldErrorBinder` filling existing `error` slots. Specified in [Field validation](field-validation.md).
- **Form submission** — `SubmitsForms::submit()` over Button `@press`, validation, and Feedback. Specified in [Form submission](form-submit.md).
- **Action authorization** — `AuthorizesActions` over Gate/Policy with authored hide, disable, and confirmation. Specified in [Action authorization](action-authorization.md).
- **List pagination** — `PaginatesLists` over List `@refresh` and `@end-reached`. Specified in [List pagination](list-pagination.md).
- **Destructive list actions** — `DestroysListItems` over stable List Item keys, Confirmation Dialog, Gate, Feedback, and `$listItems` republish. Specified in [Destructive list actions](destructive-list-actions.md).

Further Laravel-shaped work (media fields, notification bridges) must follow the same pattern: PHP owns the Laravel API; existing Firstlight or Mobile UI presentation owns the pixels. A new catalogue component is justified only when no current control can express the contract, as a media field would.

## Out of catalogue

Do not scaffold, adapt, or document these as Firstlight components:

- **Layout primitives** — columns, rows, stacks, spacers, generic text, unconstrained style bags. Mobile UI already owns layout and typography.
- **Navigation chrome** — bottom tabs, top bars, navigation stacks, FABs, route shells. Mobile UI owns application chrome. Laravel `route()` and middleware stay in the application.
- **Input masks and formatters** — client-side mask engines, currency widgets, or parallel formatted-value props on Text Field. Prefer `keyboard` / `content-type` plus PHP `prepareForValidation()`.
- **Schema / form builders** — Filament- or JSON-schema-driven field factories, `->visible()` DSLs, or a public `<firstlight:form>` layout container. Blade `@if`, server-authoritative republish, Button `loading`, and `ValidatesFields` already compose screens.
- **Auth and onboarding kits** — login, register, or settings frameworks. Compose List, Switch, Text Field, and Button in the application.
- **Client-side validation** — Swift/Kotlin rule engines, HTML forms, or Livewire `@error` clones that bypass the field `error` slot.

When an official Mobile UI primitive already satisfies the intent, write an adapter or tell consumers to use Mobile UI. Do not duplicate it under a Firstlight tag merely to enlarge the catalogue.

## Decision test

Before adding a public control, answer:

1. Is this a user-facing control with a semantic contract Mobile UI cannot already meet?
2. Can a PHP service plus existing Firstlight fields, Feedback, Button, List, or Confirmation Dialog express it?
3. Would shipping it require layout, navigation, masks, or a schema builder?

If (2) is yes or (3) is yes, it is not a catalogue component. Implement or defer it as a PHP extension, or leave it to Mobile UI.
