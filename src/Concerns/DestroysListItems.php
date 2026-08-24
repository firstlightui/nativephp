<?php

namespace FirstlightUI\Concerns;

use FirstlightUI\Facades\Feedback;
use InvalidArgumentException;

trait DestroysListItems
{
    public bool $confirmingListDestruction = false;

    public mixed $pendingListDestructionKey = null;

    /**
     * Authorize and open Confirmation Dialog for the item identified by `$key`.
     * Bind List Item `@press` to a screen method that calls this with a stable
     * domain key, never a renderer index.
     */
    public function requestDestructiveListAction(mixed $key, string $ability = 'delete'): bool
    {
        $item = $this->resolveDestructiveListItem($key);

        if ($item === null) {
            $this->clearDestructiveListAction();

            return false;
        }

        if (! $this->authorize($ability, $item)) {
            $this->clearDestructiveListAction();

            return false;
        }

        $this->pendingListDestructionKey = $key;
        $this->confirmingListDestruction = true;

        return true;
    }

    /**
     * Close Confirmation Dialog without mutating the list.
     * Bind Confirmation Dialog `@dismiss` to a screen method that calls this.
     */
    public function cancelDestructiveListAction(): void
    {
        $this->clearDestructiveListAction();
    }

    /**
     * Authorize again, destroy the pending item, remove it from `$listItems`,
     * and optionally publish success Feedback. Bind Confirmation Dialog `@press`
     * to a screen method that supplies the destroy callable.
     *
     * @param  callable(mixed $item): void  $destroy
     */
    public function confirmDestructiveListAction(
        callable $destroy,
        string $ability = 'delete',
        ?string $successMessage = null,
    ): bool {
        $key = $this->pendingListDestructionKey;
        $this->clearDestructiveListAction();

        if ($key === null) {
            return false;
        }

        $item = $this->resolveDestructiveListItem($key);

        if ($item === null) {
            return false;
        }

        if (! $this->authorize($ability, $item)) {
            return false;
        }

        $destroy($item);
        $this->forgetListItemByKey($key);

        if ($successMessage !== null && trim($successMessage) !== '') {
            Feedback::success($successMessage)->send();
        }

        return true;
    }

    /**
     * Resolve the pending row by stable key. Override to load from the database
     * instead of walking `$listItems`.
     */
    protected function resolveDestructiveListItem(mixed $key): mixed
    {
        if (! property_exists($this, 'listItems') || ! is_array($this->listItems)) {
            return null;
        }

        foreach ($this->listItems as $item) {
            if ($this->listDestructionKeysMatch($this->destructiveListItemKey($item), $key)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Stable identity for a list row. Override when items do not expose
     * `getKey()`, `id`, or an `id` array key.
     */
    protected function destructiveListItemKey(mixed $item): mixed
    {
        if (is_object($item)) {
            if (method_exists($item, 'getKey')) {
                return $item->getKey();
            }

            if (isset($item->id)) {
                return $item->id;
            }
        }

        if (is_array($item) && array_key_exists('id', $item)) {
            return $item['id'];
        }

        throw new InvalidArgumentException(
            'Firstlight destructive list actions require each list item to expose getKey(), an id property, or an id array key. Override destructiveListItemKey() for a custom stable key.',
        );
    }

    protected function forgetListItemByKey(mixed $key): void
    {
        if (! property_exists($this, 'listItems') || ! is_array($this->listItems)) {
            return;
        }

        $this->listItems = array_values(array_filter(
            $this->listItems,
            fn (mixed $item): bool => ! $this->listDestructionKeysMatch(
                $this->destructiveListItemKey($item),
                $key,
            ),
        ));
    }

    protected function clearDestructiveListAction(): void
    {
        $this->confirmingListDestruction = false;
        $this->pendingListDestructionKey = null;
    }

    protected function listDestructionKeysMatch(mixed $left, mixed $right): bool
    {
        return $left == $right;
    }
}
