<?php

namespace Clinically\Firstlight\Components;

use Native\Mobile\Edge\Components\Native\NativeBladeComponent;

class Segmented extends NativeBladeComponent
{
    protected bool $isSelfClosing = true;

    protected function elementType(): string
    {
        return 'firstlight.segmented';
    }
}
