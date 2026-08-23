<?php

namespace FirstlightUI\Components;

class ListContainer extends \Native\Mobile\UI\Components\NativeList
{
    protected function elementType(): string
    {
        return 'firstlight.list';
    }
}
