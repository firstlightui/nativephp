<?php

namespace FirstlightUI\Drafts;

use InvalidArgumentException;

/**
 * Unsaved screen state kept when a screen closes: public property values by
 * name, and the version of the record they were edited from.
 */
final readonly class Draft
{
    public ?string $basedOn;

    /**
     * @param  string  $key  Identifies the edited record within the screen, such as "note:5".
     * @param  array<string, mixed>  $values  Public property names and their unsaved values.
     * @param  string|int|null  $basedOn  The version the values were edited from, such as a hash or updated_at.
     */
    public function __construct(
        public string $key,
        public array $values,
        string|int|null $basedOn = null,
    ) {
        if (trim($key) === '') {
            throw new InvalidArgumentException('A Firstlight draft requires a non-empty key.');
        }

        foreach (array_keys($values) as $name) {
            if (! is_string($name) || $name === '') {
                throw new InvalidArgumentException('Firstlight draft values must be keyed by public property name.');
            }
        }

        $this->basedOn = $basedOn === null ? null : (string) $basedOn;
    }
}
