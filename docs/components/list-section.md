---
title: List Section
description: Grouped headers, footers, and List Item rows inside a parent List container.
type: reference
audience: consumer
sources:
  - spec/components/list-section.md
  - nativephp.json
  - src/Components/ListSection.php
  - src/Elements/ListSection.php
  - tests/Feature/ListSectionElementTest.php
---

# List Section

List Section groups related [List Item](list-item.md) rows under an optional
header and footer inside a parent [List](list.md).

## Complete example

```blade
<firstlight:list>
    <firstlight:list-section header="Account" footer="Signed in as Alex">
        <firstlight:list-item headline="Profile" @press="openProfile" />
        <firstlight:list-item headline="Security" @press="openSecurity" />
    </firstlight:list-section>
</firstlight:list>
```

List Section is a paired container and must live inside List. A section
authored on its own does not render application content.

## Props

| API | Accepted type | Purpose |
| --- | --- | --- |
| `header` | non-empty `string` | Optional section title. |
| `footer` | non-empty `string` | Optional section footnote. |

At least one List Item child is required.

## Composition rules

- Child slot accepts only `firstlight:list-item`.
- Nested sections, refresh hooks, separators, and field props belong on the
  parent List instead.

## Platform expression

The parent List renderer consumes List Section inline:

- iOS renders SwiftUI section headers and footers.
- Android renders sticky headers, grouped card rows, and footer text.

## Adapter note

The public tag is `<firstlight:list-section>`, but the published wire type
remains `list_section` so the delegated Mobile UI list renderer can apply
grouped section styling.

## Screenshots

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | ![List Section on iOS in light mode](../screenshots/list-section/ios-light.png) | ![List Section on iOS in dark mode](../screenshots/list-section/ios-dark.png) |
| Android | ![List Section on Android in light mode](../screenshots/list-section/android-light.png) | ![List Section on Android in dark mode](../screenshots/list-section/android-dark.png) |
