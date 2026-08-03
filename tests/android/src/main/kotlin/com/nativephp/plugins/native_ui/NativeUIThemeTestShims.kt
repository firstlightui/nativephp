package com.nativephp.plugins.native_ui

import androidx.compose.ui.graphics.Color

data class NativeUITokens(
    val primary: Color,
    val onPrimary: Color,
    val surface: Color,
    val onSurface: Color,
    val background: Color,
    val onSurfaceVariant: Color,
    val outline: Color,
    val destructive: Color,
) {
    companion object {
        val fallback = NativeUITokens(
            primary = Color(0xFF0F766E),
            onPrimary = Color.White,
            surface = Color.White,
            onSurface = Color(0xFF0F172A),
            background = Color(0xFFF8FAFC),
            onSurfaceVariant = Color(0xFF475569),
            outline = Color(0xFFCBD5E1),
            destructive = Color(0xFFDC2626),
        )
    }
}

object NativeUITheme {
    val light = NativeUITokens.fallback
    val dark = NativeUITokens.fallback.copy(
        surface = Color(0xFF0F172A),
        background = Color(0xFF020617),
        onSurface = Color(0xFFF8FAFC),
        onSurfaceVariant = Color(0xFFCBD5E1),
    )
}
