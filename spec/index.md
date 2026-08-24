---
title: Firstlight maintained specifications
description: Index of current repository contracts for Firstlight maintainers and development agents.
status: current
audience: maintainer
sources:
  - Constitution.md
  - spec/documentation-constitution.md
---

# Firstlight Maintained Specifications

- [Activity Indicator component contract](components/activity-indicator.md) — Defines presence-based indeterminate activity, semantic sizes, accessibility announcements, and the paired-renderer boundary.
- [Button component contract](components/button.md) — Defines the labelled action API, adapter boundary, state, diagnostics, accessibility, and renderer exit criteria.
- [Callout component contract](components/callout.md) — Defines persistent semantic messages, optional actions, tone-owned affordances, accessibility, and the paired-renderer boundary.
- [Transient Feedback maintained contract](components/transient-feedback.md) — Defines the service API, package-owned wire, native queue, lifecycle, accessibility, failures, and evidence boundary.
- [Checkbox component contract](components/checkbox.md) — Defines strict Boolean field semantics, server-authoritative proposals, accessibility, and the paired-renderer boundary.
- [Media component contract](components/media.md) — Defines image-or-document MediaValue Storage, crop composition, ValidatesFields participation, and paired crop sheets.
- [Badge component contract](components/badge.md) — Defines compact count and marker semantics, strict display sources, tones, accessibility, and the paired-renderer decision.
- [Choice Group component contract](components/choice-group.md) — Defines visible radio or checkbox choice rows, stable values, state timing, accessibility, and native expression.
- [Alert Dialog component contract](components/alert-dialog.md) — Defines server-controlled one-action acknowledgement, dismissal, accessibility, and the paired-renderer boundary.
- [Confirmation Dialog component contract](components/confirmation-dialog.md) — Defines server-controlled presentation, native action roles, dismissal, accessibility, and the paired-renderer boundary.
- [Modal component contract](components/modal.md) — Defines server-controlled full-screen presentation, dismissal, content ownership, and the Mobile UI adapter boundary.
- [Bottom Sheet component contract](components/bottom-sheet.md) — Defines server-controlled sheet presentation, dismissal, content ownership, and the Mobile UI adapter boundary.
- [Date Picker component contract](components/date-picker.md) — Defines canonical nullable dates, inclusive bounds, native draft confirmation, internationalisation, accessibility, and the paired-renderer boundary.
- [Time Picker component contract](components/time-picker.md) — Defines canonical nullable wall-clock times, native draft confirmation, internationalisation, accessibility, and the paired-renderer boundary.
- [Select component contract](components/select.md) — Defines stable single-selection values, automatic searchable presentation, state timing, accessibility, and the paired-renderer boundary.
- [Slider component contract](components/slider.md) — Defines strict Float-compatible numeric grids, native gesture drafts, synchronization policies, accessibility, and the paired-renderer boundary.
- [Stepper component contract](components/stepper.md) — Defines exact integer or float grids, bounded server-authoritative proposals, accessibility, and the paired-renderer boundary.
- [Icon Button component contract](components/icon-button.md) — Defines compact action semantics, platform icon resolution, strict accessibility, state, and the paired-renderer decision.
- [List Item component contract](components/list-item.md) — Defines narrow application-row content, action, disabled state, icon resolution, accessibility, and the paired-renderer decision.
- [Progress component contract](components/progress.md) — Defines determinate and indeterminate display semantics, strict values, accessibility, and the Mobile UI adapter boundary.
- [Shared icon contract](reference/icons.md) — Defines cross-control icon names, platform overrides, resolution, accessibility, and validation.
- [Field validation](reference/field-validation.md) — Defines Laravel Validator binding onto field `error` slots without a native rule engine.
- [Form submission](reference/form-submit.md) — Defines guarded validation, form actions, success Feedback, and honest Button state timing.
- [Action authorization](reference/action-authorization.md) — Defines Laravel Gate and Policy evaluation for hiding, disabling, and guarding Firstlight actions.
- [List pagination](reference/list-pagination.md) — Defines Laravel paginator binding onto List refresh and end-reached without a new catalogue tag.
- [Destructive list actions](reference/destructive-list-actions.md) — Defines authorized Confirmation Dialog destruction of List rows by stable keys and `$listItems` republish.
- [Catalogue boundary](reference/catalogue-boundary.md) — Defines what belongs in the Firstlight catalogue, what is a PHP SuperNative extension, and what stays in Mobile UI.
- [Pill Group component contract](components/pill-group.md) — Defines compact choice values, selection modes, state timing, accessibility, and native expression.
- [Status Label component contract](components/status-label.md) — Defines the display-only API, tones, diagnostics, accessibility, and native expression.
- [Text Area component contract](components/text-area.md) — Defines focused multiline editing, line bounds, sync policies, accessibility, and the paired-renderer boundary.
- [Package architecture](architecture/package.md) — Maps the public PHP API to the iOS and Android renderer lifecycle.
- [Adding components](workflows/adding-components.md) — Defines the evidence-based workflow for delivering a paired Firstlight component.
- [Testing](workflows/testing.md) — Defines useful contract, platform, showcase, and tooling verification.
- [Showcase and screenshots](workflows/showcase-and-screenshots.md) — Defines stable showcase fixtures and the four-image documentation capture workflow.
- [Alpha catalogue development screenshot evidence](reviews/2026-08-05-alpha-screenshot-evidence.md) — Records the approved four-image development matrix for every alpha component and the remaining release gates.
- [Repository layout](reference/repository-layout.md) — Maps package, documentation, tooling, skill, and showcase paths to their responsibilities.
