package com.nativephp.mobile.ui.nativerender

import androidx.compose.runtime.Composable

object NativeRootHostRegistry {
    data class Registration(
        val name: String,
        val consumes: String?,
        val host: @Composable (NativeUINode, @Composable () -> Unit) -> Unit,
    )

    private val registrations = mutableListOf<Registration>()

    fun register(
        name: String,
        consumes: String? = null,
        host: @Composable (NativeUINode, @Composable () -> Unit) -> Unit,
    ) {
        registrations += Registration(name, consumes, host)
    }

    fun consumes(type: String): Boolean = registrations.any { it.consumes == type }

    fun registration(name: String): Registration? = registrations.lastOrNull { it.name == name }

    fun clearForTests() {
        registrations.clear()
    }
}
