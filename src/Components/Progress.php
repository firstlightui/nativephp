<?php

namespace FirstlightUI\Components;

use FirstlightUI\Components\Concerns\DecodesBladeAttributes;

class Progress extends \Native\Mobile\UI\Components\ProgressBar
{
    use DecodesBladeAttributes;

    protected function elementType(): string
    {
        return 'firstlight.progress';
    }
}
