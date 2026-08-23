<?php

namespace FirstlightUI\Components;

class Modal extends \Native\Mobile\UI\Components\Modal
{
    protected function elementType(): string
    {
        return 'firstlight.modal';
    }
}
