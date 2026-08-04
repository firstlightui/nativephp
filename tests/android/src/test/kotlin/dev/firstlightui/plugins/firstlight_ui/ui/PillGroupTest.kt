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
import com.nativephp.plugins.native_ui.NativeUITheme
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Rule
import org.junit.Test

class PillGroupRendererContractTest {
    @Test
    fun `configuration decodes complete field and selection contract`() {
        val configuration = PillGroupRendererConfiguration(
            node(
                selectedValues = listOf("mine", "urgent"),
                multiple = true,
                helper = "Choose any that apply",
                error = "Review the selection",
                a11yLabel = "Queue filters",
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
        assertEquals("Queue filters", configuration.accessibilityLabel)
        assertEquals("Double tap to toggle", configuration.accessibilityHint)
    }

    @Test
    fun `tap emits PRESS without optimistically changing selection`() {
        val state = PillGroupRendererState(node(selectedValues = listOf("mine")))

        val event = state.userSelected(1)

        assertEquals(PillGroupRendererEvent.Press(52, 7), event)
        assertEquals("PRESS", event?.wireName)
        assertEquals(listOf("mine"), state.configuration.selectedValues)
        assertTrue(state.isAwaitingPublication)
    }

    @Test
    fun `only one proposal is allowed before the server publishes`() {
        val state = PillGroupRendererState(node(selectedValues = listOf("mine")))

        assertEquals(PillGroupRendererEvent.Press(52, 7), state.userSelected(1))
        assertNull(state.userSelected(0))
        assertTrue(state.isAwaitingPublication)

        assertFalse(state.serverPublished(tree(node(selectedValues = listOf("mine")))))
        assertFalse(state.isAwaitingPublication)
        assertEquals(PillGroupRendererEvent.Press(51, 7), state.userSelected(0))
    }

    @Test
    fun `disabled group option and missing callback are no-ops`() {
        val disabledGroup = PillGroupRendererState(node(disabled = true))
        val disabledOption = PillGroupRendererState(node())
        val missingCallback = PillGroupRendererState(
            node(
                optionEnabled = listOf("1", "1", "1"),
                optionCallbacks = listOf("51", "0", "53"),
            ),
        )

        assertNull(disabledGroup.userSelected(0))
        assertNull(disabledOption.userSelected(2))
        assertNull(missingCallback.userSelected(1))
    }

    @Test
    fun `nested stable node publication reconciles server selection`() {
        val state = PillGroupRendererState(node(selectedValues = listOf("mine")))
        state.userSelected(1)

        assertTrue(state.serverPublished(tree(node(selectedValues = listOf("mine", "all")))))
        assertEquals(listOf("mine", "all"), state.configuration.selectedValues)
        assertFalse(state.isAwaitingPublication)
    }

    @Test
    fun `missing stable node keeps the proposal pending`() {
        val state = PillGroupRendererState(node(selectedValues = listOf("mine")))
        state.userSelected(1)
        val unrelated = NativeUITree(root = NativeUINode(id = 99, props = GenericProps()))

        assertFalse(state.serverPublished(unrelated))
        assertTrue(state.isAwaitingPublication)
    }

    @Test
    fun `selected pill token colours are opaque visible and contrast safe`() {
        val resolved = resolvePillGroupTokenColors(NativeUITheme.dark)

        assertEquals(1f, resolved.selectedContainer.alpha)
        assertEquals(1f, resolved.selectedContent.alpha)
        assertTrue(resolved.selectedContent != resolved.selectedContainer)
        assertTrue(contrastRatio(resolved.selectedContent, resolved.selectedContainer) >= 4.5f)
        assertEquals(resolved.selectedContent, resolved.selectedLeadingIcon)
    }

    @Test
    fun `field semantics merge label hint required and error`() {
        assertEquals(
            FirstlightFieldSemantics(
                contentDescription = "Queue filters. Double tap to toggle",
                stateDescription = "Required",
                error = "Review the selection",
            ),
            firstlightFieldSemantics(
                accessibilityLabel = "Queue filters",
                accessibilityHint = "Double tap to toggle",
                required = true,
                error = "Review the selection",
            ),
        )
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
        root = NativeUINode(
            id = 99,
            props = GenericProps(),
            children = listOf(target),
        ),
    )
}

class PillGroupControlScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test
    fun light() = snapshot(dark = false)

    @Test
    fun dark() = snapshot(dark = true)

    @Test
    fun errorAndDisabledOption() {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = lightColorScheme()) {
                val tokens = NativeUITheme.light
                Surface(modifier = Modifier.fillMaxSize()) {
                    FirstlightPillGroupControl(
                        labels = listOf("Clinical review", "Ready for assignment", "Needs follow-up"),
                        values = listOf("clinical", "assignment", "follow-up"),
                        enabled = listOf(true, false, true),
                        selectedValues = emptyList(),
                        groupEnabled = true,
                        awaitingPublication = false,
                        label = "Workflow status",
                        helper = "",
                        error = "Choose a workflow status.",
                        onSelection = {},
                        modifier = Modifier.padding(16.dp),
                        colors = pillGroupColors(tokens),
                        labelColor = tokens.onSurface,
                        helperColor = tokens.onSurfaceVariant,
                        errorColor = tokens.destructive,
                        required = true,
                    )
                }
            }
        }
    }

    private fun snapshot(dark: Boolean) {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
                val tokens = if (dark) NativeUITheme.dark else NativeUITheme.light
                Surface(modifier = Modifier.fillMaxSize()) {
                    FirstlightPillGroupControl(
                        labels = listOf("Mine", "All referrals", "Needs follow-up", "Archived"),
                        values = listOf("mine", "all", "follow-up", "archived"),
                        enabled = listOf(true, true, true, false),
                        selectedValues = listOf("mine", "follow-up"),
                        groupEnabled = true,
                        awaitingPublication = false,
                        label = "Referral queues",
                        helper = "Choose any that apply.",
                        error = null,
                        onSelection = {},
                        modifier = Modifier.padding(16.dp),
                        colors = pillGroupColors(tokens),
                        labelColor = tokens.onSurface,
                        helperColor = tokens.onSurfaceVariant,
                        errorColor = tokens.destructive,
                        required = true,
                    )
                }
            }
        }
    }
}

class PillGroupControlLargeTextScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2.0f))

    @Test
    fun fontScaleTwo() {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = darkColorScheme()) {
                val tokens = NativeUITheme.dark
                Surface(modifier = Modifier.fillMaxSize()) {
                    FirstlightPillGroupControl(
                        labels = listOf("Assigned to me", "All referrals", "Needs clinical review"),
                        values = listOf("mine", "all", "clinical"),
                        enabled = listOf(true, true, true),
                        selectedValues = listOf("mine"),
                        groupEnabled = true,
                        awaitingPublication = false,
                        label = "Referral queues",
                        helper = "Choose any that apply.",
                        error = null,
                        onSelection = {},
                        modifier = Modifier.padding(16.dp),
                        colors = pillGroupColors(tokens),
                        labelColor = tokens.onSurface,
                        helperColor = tokens.onSurfaceVariant,
                        errorColor = tokens.destructive,
                    )
                }
            }
        }
    }
}
