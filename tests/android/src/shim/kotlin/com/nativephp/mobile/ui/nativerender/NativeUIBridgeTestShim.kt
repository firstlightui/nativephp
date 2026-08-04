package com.nativephp.mobile.ui.nativerender

import androidx.compose.runtime.mutableStateOf

object NativeUIBridge {
    val currentTree = mutableStateOf<NativeUITree?>(null)

    fun sendSelectChangeEvent(callbackId: Int, nodeId: Int, value: String) = Unit
    fun sendPressEvent(callbackId: Int, nodeId: Int) = Unit
    fun sendTextChangeEvent(callbackId: Int, nodeId: Int, text: String) = Unit
    fun sendToggleChangeEvent(callbackId: Int, nodeId: Int, value: Boolean) = Unit
    fun sendSubmitEvent(callbackId: Int, nodeId: Int, text: String) = Unit
}
