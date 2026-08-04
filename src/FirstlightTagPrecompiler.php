<?php

namespace FirstlightUI;

use Native\Mobile\Edge\NativeTagPrecompiler;

final class FirstlightTagPrecompiler
{
    private const FIRSTLIGHT_SELF_CLOSING_TAG = '~<\s*firstlight\s*:\s*(segmented|status-label|badge|button|icon-button|pill-group|choice-group|progress|text-field|search-field|text-area|date-picker|time-picker|select|slider|stepper|switch)(?=\s|/>)((?:[^>"\']|"[^"]*"|\'[^\']*\')*)/>~s';

    private const FIRSTLIGHT_PAIRED_BUTTON_TAG = '~<\s*firstlight\s*:\s*(button)(?=\s|>)((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>(.*?)</\s*firstlight\s*:\s*\1\s*>~s';

    private const COMPILED_MARKER = '<?php '.NativeTagPrecompiler::COMPILED_MARKER.' ?>';

    public function __invoke(string $value): string
    {
        if (! NativeTagPrecompiler::active()) {
            return $value;
        }

        $value = preg_replace_callback(
            self::FIRSTLIGHT_SELF_CLOSING_TAG,
            fn (array $match): string => $this->compileTag($match[0], $match[1], paired: false),
            $value,
        ) ?? $value;

        return preg_replace_callback(
            self::FIRSTLIGHT_PAIRED_BUTTON_TAG,
            fn (array $match): string => $this->compileTag($match[0], $match[1], paired: true),
            $value,
        ) ?? $value;
    }

    private function compileTag(string $source, string $tag, bool $paired): string
    {
        $expanded = (new NativeTagPrecompiler)($source);

        if (str_starts_with($expanded, self::COMPILED_MARKER)) {
            $expanded = substr($expanded, strlen(self::COMPILED_MARKER));
        }

        $expanded = preg_replace(
            '~^<\s*firstlight\s*:\s*'.preg_quote($tag, '~').'(?=\s|/?>)~',
            '<x-native-firstlight-'.$tag,
            $expanded,
            limit: 1,
        ) ?? $expanded;

        if (! $paired) {
            return $expanded;
        }

        return preg_replace(
            '~</\s*firstlight\s*:\s*'.preg_quote($tag, '~').'\s*>$~',
            '</x-native-firstlight-'.$tag.'>',
            $expanded,
            limit: 1,
        ) ?? $expanded;
    }
}
