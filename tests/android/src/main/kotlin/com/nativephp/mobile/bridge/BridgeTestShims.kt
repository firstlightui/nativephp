package com.nativephp.mobile.bridge

// Mirrors NativePHP's BridgeRouter.kt types used by Firstlight's bridge functions.
sealed class BridgeError(val code: String, override val message: String) : Exception(message) {
    class InvalidParameters(details: String) :
        BridgeError("INVALID_PARAMETERS", "Invalid parameters: $details")
}

interface BridgeFunction {
    fun execute(parameters: Map<String, Any>): Map<String, Any>
}
