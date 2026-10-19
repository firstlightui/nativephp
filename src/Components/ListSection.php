<?php

namespace FirstlightUI\Components;

use FirstlightUI\Components\Concerns\DecodesBladeAttributes;

class ListSection extends \Native\Mobile\UI\Components\ListSection
{
    use DecodesBladeAttributes;

    protected function elementType(): string
    {
        return 'firstlight.list-section';
    }
}
