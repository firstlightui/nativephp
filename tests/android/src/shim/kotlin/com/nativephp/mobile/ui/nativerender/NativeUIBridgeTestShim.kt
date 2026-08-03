package com.nativephp.mobile.ui.nativerender

import androidx.compose.runtime.mutableStateOf

object NativeUIBridge {
    val currentTree = mutableStateOf<NativeUITree?>(null)

    fun sendSelectChangeEvent(callbackId: Int, nodeId: Int, value: String) = Unit
    fun sendPressEvent(callbackId: Int, nodeId: Int) = Unit
}
