package dev.firstlightui.plugins.firstlight_ui.ui

import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import java.time.LocalDate
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test

class DatePickerTest {
    @Test
    fun `configuration decodes explicit null and display context`() {
        val configuration = DatePickerRendererConfiguration(node(hasValue = false, value = ""))

        assertFalse(configuration.hasValue)
        assertNull(configuration.acceptedValue)
        assertEquals("en-AU", configuration.locale)
        assertEquals("Australia/Sydney", configuration.timezone)
        assertEquals("Confirm", configuration.confirmLabel)
        assertEquals("Cancel", configuration.cancelLabel)
        assertEquals("Appointment date", configuration.accessibilityLabel)
    }

    @Test
    fun `null seed is today clamped to the nearest inclusive bound`() {
        val below = DatePickerRendererState(node(hasValue = false, value = "", min = "2026-08-10"))
        val above = DatePickerRendererState(node(hasValue = false, value = "", max = "2026-08-01"))

        assertTrue(below.open(LocalDate.parse("2026-08-04")))
        assertEquals("2026-08-10", below.draft)
        assertTrue(above.open(LocalDate.parse("2026-08-04")))
        assertEquals("2026-08-01", above.draft)
    }

    @Test
    fun `confirm emits canonical change without optimistically accepting it`() {
        val state = DatePickerRendererState(node(value = "2026-08-04"))
        state.open(LocalDate.parse("2026-08-04"))
        state.userSelected("2026-08-05")

        assertEquals(DatePickerRendererEvent.Change(41, 7, "2026-08-05"), state.confirm())
        assertEquals("2026-08-04", state.configuration.acceptedValue)
        assertFalse(state.isPresented)

        assertTrue(state.open(LocalDate.parse("2026-08-06")))
        assertEquals("2026-08-04", state.draft)
    }

    @Test
    fun `cancel and confirming the accepted value publish nothing`() {
        val state = DatePickerRendererState(node(value = "2026-08-04"))
        state.open()
        state.cancel()
        assertNull(state.confirm())

        state.open()
        assertNull(state.confirm())
    }

    @Test
    fun `disabled or callbackless triggers are inert`() {
        assertFalse(DatePickerRendererState(node(disabled = true)).open())
        assertFalse(DatePickerRendererState(node(callback = 0)).open())
    }

    @Test
    fun `accepted presentation changes dismiss and discard the draft`() {
        val state = DatePickerRendererState(node(value = "2026-08-04"))
        state.open()
        state.userSelected("2026-08-05")

        state.serverPublished(tree(node(value = "2026-08-06")))

        assertFalse(state.isPresented)
        assertNull(state.draft)
        assertEquals("2026-08-06", state.configuration.acceptedValue)
    }

    @Test
    fun `utc midnight conversion preserves the wire date`() {
        listOf("0001-01-01", "2024-02-29", "9999-12-31").forEach { value ->
            assertEquals(value, utcMillisToCanonicalDate(canonicalDateToUtcMillis(value)))
        }
    }

    private fun node(
        hasValue: Boolean = true,
        value: String = "2026-08-04",
        min: String = "0001-01-01",
        max: String = "9999-12-31",
        disabled: Boolean = false,
        callback: Int = 41,
    ) = NativeUINode(
        id = 7,
        props = GenericProps(
            mapOf(
                "has_value" to hasValue,
                "value" to value,
                "min" to min,
                "max" to max,
                "label" to "Appointment date",
                "placeholder" to "Choose a date",
                "helper" to "Local clinic date",
                "required" to true,
                "locale" to "en-AU",
                "timezone" to "Australia/Sydney",
                "on_change" to callback,
                "disabled" to disabled,
            ),
        ),
    )

    private fun tree(target: NativeUINode) = NativeUITree(
        root = NativeUINode(id = 99, props = GenericProps(), children = listOf(target)),
    )
}
