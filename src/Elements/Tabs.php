<?php

namespace FirstlightUI\Elements;

use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Throwable;

final class Tabs extends Element
{
    protected string $type = 'firstlight.tabs';

    private const MIN_TABS = 2;

    private const MAX_TABS = 5;

    private const RICH_FIELDS = ['value', 'label', 'badge', 'badge-a11y-label', 'badgeA11yLabel', 'disabled'];

    private const ICON_FIELDS = ['icon', 'icon-ios', 'iconIos', 'icon-android', 'iconAndroid'];

    private const INCOMPATIBLE = [
        'options' => 'use `tabs` for the tab collection',
        'helper' => 'tabs are navigation, not a form field',
        'error' => 'tabs are navigation, not a form field',
        'required' => 'tabs are navigation, not a form field',
        'placeholder' => 'tabs are navigation, not a form field',
        'multiple' => 'exactly one tab is visible at a time',
        'icon' => 'tab icons are deferred until iOS segments can show an icon and a title together',
        'variant' => 'native platform styling is fixed',
        'scrollable' => 'tab groups are limited to five tabs',
    ];

    /** @var array<int|string, mixed> */
    private array $rawTabs = [];

    private ?string $value = null;

    private string $label = '';

    private bool $disabled = false;

    private ?string $changeCallback = null;

    public static function make(): static
    {
        return new static;
    }

    public function applyAttributes(array $attrs): void
    {
        foreach (self::INCOMPATIBLE as $attribute => $reason) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException("Firstlight Tabs does not support `{$attribute}`: {$reason}.");
            }
        }

        if (array_key_exists('tabs', $attrs)) {
            if (! is_array($attrs['tabs'])) {
                throw new InvalidArgumentException('Firstlight Tabs `tabs` must be an array.');
            }
            $this->tabs($attrs['tabs']);
        }

        if (array_key_exists('value', $attrs)) {
            $this->value($attrs['value']);
        }

        if (array_key_exists('label', $attrs)) {
            $this->label((string) ($attrs['label'] ?? ''));
        }

        if (array_key_exists('disabled', $attrs)) {
            $this->disabled((bool) $attrs['disabled']);
        }

        if (isset($attrs['sync-mode']) || isset($attrs['syncMode'])) {
            $this->syncMode((string) ($attrs['sync-mode'] ?? $attrs['syncMode']));
        }

        $this->applyA11yAttributes($attrs);
    }

    /** @param array<int|string, mixed> $tabs */
    public function tabs(array $tabs): static
    {
        $this->rawTabs = $tabs;

        return $this;
    }

    public function value(mixed $value): static
    {
        if (! is_string($value) && $value !== null) {
            throw new InvalidArgumentException('Firstlight Tabs selected value must be a string or null.');
        }

        $this->value = $value;

        return $this;
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    public function syncMode(string $mode): static
    {
        if ($mode !== 'live') {
            throw new InvalidArgumentException(
                "Firstlight Tabs commits immediately, so the `{$mode}` sync mode has no effect. "
                .'Use plain `native:model` (or `native:model.live`) and defer in your component if needed.'
            );
        }

        return $this;
    }

    public function onChange(string $method): static
    {
        $this->changeCallback = $method;

        return $this;
    }

    protected function resolveProps(CallbackRegistry $registry): array
    {
        $tabs = $this->normalizedTabs();
        $this->warnWhenUnnamed();

        $a11yLabel = trim((string) ($this->extraProps['a11y_label'] ?? ''));

        $props = [
            'tab_values' => array_column($tabs, 'value'),
            'tab_labels' => array_column($tabs, 'label'),
            'tab_badges' => array_column($tabs, 'badge'),
            'tab_a11y_labels' => array_column($tabs, 'a11y_label'),
            'tab_enabled' => array_column($tabs, 'enabled'),
            'has_selection' => $this->value !== null,
            'selected_value' => $this->value ?? '',
            'disabled' => $this->disabled || $tabs === [],
            'a11y_label' => $a11yLabel !== '' ? $a11yLabel : $this->label,
        ];

        if ($this->changeCallback !== null) {
            $props['on_change'] = $registry->register($this->changeCallback);
        }

        return $props;
    }

    /** @return list<array{value: string, label: string, badge: string, a11y_label: string, enabled: string}> */
    private function normalizedTabs(): array
    {
        $count = count($this->rawTabs);

        if ($count !== 0 && ($count < self::MIN_TABS || $count > self::MAX_TABS)) {
            throw new InvalidArgumentException(
                "Firstlight Tabs requires between 2 and 5 tabs; {$count} given. "
                .'Render a single section without tabs, or move extra sections into rows on a Manage tab.'
            );
        }

        $entries = [];

        if (array_is_list($this->rawTabs) && $count > 0 && is_array($this->rawTabs[0])) {
            foreach ($this->rawTabs as $index => $entry) {
                $entries[] = $this->richTab($entry, $index);
            }
        } else {
            $index = 0;
            foreach ($this->rawTabs as $value => $label) {
                $entries[] = ['value' => $value, 'label' => $label, 'badge' => null, 'badge_a11y_label' => null, 'disabled' => false, 'index' => $index++];
            }
        }

        $seen = [];
        $tabs = [];

        foreach ($entries as $entry) {
            $index = $entry['index'];
            $value = $entry['value'];

            if (! is_string($value) || $value === '') {
                throw $this->invalidTab($index, 'values must be non-empty strings. PHP turns numeric-string keys such as \'1\' into integers, so use a word such as \'overview\'');
            }

            if (isset($seen[$value])) {
                throw $this->invalidTab($index, "duplicate value [{$value}]");
            }
            $seen[$value] = true;

            if (! is_string($entry['label']) || trim($entry['label']) === '') {
                throw $this->invalidTab($index, 'label must not be blank');
            }

            $badge = match (true) {
                $entry['badge'] === null, $entry['badge'] === 0 => '',
                $entry['badge'] > 99 => '99+',
                default => (string) $entry['badge'],
            };

            $badgeA11y = is_string($entry['badge_a11y_label']) && trim($entry['badge_a11y_label']) !== ''
                ? $entry['badge_a11y_label']
                : $badge;

            $tabs[] = [
                'value' => $value,
                'label' => $entry['label'],
                'badge' => $badge,
                'a11y_label' => $badge === '' ? $entry['label'] : "{$entry['label']}, {$badgeA11y}",
                'enabled' => $entry['disabled'] ? '0' : '1',
            ];
        }

        return $tabs;
    }

    /** @return array{value: mixed, label: mixed, badge: ?int, badge_a11y_label: ?string, disabled: bool, index: int} */
    private function richTab(mixed $entry, int $index): array
    {
        if (! is_array($entry)) {
            throw $this->invalidTab($index, 'rich tabs must all be arrays');
        }

        $icons = array_intersect(array_keys($entry), self::ICON_FIELDS);
        if ($icons !== []) {
            throw $this->invalidTab($index, 'Tab icons are not supported yet because an iOS segment shows a title or an image, not both');
        }

        $unknown = array_diff(array_keys($entry), self::RICH_FIELDS);
        if ($unknown !== []) {
            throw $this->invalidTab($index, 'unknown field: '.implode(', ', $unknown));
        }

        if (! array_key_exists('value', $entry)) {
            throw $this->invalidTab($index, 'value is required');
        }

        if (! array_key_exists('label', $entry)) {
            throw $this->invalidTab($index, 'label is required');
        }

        $badge = $entry['badge'] ?? null;
        if ($badge !== null && (! is_int($badge) || $badge < 0)) {
            throw $this->invalidTab($index, '`badge` must be a non-negative integer count or null');
        }

        $badgeA11y = $entry['badge-a11y-label'] ?? $entry['badgeA11yLabel'] ?? null;
        if ($badgeA11y !== null && ! is_string($badgeA11y)) {
            throw $this->invalidTab($index, '`badge-a11y-label` must be a string');
        }

        $disabled = $entry['disabled'] ?? false;
        if (! is_bool($disabled)) {
            throw $this->invalidTab($index, '`disabled` must be a boolean');
        }

        return [
            'value' => $entry['value'],
            'label' => $entry['label'],
            'badge' => $badge,
            'badge_a11y_label' => $badgeA11y,
            'disabled' => $disabled,
            'index' => $index,
        ];
    }

    private function invalidTab(int $index, string $reason): InvalidArgumentException
    {
        return new InvalidArgumentException("Firstlight Tabs tab {$index}: {$reason}.");
    }

    private function warnWhenUnnamed(): void
    {
        $a11yLabel = trim((string) ($this->extraProps['a11y_label'] ?? ''));

        if (trim($this->label) !== '' || $a11yLabel !== '' || $this->applicationIsProduction()) {
            return;
        }

        trigger_error('Firstlight Tabs requires a label or a11y-label naming the tab group.', E_USER_WARNING);
    }

    private function applicationIsProduction(): bool
    {
        if (! function_exists('app')) {
            return false;
        }

        try {
            $application = app();

            return is_object($application)
                && method_exists($application, 'isProduction')
                && $application->isProduction();
        } catch (Throwable) {
            return false;
        }
    }

    public function getStyle(): array
    {
        return [];
    }
}
