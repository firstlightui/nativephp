---
title: Add your first Firstlight component
description: Add a Segmented control whose selected value is managed by PHP.
type: tutorial
audience: consumer
sources:
  - src/Components/Segmented.php
  - src/Elements/Segmented.php
  - tests/Feature/SegmentedElementTest.php
---

# Add Your First Firstlight Component

This tutorial adds a native Segmented control for choosing a queue. The same EDGE markup renders as a platform-appropriate control on iOS and Android.

## Define the state

Add the selected value and stable value-to-label options to your EDGE component:

```php
public string $queue = 'mine';

public array $queueOptions = [
    'mine' => 'Mine',
    'all' => 'All',
];
```

The array keys are the public values stored in `$queue`; the labels are display text.

## Render the control

Bind the property with `native:model`:

```blade
<firstlight:segmented
    :options="$queueOptions"
    native:model="queue"
    label="Queue"
    helper="Choose the active queue."
/>
```

Run the application natively. A selection emits its stable value to PHP, and the published `$queue` value determines the visible selection.

## Handle a selection explicitly

Use `@change` when the selection should call a method instead of directly synchronising a property:

```blade
<firstlight:segmented
    :options="$queueOptions"
    :value="$queue"
    @change="selectQueue"
    label="Queue"
/>
```

The method receives the selected string value. See the [Segmented reference](../components/segmented.md) for integer values, disabled choices, accessibility, and validation rules. When the screen should show Laravel messages on Firstlight fields, follow [Validate fields](../how-to/validate-fields.md).
