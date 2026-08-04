<?php

namespace FirstlightUI\Components;

class Progress extends \Native\Mobile\UI\Components\ProgressBar
{
    protected function elementType(): string
    {
        return 'firstlight.progress';
    }
}
