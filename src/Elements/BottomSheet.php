<?php

namespace FirstlightUI\Elements;

use InvalidArgumentException;
use FirstlightUI\NativeComponent;
use FirstlightUI\Support\Chrome;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;

class BottomSheet extends \Native\Mobile\UI\Elements\BottomSheet
{
    protected string $type = 'firstlight.bottom-sheet';

    /** NativePHP closes it in a screen's last frame, so an open sheet never covers the next screen. */
    public static function closesOnNavigation(): bool
    {
        return true;
    }

    /** @var list<string> */
    private const UNSUPPORTED_ATTRIBUTES = [
        'value',
        'native:model',
        'detents',
        'dismissible',
        'dismissable',
        'horizontal',
        'label',
        'helper',
        'error',
        'required',
        'disabled',
        'loading',
        'options',
        'icon',
        'sync-mode',
        'syncMode',
        '_change',
        '_press',
        '_submit',
        '_longPress',
        '_doubleTap',
        '_pressDown',
        '_pressUp',
        '_navigate',
        '_refresh',
        'on-refresh',
        'onRefresh',
    ];

    public static function make(Element ...$children): static
    {
        $sheet = new static;
        $sheet->children = $children;

        return $sheet;
    }

    public function applyAttributes(array $attrs): void
    {
        foreach (self::UNSUPPORTED_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException(
                    "Firstlight Bottom Sheet does not support `{$attribute}`."
                );
            }
        }

        if (array_key_exists('visible', $attrs) && ! is_bool($attrs['visible'])) {
            throw new InvalidArgumentException(
                'Firstlight Bottom Sheet `visible` must be a boolean.'
            );
        }

        if (array_key_exists('pinned-footer', $attrs) || array_key_exists('pinnedFooter', $attrs)) {
            $value = array_key_exists('pinned-footer', $attrs) ? $attrs['pinned-footer'] : $attrs['pinnedFooter'];
            if (! is_bool($value)) {
                throw new InvalidArgumentException('Firstlight Bottom Sheet `pinned-footer` must be a boolean.');
            }
            $this->pinnedFooter($value);
        }

        foreach (['title', 'close-label', 'closeLabel', 'a11y-label', 'a11yLabel', 'a11y-hint', 'a11yHint'] as $attribute) {
            if (! array_key_exists($attribute, $attrs)) {
                continue;
            }

            if (! is_string($attrs[$attribute]) || trim($attrs[$attribute]) === '') {
                throw new InvalidArgumentException(
                    "Firstlight Bottom Sheet `{$attribute}` must be a non-empty string."
                );
            }
        }

        parent::applyAttributes($attrs);
    }

    public function pinnedFooter(bool $value = true): static
    {
        $this->sheetProps['pinned_footer'] = $value;

        return $this;
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        if (($this->sheetProps['pinned_footer'] ?? false) && count($this->children) < 2) {
            throw new InvalidArgumentException('Firstlight Bottom Sheet requires body and a final footer child when using `pinned-footer`.');
        }

        $title = $this->sheetProps['title'] ?? null;
        if ($title !== null && trim($title) === '') {
            throw new InvalidArgumentException('Firstlight Bottom Sheet `title` must be a non-empty string.');
        }

        if ($title === null && array_key_exists('close_label', $this->sheetProps)) {
            throw new InvalidArgumentException('Firstlight Bottom Sheet `close-label` needs a `title`: only a titled sheet has a close button.');
        }

        if (array_key_exists('close_label', $this->sheetProps) && trim($this->sheetProps['close_label']) === '') {
            throw new InvalidArgumentException('Firstlight Bottom Sheet `close-label` must be a non-empty string.');
        }

        if ($title !== null) {
            // The iOS close button is package chrome, in the app's language.
            $this->sheetProps['close_label'] ??= Chrome::string('close');
        }

        if ($this->dismissCallback === null || trim($this->dismissCallback) === '') {
            throw new InvalidArgumentException(
                'Firstlight Bottom Sheet requires `@dismiss`.'
            );
        }

        $props = parent::resolveProps($registry);

        NativeComponent::recordOverlay((int) ($props['on_dismiss'] ?? 0), ($props['visible'] ?? false) === true);

        return $props;
    }
}
