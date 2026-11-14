<?php

namespace FirstlightUI\Elements;

use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;

/**
 * A screen's bottom toolbar of icon actions. Takes no space in the screen's
 * layout: iOS attaches the items to the navigation stack's bottom bar, and
 * Android floats a Material 3 floating toolbar over the screen.
 */
final class Toolbar extends Element
{
    protected string $type = 'firstlight.toolbar';

    public const MAX_ITEMS = 5;

    /** @var list<string> */
    private const UNSUPPORTED_ATTRIBUTES = [
        'label', 'icon', 'value', 'disabled', 'loading', 'variant', 'glass', 'haptic',
        'a11y-label', 'a11yLabel', 'a11y-hint', 'a11yHint',
        '_press', '_change', '_submit', '_longPress', '_doubleTap', '_navigate',
    ];

    public static function make(): static
    {
        return new static;
    }

    public function applyAttributes(array $attrs): void
    {
        foreach (self::UNSUPPORTED_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException("Firstlight Toolbar does not support `{$attribute}`.");
            }
        }
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        $regular = 0;
        $primary = 0;

        foreach ($this->children as $child) {
            if (! $child instanceof ToolbarItem) {
                throw new InvalidArgumentException('Firstlight Toolbar accepts only Toolbar Item children.');
            }

            $child->isPrimary() ? $primary++ : $regular++;
        }

        if ($regular + $primary === 0) {
            throw new InvalidArgumentException('Firstlight Toolbar requires at least one Toolbar Item.');
        }
        if ($regular > self::MAX_ITEMS) {
            throw new InvalidArgumentException('Firstlight Toolbar takes at most '.self::MAX_ITEMS.' items besides the primary one.');
        }
        if ($primary > 1) {
            throw new InvalidArgumentException('Firstlight Toolbar takes at most one `primary` item.');
        }

        return [];
    }

    /** Out of the screen's flow, so the toolbar never shifts content. */
    public function getLayout(): array
    {
        return array_merge(parent::getLayout(), ['position_type' => 1, 'width' => 0.0, 'height' => 0.0]);
    }

    public function getStyle(): array
    {
        return [];
    }
}
