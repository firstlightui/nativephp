<?php

namespace FirstlightUI\Elements;

use FirstlightUI\Support\CallbackExpression;
use FirstlightUI\Support\Chrome;
use FirstlightUI\Validation\FieldErrorBinder;
use InvalidArgumentException;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\Element;
use Native\Mobile\Icon\IconResolver;

/**
 * A list people reorder by dragging or with accessibility actions, optionally across named
 * sections. Each possible move has a precomputed callback proposing the complete next value,
 * so PHP stays the authority on the order.
 */
class ReorderList extends Element
{
    public const MAX_ITEMS = 20;

    protected string $type = 'firstlight.reorder-list';

    /** @var list<array{value: string|int, label: string, supporting: string, icon: string, locked: bool}> */
    protected array $items = [];

    /** @var array<string, string> */
    protected array $sections = [];

    protected mixed $rawValue = null;

    /** @var array<string, string|bool> */
    protected array $fieldProps = [
        'label' => '',
        'helper' => '',
        'error' => '',
        'disabled' => false,
    ];

    protected ?string $changeCallback = null;

    public static function make(): static
    {
        return new static;
    }

    public function applyAttributes(array $attrs): void
    {
        foreach (['selected', 'multiple', 'options', '_press', '_submit'] as $attribute) {
            if (array_key_exists($attribute, $attrs)) {
                throw new InvalidArgumentException("Firstlight Reorder List `{$attribute}` is not supported.");
            }
        }

        if (array_key_exists('items', $attrs)) {
            $this->items((array) $attrs['items']);
        }
        if (array_key_exists('sections', $attrs)) {
            $this->sections((array) $attrs['sections']);
        }
        if (array_key_exists('value', $attrs)) {
            $this->value($attrs['value']);
        }
        foreach (['label', 'helper', 'error'] as $prop) {
            if (array_key_exists($prop, $attrs)) {
                $this->fieldProps[$prop] = (string) ($attrs[$prop] ?? '');
            }
        }
        if (array_key_exists('disabled', $attrs)) {
            $this->fieldProps['disabled'] = (bool) $attrs['disabled'];
        }
        if (isset($attrs['sync-mode']) || isset($attrs['syncMode'])) {
            $this->syncMode((string) ($attrs['sync-mode'] ?? $attrs['syncMode']));
        }

        $this->applyA11yAttributes($attrs);
        FieldErrorBinder::apply($this, $attrs);
    }

    /** @param array<int|string, mixed> $items */
    public function items(array $items): static
    {
        if (! array_is_list($items)) {
            throw new InvalidArgumentException('Reorder List items must be a list of item arrays.');
        }
        if (count($items) > self::MAX_ITEMS) {
            throw new InvalidArgumentException('Reorder List takes at most 20 items.');
        }

        $seen = [];
        $type = null;
        $this->items = [];
        foreach ($items as $index => $item) {
            if (! is_array($item) || ! array_key_exists('value', $item) || ! array_key_exists('label', $item)) {
                throw new InvalidArgumentException("Reorder List item [{$index}] needs a value and a label.");
            }
            $value = $item['value'];
            if (! is_string($value) && ! is_int($value)) {
                throw new InvalidArgumentException("Reorder List item [{$index}] value must be a string or integer.");
            }
            $itemType = is_string($value) ? 'string' : 'integer';
            if ($type !== null && $type !== $itemType) {
                throw new InvalidArgumentException('Reorder List item values cannot mix strings and integers.');
            }
            $type = $itemType;
            if (isset($seen[$value])) {
                throw new InvalidArgumentException("Reorder List has a duplicate item value [{$value}].");
            }
            $seen[$value] = true;
            if (! is_string($item['label']) || trim($item['label']) === '') {
                throw new InvalidArgumentException("Reorder List item [{$index}] needs a visible label.");
            }

            $icon = '';
            if (($item['icon'] ?? null) !== null) {
                $icon = IconResolver::resolve(
                    (string) $item['icon'],
                    $item['icon-ios'] ?? $item['iconIos'] ?? null,
                    $item['icon-android'] ?? $item['iconAndroid'] ?? null,
                )['icon'];
            }

            $this->items[] = [
                'value' => $value,
                'label' => $item['label'],
                'supporting' => (string) ($item['supporting'] ?? ''),
                'icon' => (string) $icon,
                'locked' => (bool) ($item['locked'] ?? false),
            ];
        }

        return $this;
    }

    /** @param array<int|string, mixed> $sections */
    public function sections(array $sections): static
    {
        if (count($sections) < 2 || array_is_list($sections)) {
            throw new InvalidArgumentException('Reorder List sections must be a map of at least two section keys to headers.');
        }
        foreach ($sections as $key => $header) {
            if (! is_string($header) || trim($header) === '') {
                throw new InvalidArgumentException("Reorder List section [{$key}] needs a visible header.");
            }
        }
        $this->sections = array_combine(array_map('strval', array_keys($sections)), $sections);

        return $this;
    }

    public function value(mixed $value): static
    {
        $this->rawValue = $value;

        return $this;
    }

    public function syncMode(string $mode): static
    {
        if ($mode !== 'live') {
            throw new InvalidArgumentException(
                "Reorder List commits each move, so the `{$mode}` sync mode has no effect. Use plain `native:model`."
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
        $order = $this->order();
        $byValue = array_column($this->items, null, 'value');
        $rows = array_merge(...array_values($order));
        $slotCount = max(0, count($rows) - 1) + count($order);
        $sectionLabels = array_values($this->sections);

        $props = array_merge($this->fieldProps, [
            'section_keys' => array_keys($this->sections),
            'section_labels' => $sectionLabels,
            'section_lengths' => array_map(fn (array $list) => (string) count($list), array_values($order)),
            'row_values' => array_map('strval', $rows),
            'row_labels' => array_map(fn ($v) => $byValue[$v]['label'], $rows),
            'row_supporting' => array_map(fn ($v) => $byValue[$v]['supporting'], $rows),
            'row_icons' => array_map(fn ($v) => $byValue[$v]['icon'], $rows),
            'row_locked' => array_map(fn ($v) => $byValue[$v]['locked'] ? '1' : '0', $rows),
            'move_slot_count' => $slotCount,
            'move_callbacks' => $this->moveCallbacks($registry, $order, $rows, $byValue),
            'move_up_label' => Chrome::string('move_up'),
            'move_down_label' => Chrome::string('move_down'),
            'move_to_labels' => array_map(fn ($label) => Chrome::string('move_to', ['section' => $label]), $sectionLabels),
            'locked_label' => Chrome::string('locked'),
            'position_label' => Chrome::string('position'),
        ]);

        if ($this->items === []) {
            $props['disabled'] = true;
        }

        return $props;
    }

    /**
     * The current order as section key => values, checked against the items.
     *
     * @return array<string, list<string|int>>
     */
    private function order(): array
    {
        $values = array_column($this->items, 'value');
        $keys = $this->sections === [] ? [''] : array_keys($this->sections);

        if ($this->rawValue === null) {
            return array_combine($keys, [$values, ...array_fill(0, count($keys) - 1, [])]);
        }
        if (! is_array($this->rawValue)) {
            throw new InvalidArgumentException('Reorder List value must be an array or null.');
        }

        if ($this->sections === []) {
            if (! array_is_list($this->rawValue)) {
                throw new InvalidArgumentException('Reorder List without sections binds a list of item values.');
            }
            $order = ['' => $this->rawValue];
        } else {
            if (array_is_list($this->rawValue) && $this->rawValue !== []) {
                throw new InvalidArgumentException('Reorder List with sections binds a map of section keys to lists.');
            }
            $unknown = array_diff(array_map('strval', array_keys($this->rawValue)), $keys);
            if ($unknown !== []) {
                throw new InvalidArgumentException('Reorder List value has an unknown section ['.implode(', ', $unknown).'].');
            }
            $order = [];
            foreach ($keys as $key) {
                $list = $this->rawValue[$key] ?? [];
                if (! is_array($list) || ! array_is_list($list)) {
                    throw new InvalidArgumentException("Reorder List section [{$key}] must be a list of item values.");
                }
                $order[$key] = $list;
            }
        }

        $flat = array_merge(...array_values($order));
        $sorted = $flat;
        $expected = $values;
        sort($sorted);
        sort($expected);
        if ($sorted !== $expected) {
            throw new InvalidArgumentException('Reorder List value must contain every item value exactly once.');
        }

        return $order;
    }

    /**
     * One callback id per row and destination slot, row by row. Slots run through the sections
     * in order; each section has one slot per position the row can take there.
     *
     * @param  array<string, list<string|int>>  $order
     * @param  list<string|int>  $rows
     * @param  array<string|int, array{locked: bool}>  $byValue
     * @return list<string>
     */
    private function moveCallbacks(CallbackRegistry $registry, array $order, array $rows, array $byValue): array
    {
        $callbacks = [];
        $inert = $this->changeCallback === null || (bool) $this->fieldProps['disabled'];

        foreach ($rows as $row) {
            $without = array_map(fn (array $list) => array_values(array_filter($list, fn ($v) => $v !== $row)), $order);
            foreach ($without as $key => $list) {
                for ($index = 0; $index <= count($list); $index++) {
                    $next = $without;
                    array_splice($next[$key], $index, 0, [$row]);
                    $callbacks[] = $inert || $byValue[$row]['locked'] || $next === $order
                        ? '0'
                        : (string) $registry->register(CallbackExpression::appendValue(
                            $this->changeCallback,
                            $this->sections === [] ? $next[''] : $next,
                        ));
                }
            }
        }

        return $callbacks;
    }
}
