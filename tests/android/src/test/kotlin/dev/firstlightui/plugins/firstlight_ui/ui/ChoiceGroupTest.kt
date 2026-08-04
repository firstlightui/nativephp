package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import app.cash.paparazzi.DeviceConfig
import app.cash.paparazzi.Paparazzi
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.mobile.ui.nativerender.NativeUITree
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Assume.assumeTrue
import org.junit.Rule
import org.junit.Test

class ChoiceGroupRendererContractTest {
    @Test
    fun `configuration decodes complete choice field contract`() {
        val configuration = ChoiceGroupRendererConfiguration(
            node(
                selectedValues = listOf("mine", "urgent"),
                multiple = true,
                helper = "Choose any that apply",
                error = "Review the selection",
                a11yLabel = "Queue choices",
                a11yHint = "Double tap to toggle",
            ),
        )

        assertEquals(listOf("mine", "all", "urgent"), configuration.optionValues)
        assertEquals(listOf("Mine", "All", "Urgent"), configuration.optionLabels)
        assertEquals(listOf(true, true, false), configuration.optionEnabled)
        assertEquals(listOf(51, 52, 0), configuration.optionCallbacks)
        assertEquals(listOf("mine", "urgent"), configuration.selectedValues)
        assertTrue(configuration.multiple)
        assertEquals("Choose any that apply", configuration.helper)
        assertEquals("Review the selection", configuration.error)
        assertTrue(configuration.required)
        assertEquals("Queue choices", configuration.accessibilityLabel)
        assertEquals("Double tap to toggle", configuration.accessibilityHint)
    }

    @Test
    fun `tap emits PRESS without optimistic selection and suppresses stale taps`() {
        val state = ChoiceGroupRendererState(node(selectedValues = listOf("mine")))

        val event = state.userSelected(1)

        assertEquals(ChoiceGroupRendererEvent.Press(52, 7), event)
        assertEquals("PRESS", event?.wireName)
        assertEquals(listOf("mine"), state.configuration.selectedValues)
        assertTrue(state.isAwaitingPublication)
        assertNull(state.userSelected(0))
    }

    @Test
    fun `equal server publication releases the stale tap guard`() {
        val state = ChoiceGroupRendererState(node(selectedValues = listOf("mine")))
        state.userSelected(1)

        assertFalse(state.serverPublished(tree(node(selectedValues = listOf("mine")))))
        assertFalse(state.isAwaitingPublication)
        assertEquals(ChoiceGroupRendererEvent.Press(52, 7), state.userSelected(1))
    }

    @Test
    fun `disabled group option selected radio and missing callback are no-ops`() {
        val disabledGroup = ChoiceGroupRendererState(node(disabled = true))
        val disabledOption = ChoiceGroupRendererState(node())
        val selectedRadio = ChoiceGroupRendererState(
            node(optionCallbacks = listOf("0", "52", "0"), selectedValues = listOf("mine")),
        )
        val missingCallback = ChoiceGroupRendererState(
            node(optionEnabled = listOf("1", "1", "1"), optionCallbacks = listOf("51", "0", "53")),
        )

        assertNull(disabledGroup.userSelected(0))
        assertNull(disabledOption.userSelected(2))
        assertNull(selectedRadio.userSelected(0))
        assertNull(missingCallback.userSelected(1))
    }

    @Test
    fun `nested stable node publication reconciles server selection`() {
        val state = ChoiceGroupRendererState(node(selectedValues = listOf("mine")))
        state.userSelected(1)

        assertTrue(state.serverPublished(tree(node(selectedValues = listOf("all")))))
        assertEquals(listOf("all"), state.configuration.selectedValues)
        assertFalse(state.isAwaitingPublication)
    }

    @Test
    fun `missing stable node keeps proposal pending`() {
        val state = ChoiceGroupRendererState(node(selectedValues = listOf("mine")))
        state.userSelected(1)
        val unrelated = NativeUITree(root = NativeUINode(id = 99, props = GenericProps()))

        assertFalse(state.serverPublished(unrelated))
        assertTrue(state.isAwaitingPublication)
    }

    private fun node(
        selectedValues: List<String> = emptyList(),
        multiple: Boolean = false,
        optionEnabled: List<String> = listOf("1", "1", "0"),
        optionCallbacks: List<String> = listOf("51", "52", "0"),
        disabled: Boolean = false,
        helper: String = "",
        error: String = "",
        a11yLabel: String = "",
        a11yHint: String = "",
    ) = NativeUINode(
        id = 7,
        props = GenericProps(
            mapOf(
                "option_values" to listOf("mine", "all", "urgent"),
                "option_labels" to listOf("Mine", "All", "Urgent"),
                "option_enabled" to optionEnabled,
                "option_callbacks" to optionCallbacks,
                "selected_values" to selectedValues,
                "value_type" to "string",
                "multiple" to multiple,
                "disabled" to disabled,
                "label" to "Document queue",
                "helper" to helper,
                "error" to error,
                "required" to true,
                "a11y_label" to a11yLabel,
                "a11y_hint" to a11yHint,
            ),
        ),
    )

    private fun tree(target: NativeUINode) = NativeUITree(
        root = NativeUINode(id = 99, props = GenericProps(), children = listOf(target)),
    )
}

class ChoiceGroupControlScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test
    fun `Material radio and checkbox evidence is controller gated`() {
        assumeTrue(System.getenv("FIRSTLIGHT_VERIFY_CHOICE_GROUP_SNAPSHOTS") == "1")

        snapshot(multiple = false, dark = false)
        snapshot(multiple = true, dark = true)
    }

    private fun snapshot(multiple: Boolean, dark: Boolean) {
        paparazzi.snapshot {
            choiceGroupScreenshotContent(multiple = multiple, dark = dark)
        }
    }
}

class ChoiceGroupControlLargeTextScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2f))

    @Test
    fun `Material large text evidence is controller gated`() {
        assumeTrue(System.getenv("FIRSTLIGHT_VERIFY_CHOICE_GROUP_SNAPSHOTS") == "1")

        paparazzi.snapshot {
            choiceGroupScreenshotContent(multiple = true, dark = false)
        }
    }
}

@androidx.compose.runtime.Composable
private fun choiceGroupScreenshotContent(multiple: Boolean, dark: Boolean) {
    MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
        Surface(modifier = Modifier.fillMaxSize()) {
            FirstlightChoiceGroupControl(
                labels = listOf("Routine", "Urgent", "Critical review required"),
                values = listOf("routine", "urgent", "critical"),
                enabled = listOf(true, true, false),
                selectedValues = if (multiple) listOf("routine", "urgent") else listOf("urgent"),
                multiple = multiple,
                groupEnabled = true,
                awaitingPublication = false,
                label = if (multiple) "Notifications" else "Priority",
                helper = "Choose the options that apply.",
                error = null,
                onSelection = {},
                modifier = Modifier.padding(16.dp),
                required = true,
            )
        }
    }
}
