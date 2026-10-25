package com.nativephp.plugins.native_ui.ui

import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.semantics
import com.nativephp.mobile.ui.nativerender.NativeUINode

// Mirrors Mobile UI 0.8's ButtonRenderer for the parts Firstlight wraps: an
// M3 button whose own modifier carries the label and hint (`nuiA11y`).
object ButtonRenderer {
    @Composable
    fun Render(node: NativeUINode, modifier: Modifier) {
        val description = listOf(node.props.getString("a11y_label"), node.props.getString("a11y_hint"))
            .filter { it.isNotEmpty() }
            .joinToString(". ")
        TextButton(
            onClick = {},
            modifier = if (description.isEmpty()) modifier else modifier.semantics { contentDescription = description },
        ) {
            Text(node.props.getString("label"))
        }
    }
}
