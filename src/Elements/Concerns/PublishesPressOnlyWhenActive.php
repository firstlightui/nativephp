<?php

namespace FirstlightUI\Elements\Concerns;

use Native\Mobile\Edge\CallbackRegistry;

/**
 * Leaves a control's press off its published node while it cannot be pressed.
 *
 * Mobile attaches its own tap gesture to every node that carries a press. The
 * Firstlight renderer's button sits inside it and ignores taps while disabled,
 * so each tap fell through to Mobile's gesture and ran `@press` anyway. With no
 * press on the node there is nothing for that gesture to run, and the renderer
 * already reads a missing press as disabled.
 */
trait PublishesPressOnlyWhenActive
{
    abstract protected function acceptsPress(): bool;

    public function toArray(
        CallbackRegistry $registry,
        int &$nextId = 1,
        string $parentKeyPath = '',
        int $indexInParent = 0,
        array &$emittedIds = [],
        array &$lastNodeHashes = []
    ): array {
        $node = parent::toArray($registry, $nextId, $parentKeyPath, $indexInParent, $emittedIds, $lastNodeHashes);

        if (! $this->acceptsPress()) {
            unset($node['on_press'], $node['on_long_press']);
        }

        return $node;
    }
}
