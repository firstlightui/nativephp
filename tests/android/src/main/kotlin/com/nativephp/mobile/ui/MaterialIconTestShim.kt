package com.nativephp.mobile.ui

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.size
import androidx.compose.material3.LocalContentColor
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.PlatformTextStyle
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.style.LineHeightStyle
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

private fun shimGlyph(name: String): String = when (name) {
    "email" -> "●"
    "close" -> "×"
    "visibility" -> "◉"
    "visibility_off" -> "○"
    "send" -> "➤"
    else -> "◆"
}

@Composable
fun MaterialIcon(name: String, contentDescription: String?, modifier: Modifier = Modifier) {
    Box(modifier = modifier.size(24.dp), contentAlignment = Alignment.Center) {
        Text(shimGlyph(name), fontSize = 20.sp)
    }
}

/**
 * Mirrors core's sized overload: the glyph is drawn at `size.value.sp` inside a
 * `size` dp box with font padding trimmed, so callers see core's font-scale and
 * clipping behaviour. A filled square makes any clipping visible in snapshots.
 */
@Composable
fun MaterialIcon(
    name: String,
    contentDescription: String?,
    modifier: Modifier = Modifier,
    size: Dp = 24.dp,
    tint: Color = LocalContentColor.current,
) {
    Box(modifier = modifier.size(size), contentAlignment = Alignment.Center) {
        Text(
            text = "■",
            fontSize = size.value.sp,
            color = tint,
            textAlign = TextAlign.Center,
            style = TextStyle(
                platformStyle = PlatformTextStyle(includeFontPadding = false),
                lineHeightStyle = LineHeightStyle(
                    alignment = LineHeightStyle.Alignment.Center,
                    trim = LineHeightStyle.Trim.Both,
                ),
            ),
        )
    }
}
