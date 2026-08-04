package dev.firstlightui.plugins.firstlight_ui.ui

import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test

class SliderTest {
    @Test fun `configuration decodes fractional Float props and metadata`() {
        val configuration = SliderRendererConfiguration(node())

        assertEquals(0.25f, configuration.value)
        assertEquals(-1.5f, configuration.min)
        assertEquals(1.5f, configuration.max)
        assertEquals(0.25f, configuration.step)
        assertEquals(12, configuration.intervalCount)
        assertEquals("Medication dose", configuration.accessibilityLabel)
        assertEquals("0.25 milligrams", configuration.accessibilityValue)
    }

    @Test fun `live changes snap to the grid and publish Float events`() {
        val state = SliderRendererState(configuration())

        assertTrue(state.beginEditing())
        assertEquals(SliderRendererEvent(41, 7, 0.25f), state.userChanged(0.34f))
        assertEquals(0.25f, state.draft)
        assertNull(state.userChanged(0.26f))
        assertNull(state.finishEditing())
    }

    @Test fun `blur and debounce keep drafts local until their policy publishes`() {
        val blur = SliderRendererState(configuration(syncMode = "blur"))
        assertNull(blur.userChanged(0.74f))
        assertEquals(SliderRendererEvent(41, 7, 0.75f), blur.finishEditing())

        val debounce = SliderRendererState(configuration(syncMode = "debounce"))
        assertNull(debounce.userChanged(-0.74f))
        assertEquals(SliderRendererEvent(41, 7, -0.75f), debounce.flush())
        assertNull(debounce.flush())
        assertNull(debounce.finishEditing())
    }

    @Test fun `every server publication is authoritative including rejected identical values`() {
        val accepted = configuration(value = 0f)
        val state = SliderRendererState(accepted)
        assertEquals(SliderRendererEvent(41, 7, 0.75f), state.userChanged(0.75f))
        assertEquals(0.75f, state.draft)

        state.serverPublished(accepted)

        assertEquals(0f, state.draft)
        assertEquals(0f, state.lastEmitted)
        assertFalse(state.isEditing)
        assertNull(state.flush())
    }

    @Test fun `disabled and callbackless configurations reject native editing`() {
        val disabled = SliderRendererState(configuration(disabled = true))
        val callbackless = SliderRendererState(configuration(onChangeCallback = 0))

        assertFalse(disabled.beginEditing())
        assertNull(disabled.userChanged(1f))
        assertNull(callbackless.userChanged(1f))
        assertEquals(0f, disabled.draft)
        assertEquals(0f, callbackless.draft)
    }

    private fun configuration(
        value: Float = 0f,
        syncMode: String = "live",
        disabled: Boolean = false,
        onChangeCallback: Int = 41,
    ) = SliderRendererConfiguration(
        nodeId = 7,
        value = value,
        min = -1.5f,
        max = 1.5f,
        step = 0.25f,
        intervalCount = 12,
        label = "Dose",
        helper = "Choose a dose",
        error = "",
        disabled = disabled,
        syncMode = syncMode,
        debounceMilliseconds = 300,
        accessibilityLabel = "Medication dose",
        accessibilityHint = "Swipe to adjust",
        accessibilityValue = "",
        onChangeCallback = onChangeCallback,
    )

    private fun node() = NativeUINode(
        id = 7,
        props = GenericProps(mapOf(
            "value" to 0.25f,
            "min" to -1.5f,
            "max" to 1.5f,
            "step" to 0.25f,
            "interval_count" to 12,
            "label" to "Dose",
            "helper" to "Choose a dose",
            "error" to "",
            "disabled" to false,
            "sync_mode" to "live",
            "debounce_ms" to 300,
            "a11y_label" to "Medication dose",
            "a11y_hint" to "Swipe to adjust",
            "a11y_value" to "0.25 milligrams",
            "on_change" to 41,
        )),
    )
}
