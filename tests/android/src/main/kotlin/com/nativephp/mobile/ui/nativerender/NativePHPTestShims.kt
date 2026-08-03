package com.nativephp.mobile.ui.nativerender

class GenericProps(private val values: Map<String, Any> = emptyMap()) {
    fun getString(key: String, default: String = ""): String = values[key] as? String ?: default
    fun getBool(key: String, default: Boolean = false): Boolean = values[key] as? Boolean ?: default
    fun getCallbackId(key: String): Int = (values[key] as? Number)?.toInt() ?: 0

    @Suppress("UNCHECKED_CAST")
    fun getStringList(key: String): List<String> = values[key] as? List<String> ?: emptyList()
}

data class NativeUITree(val root: NativeUINode)

data class NativeUINode(
    val id: Int,
    val props: GenericProps,
    val children: List<NativeUINode> = emptyList(),
)

object NativeElementBridge {
    fun sendPressEvent(callbackId: Int, nodeId: Int, x: Float, y: Float) = Unit
    fun sendLongPressEvent(callbackId: Int, nodeId: Int, x: Float, y: Float) = Unit
    fun sendTextChangeEvent(callbackId: Int, nodeId: Int, text: String) = Unit
    fun sendToggleChangeEvent(callbackId: Int, nodeId: Int, value: Boolean) = Unit
    fun sendSubmitEvent(callbackId: Int, nodeId: Int, text: String) = Unit
    fun sendSystemBackEvent() = Unit
    fun sendSliderChangeEvent(callbackId: Int, nodeId: Int, value: Float) = Unit
    fun sendCheckboxChangeEvent(callbackId: Int, nodeId: Int, value: Boolean) = Unit
    fun sendRadioChangeEvent(callbackId: Int, nodeId: Int, value: String) = Unit
    fun sendTabChangeEvent(callbackId: Int, nodeId: Int, index: Int) = Unit
    fun sendSheetDismissEvent(callbackId: Int, nodeId: Int) = Unit
    fun sendHotReloadEvent() = Unit
    fun sendSelectChangeEvent(callbackId: Int, nodeId: Int, value: String) = Unit
}
