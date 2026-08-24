<?php

namespace FirstlightUI;

use FirstlightUI\Concerns\AuthorizesActions;
use FirstlightUI\Concerns\DestroysListItems;
use FirstlightUI\Concerns\PaginatesLists;
use FirstlightUI\Concerns\SubmitsForms;
use FirstlightUI\Concerns\ValidatesFields;
use Native\Mobile\Edge\NativeComponent as EdgeNativeComponent;

class NativeComponent extends EdgeNativeComponent
{
    use AuthorizesActions;
    use DestroysListItems;
    use PaginatesLists;
    use SubmitsForms;
    use ValidatesFields;
}
