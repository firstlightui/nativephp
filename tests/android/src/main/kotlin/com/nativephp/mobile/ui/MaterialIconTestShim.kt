package com.nativephp.mobile.ui

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.size
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

@Composable
fun MaterialIcon(name: String, contentDescription: String?, modifier: Modifier = Modifier) {
    val glyph = when (name) {
        "email" -> "●"
        "close" -> "×"
        "visibility" -> "◉"
        "visibility_off" -> "○"
        "send" -> "➤"
        else -> "◆"
    }

    Box(modifier = modifier.size(24.dp), contentAlignment = Alignment.Center) {
        Text(glyph, fontSize = 20.sp)
    }
}
