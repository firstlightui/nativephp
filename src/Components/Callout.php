<?php

namespace FirstlightUI\Components;

use FirstlightUI\Components\Concerns\DecodesBladeAttributes;
use FirstlightUI\Elements\Callout as CalloutElement;
use Native\Mobile\Edge\Components\Native\NativeBladeComponent;
use Native\Mobile\Edge\NativeElementCollector;

final class Callout extends NativeBladeComponent
{
    use DecodesBladeAttributes {
        withAttributes as decodeAttributes;
    }

    protected bool $isSelfClosing = true;

    protected bool $handlesCollectorManually = true;

    public function withAttributes(array $attributes)
    {
        $this->decodeAttributes($attributes);
        $attrs = $this->attributes->getAttributes();

        // An error-bound Callout is present only while its key (or, for a
        // summary, any key) has a message, like Blade's @error.
        if (CalloutElement::boundErrorMessage($attrs) === '') {
            return $this;
        }

        NativeElementCollector::isStreaming()
            ? NativeElementCollector::leafStreaming($this->elementType(), $attrs)
            : NativeElementCollector::leaf($this->elementType(), $attrs);

        return $this;
    }

    protected function elementType(): string
    {
        return 'firstlight.callout';
    }
}
