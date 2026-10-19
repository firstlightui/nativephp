<?php

namespace FirstlightUI\Components\Concerns;

/**
 * Blade HTML-escapes bound string attributes on class components
 * (`sanitizeComponentAttribute`) because it assumes they become HTML. EDGE
 * publishes them to native views, so `"` would arrive as `&quot;`. Decoding
 * restores exactly the string PHP held; static attributes follow HTML
 * attribute rules, so `label="R&amp;D"` publishes `R&D`.
 */
trait DecodesBladeAttributes
{
    public function withAttributes(array $attributes)
    {
        return parent::withAttributes(array_map(
            fn (mixed $value): mixed => is_string($value)
                ? htmlspecialchars_decode($value, ENT_QUOTES | ENT_HTML401)
                : $value,
            $attributes,
        ));
    }
}
