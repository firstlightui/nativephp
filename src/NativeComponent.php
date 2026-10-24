<?php

namespace FirstlightUI;

use FirstlightUI\Concerns\AuthorizesActions;
use FirstlightUI\Concerns\DestroysListItems;
use FirstlightUI\Concerns\PaginatesLists;
use FirstlightUI\Concerns\SubmitsForms;
use FirstlightUI\Concerns\ValidatesFields;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent as EdgeNativeComponent;
use Native\Mobile\Edge\NativeElementCollector;

class NativeComponent extends EdgeNativeComponent
{
    use AuthorizesActions;
    use DestroysListItems;
    use PaginatesLists;
    use SubmitsForms;
    use ValidatesFields;

    // Shared across screen instances: navigating/remounting must not reuse
    // the previous frame's revision within the same PHP runtime.
    private static int $responseRevision = 0;

    /**
     * True when nothing is below this screen on PHP's navigation stack.
     * Layouts use it to decide whether the native bar shows a back button.
     */
    public function isRootScreen(): bool
    {
        return $this->nativeRouter?->isRootScreen() ?? true;
    }

    /**
     * Complete a hand-built screen tree, just as view()/fromView() do for
     * Blade. Nested components contribute content, not separate responses.
     */
    protected function response(Element $content): Element
    {
        return NativeElementCollector::inComponentScope()
            ? $content
            : $this->wrapWithChrome($content);
    }

    protected function wrapWithChrome(Element $content): Element
    {
        return parent::wrapWithChrome($content)->setProp(
            'firstlight_response_revision', ++self::$responseRevision,
        );
    }

    /** Streaming bypasses the revision-bearing Element response. */
    final protected function renderStreaming(): bool
    {
        return false;
    }
}
