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
import org.junit.Assume.assumeTrue
import org.junit.Rule
import org.junit.Test

class SelectRendererContractTest {
    @Test
    fun `configuration decodes complete field and option contract`() {
        val configuration = SelectRendererConfiguration(
            node(
                selectedValues = listOf("urgent"),
                searchEnabled = true,
                helper = "Choose one priority",
                error = "Review the priority",
                a11yLabel = "Document priority",
                a11yHint = "Opens priority options",
            ),
        )

        assertEquals(listOf("routine", "urgent", "critical"), configuration.optionValues)
        assertEquals(listOf("Routine", "Urgent", "Critical"), configuration.optionLabels)
        assertEquals(listOf(true, true, false), configuration.optionEnabled)
        assertEquals(listOf(51, 52, 0), configuration.optionCallbacks)
        assertEquals(listOf("urgent"), configuration.selectedValues)
        assertEquals("string", configuration.valueType)
        assertTrue(configuration.searchEnabled)
        assertEquals("Select a priority", configuration.placeholder)
        assertEquals("Choose one priority", configuration.helper)
        assertEquals("Review the priority", configuration.error)
        assertTrue(configuration.required)
        assertEquals("Document priority", configuration.accessibilityLabel)
        assertEquals("Opens priority options", configuration.accessibilityHint)
    }

    @Test
    fun `selection emits PRESS without optimistic trigger state`() {
        val state = SelectRendererState(node(selectedValues = listOf("routine")))

        val event = state.userSelected(1)

        assertEquals(SelectRendererEvent.Press(52, 7), event)
        assertEquals("PRESS", event?.wireName)
        assertEquals(listOf("routine"), state.configuration.selectedValues)
        assertTrue(state.isAwaitingPublication)
    }

    @Test
    fun `equal publication releases stale selection suppression`() {
        val state = SelectRendererState(node(selectedValues = listOf("routine")))

        assertEquals(SelectRendererEvent.Press(52, 7), state.userSelected(1))
        assertNull(state.userSelected(2))
        assertFalse(state.serverPublished(tree(node(selectedValues = listOf("routine")))))
        assertFalse(state.isAwaitingPublication)
        assertEquals(SelectRendererEvent.Press(52, 7), state.userSelected(1))
    }

    @Test
    fun `disabled group option selected value and missing callback are no-ops`() {
        val disabledGroup = SelectRendererState(node(disabled = true))
        val disabledOption = SelectRendererState(node())
        val selected = SelectRendererState(
            node(selectedValues = listOf("routine"), optionCallbacks = listOf("51", "52", "0")),
        )
        val missingCallback = SelectRendererState(
            node(optionEnabled = listOf("1", "1", "1"), optionCallbacks = listOf("51", "0", "53")),
        )

        assertNull(disabledGroup.userSelected(0))
        assertNull(disabledOption.userSelected(2))
        assertNull(selected.userSelected(0))
        assertNull(missingCallback.userSelected(1))
        assertNull(missingCallback.userSelected(8))
    }

    @Test
    fun `nested publication reconciles and missing node remains pending`() {
        val state = SelectRendererState(node(selectedValues = listOf("routine")))
        state.userSelected(1)

        assertTrue(state.serverPublished(tree(node(selectedValues = listOf("urgent")))))
        assertEquals(listOf("urgent"), state.configuration.selectedValues)
        assertFalse(state.isAwaitingPublication)

        state.userSelected(0)
        val unrelated = NativeUITree(root = NativeUINode(id = 99, props = GenericProps()))
        assertFalse(state.serverPublished(unrelated))
        assertTrue(state.isAwaitingPublication)
    }

    @Test
    fun `search filters labels case insensitively in authored order`() {
        assertEquals(
            listOf(1),
            filterSelectOptionIndices(
                labels = listOf("Routine", "Urgent clinical", "Critical"),
                query = "CLIN",
            ),
        )
        assertEquals(
            listOf(0, 1, 2),
            filterSelectOptionIndices(
                labels = listOf("Routine", "Urgent", "Critical"),
                query = "",
            ),
        )
    }

    private fun node(
        selectedValues: List<String> = emptyList(),
        searchEnabled: Boolean = false,
        optionEnabled: List<String> = listOf("1", "1", "0"),
        optionCallbacks: List<String> = listOf("51", "52", "0"),
        disabled: Boolean = false,
        helper: String = "",
        error: String = "",
        a11yLabel: String = "",
        a11yHint: String = "",
    ) = selectNode(
        selectedValues = selectedValues,
        searchEnabled = searchEnabled,
        optionEnabled = optionEnabled,
        optionCallbacks = optionCallbacks,
        disabled = disabled,
        helper = helper,
        error = error,
        a11yLabel = a11yLabel,
        a11yHint = a11yHint,
    )

    private fun tree(target: NativeUINode) = NativeUITree(
        root = NativeUINode(id = 99, props = GenericProps(), children = listOf(target)),
    )
}

class SelectControlScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test
    fun `compact and searchable Material evidence is controller gated`() {
        assumeTrue(System.getenv("FIRSTLIGHT_VERIFY_SELECT_SNAPSHOTS") == "1")
        snapshot(searchEnabled = false, dark = false)
        snapshot(searchEnabled = true, dark = true)
    }

    private fun snapshot(searchEnabled: Boolean, dark: Boolean) {
        paparazzi.snapshot {
            selectScreenshotContent(searchEnabled = searchEnabled, dark = dark)
        }
    }
}

class SelectControlLargeTextScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2f))

    @Test
    fun `large text Material evidence is controller gated`() {
        assumeTrue(System.getenv("FIRSTLIGHT_VERIFY_SELECT_SNAPSHOTS") == "1")
        paparazzi.snapshot {
            selectScreenshotContent(searchEnabled = false, dark = false)
        }
    }
}

@androidx.compose.runtime.Composable
private fun selectScreenshotContent(searchEnabled: Boolean, dark: Boolean) {
    MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
        val tokens = if (dark) NativeUITheme.dark else NativeUITheme.light
        Surface(modifier = Modifier.fillMaxSize()) {
            FirstlightSelectControl(
                configuration = SelectRendererConfiguration(
                    selectNode(selectedValues = listOf("urgent"), searchEnabled = searchEnabled),
                ),
                awaitingPublication = false,
                onSelection = {},
                modifier = Modifier.padding(16.dp),
                tokens = tokens,
            )
        }
    }
}

private fun selectNode(
    selectedValues: List<String> = emptyList(),
    searchEnabled: Boolean = false,
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
            "option_values" to listOf("routine", "urgent", "critical"),
            "option_labels" to listOf("Routine", "Urgent", "Critical"),
            "option_enabled" to optionEnabled,
            "option_callbacks" to optionCallbacks,
            "selected_values" to selectedValues,
            "value_type" to "string",
            "search_enabled" to searchEnabled,
            "disabled" to disabled,
            "label" to "Priority",
            "placeholder" to "Select a priority",
            "helper" to helper,
            "error" to error,
            "required" to true,
            "a11y_label" to a11yLabel,
            "a11y_hint" to a11yHint,
        ),
    ),
)
