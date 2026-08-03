<?php

namespace Clinically\Firstlight\Support;

use Clinically\Firstlight\Exceptions\InvalidOption;

final class OptionNormalizer
{
    /** @param array<int|string, mixed> $options */
    public static function normalize(array $options): NormalizedOptions
    {
        if ($options === []) {
            return new NormalizedOptions('string', []);
        }

        if (array_is_list($options)) {
            return self::normalizeList($options);
        }

        return self::normalizeMap($options);
    }

    /** @param list<mixed> $options */
    private static function normalizeList(array $options): NormalizedOptions
    {
        $first = $options[0];

        if (is_string($first)) {
            $items = [];
            $seen = [];

            foreach ($options as $index => $option) {
                if (! is_string($option)) {
                    throw InvalidOption::at($index, 'list options must be strings');
                }

                self::assertUnique($option, $seen, $index);

                $items[] = new NormalizedOption($option, $option, false);
            }

            return new NormalizedOptions('string', $items);
        }

        if (is_array($first)) {
            return self::normalizeRichList($options);
        }

        throw InvalidOption::at(0, 'option must be a string or rich option');
    }

    /** @param array<int|string, mixed> $options */
    private static function normalizeMap(array $options): NormalizedOptions
    {
        $items = [];
        $valueType = null;
        $seen = [];

        foreach ($options as $value => $label) {
            if (! is_string($label)) {
                throw InvalidOption::at(count($items), 'label must be a string');
            }

            self::assertValueType($value, $valueType, count($items));
            self::assertUnique($value, $seen, count($items));

            $items[] = new NormalizedOption($value, $label, false);
        }

        return new NormalizedOptions($valueType, $items);
    }

    /** @param list<mixed> $options */
    private static function normalizeRichList(array $options): NormalizedOptions
    {
        $items = [];
        $valueType = null;
        $seen = [];

        foreach ($options as $index => $entry) {
            if (! is_array($entry)) {
                throw InvalidOption::at($index, 'rich options must be arrays');
            }

            $unknown = array_diff(array_keys($entry), ['value', 'label', 'disabled']);

            if ($unknown !== []) {
                throw InvalidOption::at($index, 'unknown field: '.implode(', ', $unknown));
            }

            if (! array_key_exists('value', $entry)) {
                throw InvalidOption::at($index, 'value is required');
            }

            if (! array_key_exists('label', $entry)) {
                throw InvalidOption::at($index, 'label is required');
            }

            $value = $entry['value'];
            $label = $entry['label'];
            $disabled = array_key_exists('disabled', $entry) ? $entry['disabled'] : false;

            if (! is_string($label)) {
                throw InvalidOption::at($index, 'label must be a string');
            }

            if (! is_bool($disabled)) {
                throw InvalidOption::at($index, 'disabled must be a boolean');
            }

            self::assertValueType($value, $valueType, $index);
            self::assertUnique($value, $seen, $index);

            $items[] = new NormalizedOption($value, $label, $disabled);
        }

        return new NormalizedOptions($valueType, $items);
    }

    /** @param string|null $valueType */
    private static function assertValueType(mixed $value, ?string &$valueType, int $index): void
    {
        if (! is_string($value) && ! is_int($value)) {
            throw InvalidOption::at($index, 'value must be a string or integer');
        }

        $type = is_string($value) ? 'string' : 'integer';

        if ($valueType !== null && $valueType !== $type) {
            throw InvalidOption::at($index, 'cannot mix string and integer values');
        }

        $valueType = $type;
    }

    /** @param array<string, true> $seen */
    private static function assertUnique(string|int $value, array &$seen, int $index): void
    {
        $key = (is_string($value) ? 'string' : 'integer').':'.$value;

        if (isset($seen[$key])) {
            throw InvalidOption::at($index, "duplicate value: {$key}");
        }

        $seen[$key] = true;
    }
}
