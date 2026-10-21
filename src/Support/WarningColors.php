<?php

namespace FirstlightUI\Support;

use Native\Mobile\UI\Theme;

final class WarningColors
{
    /** @return array<string, string> Resolved NativePHP wire-format colours. */
    public static function props(): array
    {
        return [
            'warning_color' => Theme::get('light.warning', '#B45309'),
            'on_warning_color' => Theme::get('light.on-warning', '#FFFFFF'),
            'dark_warning_color' => Theme::get('dark.warning', '#FBBF24'),
            'dark_on_warning_color' => Theme::get('dark.on-warning', '#1C1917'),
        ];
    }
}
