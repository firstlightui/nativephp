---
title: Firstlight UI documentation
description: Current guides, concepts, and reference documentation for developers using Firstlight UI.
type: reference
audience: consumer
sources:
  - composer.json
  - nativephp.json
---

# Firstlight UI Documentation

- [Installation](getting-started/installation.md) — Install Firstlight and make its native renderers available to a NativePHP application.
- [Add your first component](getting-started/first-component.md) — Add a complete Segmented control with server-backed state.
- [Validate fields](how-to/validate-fields.md) — Show Laravel validation messages on Firstlight fields with `validate()`, `validateOnly()`, and Form Requests.
- [Submit forms](how-to/submit-forms.md) — Validate Firstlight fields, run a PHP action once, and publish success Feedback.
- [Authorize actions](how-to/authorize-actions.md) — Hide, disable, or guard Firstlight actions with Laravel Gate and Policy decisions.
- [Theming](getting-started/theming.md) — Configure the semantic NativePHP theme tokens inherited by Firstlight components.
- [SuperNative components](concepts/supernative-components.md) — Understand how an EDGE component becomes genuine platform UI.
- [Server-authoritative state](concepts/server-authoritative-state.md) — Understand interaction, PHP acceptance, and native reconciliation.
- [Firstlight and NativePHP Mobile UI](concepts/firstlight-and-mobile-ui.md) — Understand how Firstlight complements Mobile UI with a focused form and control layer.
- [Activity Indicator](components/activity-indicator.md) — Communicate indeterminate native activity with semantic sizes and one polite appearance announcement.
- [Button](components/button.md) — Look up labelled actions, semantic variants, loading and disabled states, accessibility, and adapter behaviour.
- [Callout](components/callout.md) — Present persistent semantic messages with one optional labelled action.
- [Transient Feedback](components/transient-feedback.md) — Publish queued app-level outcomes with semantic tones, optional actions, and automatic or held lifetime.
- [Badge](components/badge.md) — Present compact display-only counts or short markers with semantic tones and contextual accessibility.
- [Checkbox](components/checkbox.md) — Configure strict Boolean form and checklist state with server-authoritative proposals.
- [Choice Group](components/choice-group.md) — Configure visible single-radio or multiple-checkbox choice rows with stable values.
- [Confirmation Dialog](components/confirmation-dialog.md) — Ask for one native confirmation with explicit cancellation and destructive action semantics.
- [Modal](components/modal.md) — Present authored content in a full-screen native overlay with server-controlled dismissal.
- [Bottom Sheet](components/bottom-sheet.md) — Present authored content in a native bottom sheet with server-controlled dismissal.
- [Date Picker](components/date-picker.md) — Choose strict nullable calendar dates with inclusive bounds and native confirmation.
- [Time Picker](components/time-picker.md) — Choose strict nullable wall-clock times with localized native confirmation.
- [Icon Button](components/icon-button.md) — Configure compact icon-only actions, platform icon overrides, semantic variants, states, and accessibility.
- [List Item](components/list-item.md) — Configure tappable application rows with leading identity, trailing affordances, disabled behaviour, and platform icon overrides.
- [List](components/list.md) — Compose finite vertical collections of List Item rows with optional grouped sections and refresh or pagination hooks.
- [List Section](components/list-section.md) — Group List Item rows with optional headers and footers inside a parent List.
- [Pill Group](components/pill-group.md) — Configure compact single- or multiple-selection native options.
- [Progress](components/progress.md) — Communicate determinate or indeterminate work with strict values and native accessibility.
- [Segmented](components/segmented.md) — Look up the Segmented control's complete public contract and platform evidence.
- [Search Field](components/search-field.md) — Configure native query entry, clear, submission, and synchronisation behaviour.
- [Select](components/select.md) — Choose one stable string or integer value with automatic searchable presentation for larger option sets.
- [Slider](components/slider.md) — Choose one strictly validated numeric value from an evenly spaced native range.
- [Stepper](components/stepper.md) — Increment or decrement an exact bounded number while PHP remains authoritative.
- [Status Label](components/status-label.md) — Look up display-only status text, semantic tones, accessibility, and platform evidence.
- [Text Field](components/text-field.md) — Configure native single-line editing, input hints, icons, and synchronisation.
- [Text Area](components/text-area.md) — Configure native multiline editing, line bounds, validation, accessibility, and synchronisation.
- [Switch](components/switch.md) — Configure native boolean settings, server-authoritative state, and accessibility.
- [ValidatesFields](reference/validates-fields.md) — Look up the public `validate()` contract, MessageBag binding, and current limitations.
- [Compatibility](reference/compatibility.md) — Check the currently supported PHP, NativePHP, iOS, Android, and Swift versions.
