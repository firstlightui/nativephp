<?php

namespace FirstlightUI\Components;

class Button extends \Native\Mobile\UI\Components\Button
{
    protected function elementType(): string
    {
        return 'firstlight.button';
    }
}
