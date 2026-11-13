<?php

namespace FirstlightUI\Elements\Concerns;

use FirstlightUI\Haptics\Haptic;
use InvalidArgumentException;

/**
 * The `haptic` attribute. Buttons play the named haptic on the device as the
 * tap lands, without waiting for PHP. Controls with a default haptic, such as
 * Switch or Segmented, accept only `none` to keep that control silent.
 */
trait AcceptsHaptic
{
    /** Whether `haptic` names what a press plays, rather than only silencing a default. */
    protected function hapticPlaysOnPress(): bool
    {
        return false;
    }

    public function haptic(Haptic|string $haptic): static
    {
        $haptic = Haptic::parse($haptic);

        if ($haptic !== Haptic::None && ! $this->hapticPlaysOnPress()) {
            $tag = str_replace('firstlight.', 'firstlight:', $this->type);

            throw new InvalidArgumentException(
                "<{$tag}> plays its platform's own haptics; `haptic` accepts only `none`."
            );
        }

        return $this->setProp('haptic', $haptic->value);
    }

    /**
     * @param  array<string, mixed>  $attrs
     * @return array<string, mixed> the attributes without `haptic`
     */
    protected function applyHapticAttribute(array $attrs): array
    {
        if (array_key_exists('haptic', $attrs)) {
            if (! is_string($attrs['haptic']) && ! $attrs['haptic'] instanceof Haptic) {
                throw new InvalidArgumentException('`haptic` must name a haptic, such as `keypress` or `none`.');
            }

            $this->haptic($attrs['haptic']);
            unset($attrs['haptic']);
        }

        return $attrs;
    }
}
