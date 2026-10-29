package com.nativephp.mobile.ui.nativerender

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp

/**
 * Stand-in for core's NodeView dispatch. Scroll views use the same LazyColumn
 * measurement contract as Mobile UI so containers cannot hide unbounded-height
 * failures behind fixed-height rows. Other leaves show `label` at `height` dp.
 */
@Composable
fun RenderNode(node: NativeUINode, modifier: Modifier? = null) {
    if (node.type == "scroll_view") {
        LazyColumn(modifier ?: Modifier) {
            items(node.children, key = { it.id }) { RenderNode(it) }
        }
        return
    }
    if (node.type == "column") {
        Column(modifier ?: Modifier) {
            node.children.forEach { RenderNode(it) }
        }
        return
    }
    Box((modifier ?: Modifier).fillMaxWidth().height(node.props.getInt("height", 48).dp)) {
        Text(node.props.getString("label"))
    }
}
