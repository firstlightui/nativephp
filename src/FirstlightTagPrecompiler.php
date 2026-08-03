<?php

namespace Clinically\Firstlight;

use Native\Mobile\Edge\NativeTagPrecompiler;

final class FirstlightTagPrecompiler
{
    public function __invoke(string $value): string
    {
        if (! NativeTagPrecompiler::active()) {
            return $value;
        }

        return preg_replace_callback(
            '~<\s*firstlight\s*:\s*segmented\b((?:[^>"\']|"[^"]*"|\'[^\']*\')*)/>~s',
            fn (array $match): string => '<x-native-firstlight-segmented'.$match[1].'/>',
            $value,
        );
    }
}
