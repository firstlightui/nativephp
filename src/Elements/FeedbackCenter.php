<?php

namespace FirstlightUI\Elements;

use Native\Mobile\Edge\Element;

final class FeedbackCenter extends Element
{
    protected string $type = 'firstlight.feedback-center';

    public static function make(Element ...$children): static
    {
        $element = new static;
        $element->children = $children;

        return $element;
    }

    public function getLayout(): array
    {
        return [];
    }

    public function getStyle(): array
    {
        return [];
    }
}
