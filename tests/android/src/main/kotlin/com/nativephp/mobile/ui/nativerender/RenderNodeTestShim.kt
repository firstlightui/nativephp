package com.nativephp.mobile.ui.nativerender

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp

/**
 * Stand-in for core's NodeView dispatch: a full-width row `height` dp tall
 * showing `label`, enough to exercise container renderers' geometry.
 */
@Composable
fun RenderNode(node: NativeUINode, modifier: Modifier? = null) {
    Box((modifier ?: Modifier).fillMaxWidth().height(node.props.getInt("height", 48).dp)) {
        Text(node.props.getString("label"))
    }
}
