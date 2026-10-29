package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.mutableStateOf
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.SemanticsProperties
import androidx.compose.ui.test.*
import androidx.compose.ui.text.input.TextFieldValue
import androidx.compose.ui.unit.dp
import app.cash.paparazzi.DeviceConfig
import app.cash.paparazzi.Paparazzi
import com.android.resources.NightMode
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.plugins.native_ui.NativeUITheme
import org.junit.Assert.assertEquals
import org.junit.Rule
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import org.robolectric.annotation.Config

@OptIn(ExperimentalTestApi::class)
@RunWith(RobolectricTestRunner::class)
@Config(qualifiers = "w412dp-h892dp")
class AppControlExtensionsTest {
    @Test
    fun `tiles expose number name fee and checked state without optimistic selection`() = runComposeUiTest {
        val selected = mutableStateOf(listOf("consult"))
        val pending = mutableStateOf(false)
        val proposals = mutableListOf<Int>()
        setContent {
            MaterialTheme {
                tiles(selected.value, pending.value) { proposals.add(it); pending.value = true }
            }
        }
        onNodeWithText("Initial consultation").assertIsOn()
        onNodeWithText("Follow-up").assertIsOff().performClick().assertIsOff()
        onNodeWithText("Initial consultation").performClick()
        assertEquals(listOf(1), proposals)
        onNodeWithText("Unavailable").assertIsNotEnabled().performClick()
        assertEquals(listOf(1), proposals)
        onNode(hasText("Follow-up") and hasText("116") and hasText("$45.00")).assertExists()
        runOnIdle { selected.value = listOf("consult", "review"); pending.value = false }
        onNodeWithText("Follow-up").assertIsOn()
    }

    @Test
    fun `chip opens the native dropdown and keeps the accepted label until publication`() = runComposeUiTest {
        val events = mutableListOf<Int>()
        setContent {
            MaterialTheme {
                FirstlightSelectControl(selectConfiguration(), false, { events.add(it) }, tokens = NativeUITheme.light)
            }
        }
        // A real pointer click catches conflicting menu-anchor and chip handlers.
        onNodeWithText("Hospital North").performTouchInput { click() }
        onNodeWithText("South").assertIsDisplayed().performClick()
        assertEquals(listOf(1), events)
        onNodeWithText("Hospital North").assertIsDisplayed()
    }

    @Test
    fun `date chip retains full spoken date and opens the native date dialog`() = runComposeUiTest {
        val state = DatePickerRendererState(dateNode())
        setContent {
            MaterialTheme {
                FirstlightDatePickerControl(state, NativeUITheme.light, state::open, state::cancel, state::userSelected, {})
            }
        }
        onNodeWithText("DOS Today")
            .assert(SemanticsMatcher.expectValue(SemanticsProperties.StateDescription, "29 Oct 2026"))
            .performClick()
        onNodeWithText("Cancel").assertIsDisplayed().performClick()
        onNodeWithText("DOS Today").assertIsDisplayed()
    }

    @Test
    fun `pending row announces its state and identity while remaining actionable`() = runComposeUiTest {
        var presses = 0
        setContent {
            MaterialTheme {
                FirstlightListItemControl(rowConfiguration("pending-removal"), NativeUITheme.light, { presses++ })
            }
        }
        onNodeWithContentDescription("4B-12. Alex Morgan. Waiting to sync. Discharge")
            .assert(SemanticsMatcher.expectValue(SemanticsProperties.StateDescription, "Discharge waiting to sync"))
            .performClick()
        assertEquals(1, presses)
    }

    @Test
    fun `warning helpers are guidance not errors and validation still wins`() = runComposeUiTest {
        val error = mutableStateOf("")
        setContent { MaterialTheme { helperField("warning", error.value) } }
        onNodeWithText("Check this").assertIsDisplayed()
        onAllNodes(SemanticsMatcher.keyIsDefined(SemanticsProperties.Error)).assertCountEquals(0)
        runOnIdle { error.value = "Invalid identifier" }
        onNodeWithText("Check this").assertDoesNotExist()
        onNode(SemanticsMatcher.expectValue(SemanticsProperties.Error, "Invalid identifier")).assertExists()
    }
}

class AppControlExtensionsScreenshotTest {
    @get:Rule val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(screenHeight = 3100))
    @Test fun light() = capture(false)
    @Test fun dark() = capture(true)
    private fun capture(dark: Boolean) {
        paparazzi.unsafeUpdateConfig(deviceConfig = DeviceConfig.PIXEL_5.copy(
            screenHeight = 3100, nightMode = if (dark) NightMode.NIGHT else NightMode.NOTNIGHT,
        ))
        paparazzi.snapshot { extensionContent(dark) }
    }
}

class AppControlExtensionsLargeTextScreenshotTest {
    @get:Rule val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2f, screenHeight = 6100))
    @Test fun largeText() = paparazzi.snapshot { extensionContent(false) }
}

@Composable
private fun extensionContent(dark: Boolean) {
    MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
        val tokens = if (dark) NativeUITheme.dark else NativeUITheme.light
        Surface(Modifier.fillMaxSize()) {
            Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                listOf("normal", "provisional", "pending-removal").forEach {
                    FirstlightListItemControl(rowConfiguration(it), tokens, {})
                }
                FirstlightSelectControl(selectConfiguration(), false, {}, tokens = tokens)
                val date = DatePickerRendererState(dateNode())
                FirstlightDatePickerControl(date, tokens, date::open, date::cancel, date::userSelected, {})
                tiles(listOf("consult"), false) {}
                helperField("success", "", dark)
                helperField("warning", "", dark)
                helperField("neutral", "Invalid identifier", dark)
            }
        }
    }
}

private fun rowConfiguration(state: String) = ListItemRendererConfiguration(NativeUINode(id = 5, onPress = 11, props = GenericProps(mapOf(
    "headline" to "Alex Morgan", "supporting" to "Discharge", "leading_type" to "label", "leading_value" to "4B-12",
    "trailing_type" to "status", "trailing_value" to if (state == "normal") "Billed today" else "Waiting to sync",
    "trailing_status_tone" to if (state == "normal") "success" else "warning", "state" to state,
    "state_label" to if (state == "pending-removal") "Discharge waiting to sync" else "Provisional admission",
))))

private fun selectConfiguration() = SelectRendererConfiguration(NativeUINode(id = 6, props = GenericProps(mapOf(
    "variant" to "chip", "label" to "Hospital", "option_values" to listOf("north", "south"),
    "option_labels" to listOf("North", "South"), "option_enabled" to listOf("1", "1"),
    "option_callbacks" to listOf("51", "52"), "selected_values" to listOf("north"),
))))

private fun dateNode() = NativeUINode(id = 7, props = GenericProps(mapOf(
    "variant" to "chip", "label" to "DOS", "display_text" to "Today", "has_value" to true,
    "value" to "2026-10-29", "locale" to "en-AU", "timezone" to "Australia/Sydney", "on_change" to 41,
    "confirm_label" to "Confirm", "cancel_label" to "Cancel",
)))

@Composable
private fun tiles(selected: List<String>, pending: Boolean, onSelection: (Int) -> Unit) {
    FirstlightChoiceGroupControl(
        labels = listOf("Initial consultation", "Follow-up", "Unavailable"),
        values = listOf("consult", "review", "disabled"), enabled = listOf(true, true, false),
        selectedValues = selected, multiple = true, groupEnabled = true, awaitingPublication = pending,
        label = "Favourites", helper = "", error = null, onSelection = onSelection,
        tiles = true, prefixes = listOf("110", "116", "119"), supporting = listOf("$90.00", "$45.00", "$65.00"),
    )
}

@Composable
private fun helperField(tone: String, error: String, dark: Boolean = false) {
    FirstlightTextFieldControl(
        configuration = TextFieldRendererConfiguration(NativeUINode(id = 8, props = GenericProps(mapOf(
            "label" to "Identifier", "helper" to if (tone == "success") "Checksum OK" else "Check this",
            "helper_tone" to tone, "error" to error,
        )))), draft = TextFieldValue("1234"), revealed = false,
        tokens = if (dark) NativeUITheme.dark else NativeUITheme.light,
        onValueChange = {}, onClear = {}, onReveal = {}, onTrailingPress = {}, onSubmit = {},
    )
}
