---
title: List Section component contract
description: Public grouped section headers, footers, child composition, and adapter contract for Firstlight List Section.
status: current
audience: maintainer
sources:
  - Constitution.md
  - nativephp.json
  - vendor/nativephp/mobile-ui/src/Elements/ListSection.php
  - vendor/nativephp/mobile-ui/resources/ios/NativeUIListRenderer.swift
  - vendor/nativephp/mobile-ui/resources/android/ListRenderer.kt
  - spec/components/list-item.md
  - spec/components/list-section.md
---

# List Section Component Contract

## Purpose and state class

`<firstlight:list-section>` groups List Item rows under an optional header and
footer inside a parent List. It is an action/display structural child: PHP
owns the section copy while native code owns grouped or plain section chrome.

List Section is not a standalone scroll surface. A section authored outside a
List does not render application content.

## Public API

```blade
<firstlight:list-section header="Account" footer="Signed in as Alex">
    <firstlight:list-item headline="Profile" @press="openProfile" />
    <firstlight:list-item headline="Security" @press="openSecurity" />
</firstlight:list-section>
```

| Prop | Contract |
| --- | --- |
| `header` | Optional non-empty section title text. |
| `footer` | Optional non-empty section footnote text. |

List Section is a paired container. Its child slot accepts only List Item
elements. At least one List Item child is required.

## Empty, action, and failure behaviour

Header and footer may both be absent when at least one row is present. When
authored, header and footer must be non-empty strings.

A section with zero List Item children fails before publication. Unsupported
child types and field or action props on the section itself fail with
actionable `InvalidArgumentException` messages.

## Accessibility

Headers and footers render as native text associated with their rows through
platform list semantics. Row interaction remains on List Item.

## Platform expression

List Section is consumed inline by the parent List renderer:

- iOS uses SwiftUI `Section` headers and footers inside the delegated list.
- Android uses sticky headers, grouped card rows, and footer text inside the
  delegated `LazyColumn`.

## Adapter decision

Mobile UI 0.4 `list_section` is a structural collector consumed only by `list`.
Firstlight exposes `<firstlight:list-section>` while publishing the upstream
wire type `list_section` so the delegated list renderer recognizes grouped
sections.

## Evidence boundary

Development evidence requires Pest contracts, manifest registration, showcase
fixtures nested inside List, public documentation, and constitutional review.
