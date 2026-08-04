package com.nativephp.plugins.native_ui

import androidx.compose.ui.graphics.Color

data class NativeUITokens(
    val primary: Color,
    val onPrimary: Color,
    val surface: Color,
    val onSurface: Color,
    val background: Color,
    val surfaceVariant: Color,
    val onSurfaceVariant: Color,
    val outline: Color,
    val destructive: Color,
    val onDestructive: Color,
    val success: Color,
    val onSuccess: Color,
    val accent: Color,
    val onAccent: Color,
) {
    companion object {
        val fallback = NativeUITokens(
            primary = Color(0xFF0F766E),
            onPrimary = Color.White,
            surface = Color.White,
            onSurface = Color(0xFF0F172A),
            background = Color(0xFFF8FAFC),
            surfaceVariant = Color(0xFFF1F5F9),
            onSurfaceVariant = Color(0xFF475569),
            outline = Color(0xFFCBD5E1),
            destructive = Color(0xFFDC2626),
            onDestructive = Color.White,
            success = Color(0xFF15803D),
            onSuccess = Color.White,
            accent = Color(0xFFFB923C),
            onAccent = Color.White,
        )
    }
}

object NativeUITheme {
    val light = NativeUITokens.fallback
    val dark = NativeUITokens.fallback.copy(
        surface = Color(0xFF0F172A),
        background = Color(0xFF020617),
        surfaceVariant = Color(0xFF1E293B),
        onSurface = Color(0xFFF8FAFC),
        onSurfaceVariant = Color(0xFFCBD5E1),
        primary = Color(0xFF14B8A6),
        onPrimary = Color.Black,
        destructive = Color(0xFFF87171),
        onDestructive = Color.Black,
        success = Color(0xFF4ADE80),
        onSuccess = Color.Black,
        accent = Color(0xFFFB923C),
        onAccent = Color.Black,
    )
}
