<?php

namespace FirstlightUI\Elements\Concerns;

use InvalidArgumentException;

/**
 * The `glass` attribute. On iOS 26 and later, `regular` draws the button in
 * Liquid Glass and `prominent` in glass filled with the variant's colour.
 * Earlier iOS and Android keep the variant's ordinary look. Mobile UI's
 * `class="glass"` utility arrives as a number and is refused, so the wire
 * value is always one of these names.
 */
trait AcceptsGlass
{
    public function glass(string $style = 'regular'): static
    {
        if (! in_array($style, ['regular', 'prominent'], true)) {
            throw new InvalidArgumentException(
                "Unsupported glass [{$style}]. Expected one of: regular, prominent."
            );
        }

        return $this->setProp('glass_style', $style);
    }

    /**
     * @param  array<string, mixed>  $attrs
     * @return array<string, mixed> the attributes without `glass`
     */
    protected function applyGlassAttribute(array $attrs): array
    {
        if (array_key_exists('glass', $attrs)) {
            if (! is_string($attrs['glass'])) {
                throw new InvalidArgumentException(
                    '`glass` must be `regular` or `prominent`; the `glass` class is not supported.'
                );
            }

            $this->glass($attrs['glass']);
            unset($attrs['glass']);
        }

        return $attrs;
    }
}
