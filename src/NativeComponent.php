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

    private bool $leavingScreen = false;

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

    /**
     * back() republishes this screen once before it is popped. Keeping that
     * frame's revision leaves an unchanged screen byte-identical, so NativePHP
     * drops it: a changed frame reaching iOS mid-pop re-pushes the screen the
     * user just left (a second back transition over a blank page).
     */
    public function back(): static
    {
        $this->leavingScreen = true;

        try {
            return parent::back();
        } finally {
            $this->leavingScreen = false;
        }
    }

    protected function wrapWithChrome(Element $content): Element
    {
        return parent::wrapWithChrome($content)->setProp(
            'firstlight_response_revision',
            $this->leavingScreen ? self::$responseRevision : ++self::$responseRevision,
        );
    }

    /** Streaming bypasses the revision-bearing Element response. */
    final protected function renderStreaming(): bool
    {
        return false;
    }
}
