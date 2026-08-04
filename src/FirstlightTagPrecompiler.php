<?php

namespace FirstlightUI;

use Native\Mobile\Edge\NativeTagPrecompiler;

final class FirstlightTagPrecompiler
{
    private const COMPONENT_TAG = '~<\s*firstlight\s*:\s*(segmented|text-field)\b((?:[^>"\']|"[^"]*"|\'[^\']*\')*)/>~s';

    private const COMPILED_MARKER = '<?php '.NativeTagPrecompiler::COMPILED_MARKER.' ?>';

    public function __invoke(string $value): string
    {
        if (! NativeTagPrecompiler::active()) {
            return $value;
        }

        return preg_replace_callback(
            self::COMPONENT_TAG,
            function (array $match): string {
                $expanded = (new NativeTagPrecompiler)($match[0]);

                if (str_starts_with($expanded, self::COMPILED_MARKER)) {
                    $expanded = substr($expanded, strlen(self::COMPILED_MARKER));
                }

                return preg_replace(
                    '~^<\s*firstlight\s*:\s*'.preg_quote($match[1], '~').'\b~',
                    '<x-native-firstlight-'.$match[1],
                    $expanded,
                    limit: 1,
                ) ?? $expanded;
            },
            $value,
        ) ?? $value;
    }
}
