---
title: Authorize Firstlight actions
description: Apply Laravel Gate and Policy decisions to Firstlight actions with explicit hide, disable, and destructive confirmation patterns.
type: how-to
audience: consumer
sources:
  - src/Authorization/GateEvaluator.php
  - src/Concerns/AuthorizesActions.php
  - src/NativeComponent.php
  - src/Facades/Feedback.php
  - src/Elements/Button.php
  - src/Elements/IconButton.php
  - src/Elements/ListItem.php
  - src/Elements/ConfirmationDialog.php
  - tests/Feature/AuthorizesActionsTest.php
---

# Authorize Firstlight Actions

Extend `FirstlightUI\NativeComponent` (or `use AuthorizesActions`) so a screen
can evaluate the current Laravel Gate or Policy before publishing or performing
an action:

```php
use App\Models\Post;
use FirstlightUI\NativeComponent;

class PostScreen extends NativeComponent
{
    public Post $post;
    public bool $confirmingDeletion = false;

    public function updatePost(): void
    {
        if (! $this->authorize('update', $this->post)) {
            return;
        }

        $this->post->save();
    }
}
```

`allows()` and `denies()` return booleans. `authorize()` also returns a
boolean; unlike Laravel's throwing Gate method, a denial publishes danger
Feedback with `This action is unauthorized.` and returns `false`. Keep the
guard at the start of every PHP action, even when the published control is
already hidden or disabled, because authorization can change after a frame is
published.

## Choose hide or disable

Hide an ordinary action when its absence is unsurprising:

```blade
@if ($this->allows('edit', $post))
    <firstlight:button @press="editPost">
        Edit post
    </firstlight:button>
@endif
```

Disable an action when preserving its location helps explain the available
workflow:

```blade
<firstlight:list-item
    headline="Publishing settings"
    supporting="Only editors can change publication access"
    :disabled="$this->denies('update', $post)"
    @press="openPublishingSettings"
/>
```

Button, Icon Button, and List Item all accept the existing `disabled` prop.
Firstlight does not add a `can` attribute or evaluate authorization in a
native renderer.

## Keep destructive actions visible

Never silently omit a destructive action. Keep the Button, Icon Button, or
List Item visible and disable it when denied. Supporting copy should explain
why the action is unavailable when the surrounding interface does not make
that clear.

```blade
<firstlight:button
    variant="destructive"
    :disabled="$this->denies('delete', $post)"
    @press="requestDeletion"
>
    Delete post
</firstlight:button>
```

Confirmation Dialog has no `disabled` or `loading` prop. Authorize before
setting its `visible` state, and authorize again when confirmation arrives:

```php
public function requestDeletion(): void
{
    if (! $this->authorize('delete', $this->post)) {
        $this->confirmingDeletion = false;

        return;
    }

    $this->confirmingDeletion = true;
}

public function deletePost(): void
{
    $this->confirmingDeletion = false;

    if (! $this->authorize('delete', $this->post)) {
        return;
    }

    $this->post->delete();
}

public function cancelDeletion(): void
{
    $this->confirmingDeletion = false;
}
```

```blade
<firstlight:confirmation-dialog
    :visible="$confirmingDeletion"
    title="Delete post?"
    message="This action cannot be undone."
    confirm-label="Delete"
    cancel-label="Keep post"
    tone="destructive"
    @press="deletePost"
    @dismiss="cancelDeletion"
/>
```

The denied request does not open the dialog, and `authorize()` supplies danger
Feedback instead of allowing an uncaught `AuthorizationException` to reach
NativePHP's error overlay.
