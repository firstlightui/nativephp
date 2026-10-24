package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.runtime.mutableStateOf
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.platform.LocalLayoutDirection
import androidx.compose.ui.semantics.SemanticsActions
import androidx.compose.ui.test.ExperimentalTestApi
import androidx.compose.ui.test.assertCountEquals
import androidx.compose.ui.test.assertIsDisplayed
import androidx.compose.ui.test.assertIsNotEnabled
import androidx.compose.ui.test.longClick
import androidx.compose.ui.test.onAllNodesWithText
import androidx.compose.ui.test.onNodeWithContentDescription
import androidx.compose.ui.test.onNodeWithText
import androidx.compose.ui.test.performClick
import androidx.compose.ui.test.performTouchInput
import androidx.compose.ui.test.runComposeUiTest
import androidx.compose.ui.test.swipeLeft
import androidx.compose.ui.test.swipeRight
import androidx.compose.ui.unit.LayoutDirection
import androidx.compose.ui.unit.Density
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
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner

@RunWith(RobolectricTestRunner::class)
class ListItemContractTest {
    @Test
    fun `icon colours follow semantic tones and explicit colours win`() {
        val tokens = NativeUITheme.light.copy(primary = Color.Magenta, success = Color.Green, destructive = Color.Red)
        val warning = FirstlightWarningColors(light = "#92400E", dark = "#FDE68A")
        for ((tone, expected) in mapOf(
            "neutral" to tokens.onSurfaceVariant, "info" to Color.Magenta,
            "success" to Color.Green, "danger" to Color.Red,
            "warning" to Color(0xFF92400E), "" to Color.Blue,
        )) {
            assertEquals(expected, firstlightListItemIconColor("", tone, tokens, warning, false, Color.Blue))
            assertEquals(Color(0xFF123ABC), firstlightListItemIconColor("#123ABC", tone, tokens, warning, false, Color.Blue))
        }
        assertEquals(Color(0xFFFDE68A), firstlightListItemIconColor("", "warning", tokens, warning, true, Color.Blue))
        assertEquals(Color(0xFF445566), firstlightListItemIconColor("#456", "danger", tokens, warning, true, Color.Blue))
        assertEquals(Color(0x80123456), firstlightListItemIconColor("#80123456", "success", tokens, warning, false, Color.Blue))

        val decoded = ListItemRendererConfiguration(NativeUINode(id = 9, props = GenericProps(mapOf(
            "leading_icon_tone" to "danger", "trailing_icon_tone" to "warning",
            "leading_icon_color" to "#123ABC", "trailing_icon_color" to "#456",
        ))))
        assertEquals("danger", decoded.leadingIconTone)
        assertEquals("warning", decoded.trailingIconTone)
        assertEquals("#123ABC", decoded.leadingIconColor)
        assertEquals("#456", decoded.trailingIconColor)
    }

    @Test
    fun `configuration decodes the complete row contract`() {
        val configuration = ListItemRendererConfiguration(node())

        assertEquals(7, configuration.nodeId)
        assertEquals("Account", configuration.headline)
        assertEquals("Manage your profile and security", configuration.supporting)
        assertEquals(FirstlightListItemLeadingType.Icon, configuration.leadingType)
        assertEquals("account_circle", configuration.leadingValue)
        assertEquals("outlined", configuration.leadingIconVariant)
        assertEquals(FirstlightListItemTrailingType.Icon, configuration.trailingType)
        assertEquals("chevron_right", configuration.trailingValue)
        assertEquals("outlined", configuration.trailingIconVariant)
        assertEquals("Account settings", configuration.accessibilityLabel)
        assertEquals("Opens account settings", configuration.accessibilityHint)
        assertEquals(41, configuration.callbackId)
        assertTrue(configuration.enabled)
    }

    @Test
    fun `malformed content types defensively render as absent`() {
        val configuration = ListItemRendererConfiguration(node(
            leadingType = "control",
            trailingType = "button",
        ))

        assertEquals(FirstlightListItemLeadingType.None, configuration.leadingType)
        assertEquals(FirstlightListItemTrailingType.None, configuration.trailingType)
    }

    @Test
    fun `disabled and missing callbacks suppress activation`() {
        assertNull(ListItemRendererConfiguration(node(disabled = true)).pressEvent())
        assertNull(ListItemRendererConfiguration(node(callbackId = 0)).pressEvent())
        assertEquals(
            ListItemRendererEvent.Press(callbackId = 41, nodeId = 7),
            ListItemRendererConfiguration(node()).pressEvent(),
        )
    }

    @Test
    fun `swipe actions dispatch only published enabled callbacks`() {
        val configuration = ListItemRendererConfiguration(node(actions = true))
        assertEquals(listOf("Archive"), configuration.leadingActions.map { it.label })
        assertEquals(listOf("Delete", "Flag"), configuration.trailingActions.map { it.label })
        assertEquals("destructive", configuration.trailingActions.first().role)
        assertEquals(ListItemRendererEvent.Press(52, 7), configuration.actionEvent(52))
        assertNull(configuration.actionEvent(999))
        assertNull(ListItemRendererConfiguration(node(disabled = true, actions = true)).actionEvent(52))

        val state = ListItemRendererState(node(actions = true))
        assertTrue(state.serverPublished(NativeUITree(node())))
        assertNull(state.configuration.actionEvent(52))
    }

    @Test
    fun `context menu dispatches only enabled published items`() {
        val configuration = ListItemRendererConfiguration(node(menu = true))
        assertEquals(listOf("Reply", "Forward", "Delete"), configuration.contextMenu.map { it.label })
        assertEquals(listOf(true, false, true), configuration.contextMenu.map { it.enabled })
        assertEquals("destructive", configuration.contextMenu.last().role)
        assertTrue(configuration.hasContextMenu)
        assertEquals(ListItemRendererEvent.Press(61, 7), configuration.actionEvent(61))
        assertEquals(ListItemRendererEvent.Press(63, 7), configuration.actionEvent(63))
        assertNull(configuration.actionEvent(0))
        assertNull(configuration.actionEvent(999))

        val disabledRow = ListItemRendererConfiguration(node(disabled = true, menu = true))
        assertFalse(disabledRow.hasContextMenu)
        assertNull(disabledRow.actionEvent(61))

        val state = ListItemRendererState(node(menu = true))
        assertTrue(state.serverPublished(NativeUITree(node())))
        assertFalse(state.configuration.hasContextMenu)
        assertNull(state.configuration.actionEvent(61))
    }

    @Test
    fun `server publication updates metadata without emitting`() {
        val state = ListItemRendererState(node())
        val updated = node(
            headline = "Billing",
            supporting = "Invoices and payment methods",
            disabled = true,
        )

        assertTrue(state.serverPublished(NativeUITree(updated)))
        assertEquals("Billing", state.configuration.headline)
        assertEquals("Invoices and payment methods", state.configuration.supporting)
        assertTrue(state.configuration.disabled)
        assertNull(state.configuration.pressEvent())
        assertFalse(state.serverPublished(NativeUITree(updated)))
    }

    @Test
    fun `accessibility description uses explicit override or combined visible text`() {
        assertEquals(
            "Account settings. Opens account settings",
            firstlightListItemDescription(
                headline = "Account",
                supporting = "Manage your profile",
                accessibilityLabel = "Account settings",
                accessibilityHint = "Opens account settings",
            ),
        )
        assertEquals(
            "Account. Manage your profile",
            firstlightListItemDescription(
                headline = "Account",
                supporting = "Manage your profile",
                accessibilityLabel = "",
                accessibilityHint = "",
            ),
        )
        assertEquals(
            "Account",
            firstlightListItemDescription(
                headline = "Account",
                supporting = "",
                accessibilityLabel = "",
                accessibilityHint = "",
            ),
        )
    }

    @Test
    fun `row target and edge content preserve accessibility contract`() {
        assertTrue(FIRSTLIGHT_LIST_ITEM_MINIMUM_TARGET >= 48.dp)
        assertTrue(FIRSTLIGHT_LIST_ITEM_LEADING_CONTENT_DECORATIVE)
        assertTrue(FIRSTLIGHT_LIST_ITEM_TRAILING_CONTENT_DECORATIVE)
    }

    private fun node(
        headline: String = "Account",
        supporting: String = "Manage your profile and security",
        leadingType: String = "icon",
        leadingValue: String = "account_circle",
        trailingType: String = "icon",
        trailingValue: String = "chevron_right",
        disabled: Boolean = false,
        callbackId: Int = 41,
        actions: Boolean = false,
        menu: Boolean = false,
    ) = NativeUINode(
        id = 7,
        onPress = callbackId,
        props = GenericProps(
            mapOf(
                "headline" to headline,
                "supporting" to supporting,
                "leading_type" to leadingType,
                "leading_value" to leadingValue,
                "leading_icon_variant" to "outlined",
                "trailing_type" to trailingType,
                "trailing_value" to trailingValue,
                "trailing_icon_variant" to "outlined",
                "disabled" to disabled,
                "a11y_label" to "Account settings",
                "a11y_hint" to "Opens account settings",
                "firstlight_leading_actions_json" to if (actions) LEADING_ACTIONS else "",
                "firstlight_trailing_actions_json" to if (actions) TRAILING_ACTIONS else "",
                "firstlight_context_menu_json" to if (menu) CONTEXT_MENU else "",
            ),
        ),
    )
}

@OptIn(ExperimentalTestApi::class)
@RunWith(RobolectricTestRunner::class)
class ListItemSwipeInteractionTest {
    @Test
    fun `swipes reveal logical edges but only a button tap invokes an action`() {
        for ((direction, scale) in listOf(LayoutDirection.Ltr to 1f, LayoutDirection.Rtl to 1f, LayoutDirection.Ltr to 2f)) {
            runComposeUiTest {
                val actions = mutableListOf<Int>()
                var presses = 0
                setContent {
                    CompositionLocalProvider(
                        LocalLayoutDirection provides direction,
                        LocalDensity provides Density(LocalDensity.current.density, scale),
                    ) {
                        MaterialTheme {
                            FirstlightListItemControl(
                                configuration = ListItemRendererConfiguration(screenshotNode(actions = true)),
                                tokens = NativeUITheme.light,
                                onPress = { presses++ },
                                onAction = { actions += it },
                                // NativePHP NodeView supplies an outer clickable modifier.
                                modifier = Modifier.clickable { presses++ },
                            )
                        }
                    }
                }

                onNodeWithContentDescription("Account. Manage your profile and security").performTouchInput {
                    if (direction == LayoutDirection.Ltr) swipeLeft() else swipeRight()
                }
                onNodeWithText("Delete").assertIsDisplayed()
                onNodeWithText("Flag").assertIsDisplayed()
                assertTrue(actions.isEmpty())
                assertEquals(0, presses)
                onNodeWithText("Delete").performClick()
                assertEquals(listOf(52), actions)
                onNodeWithContentDescription("Account. Manage your profile and security").assertIsDisplayed()

                onNodeWithContentDescription("Account. Manage your profile and security").performTouchInput {
                    if (direction == LayoutDirection.Ltr) swipeRight() else swipeLeft()
                }
                onNodeWithText("Archive").assertIsDisplayed().performClick()
                assertEquals(listOf(52, 51), actions)
                onNodeWithContentDescription("Account. Manage your profile and security").performClick()
                assertEquals(1, presses)
            }
        }
    }

    @Test
    fun `screen reader actions work without swiping and disabling closes an open drawer`() = runComposeUiTest {
        val configuration = mutableStateOf(ListItemRendererConfiguration(screenshotNode(actions = true)))
        val events = mutableListOf<Int>()
        setContent {
            MaterialTheme {
                FirstlightListItemControl(
                    configuration = configuration.value,
                    tokens = NativeUITheme.light,
                    onPress = {},
                    onAction = { events += it },
                )
            }
        }

        val row = onNodeWithContentDescription("Account. Manage your profile and security")
        val actions = row.fetchSemanticsNode().config[SemanticsActions.CustomActions]
        assertEquals(listOf("Archive", "Delete", "Flag"), actions.map { it.label })
        runOnIdle { assertTrue(actions.first { it.label == "Flag" }.action()) }
        assertEquals(listOf(53), events)
        row.performTouchInput { swipeLeft() }
        onNodeWithText("Delete").assertIsDisplayed()
        runOnIdle { configuration.value = configuration.value.copy(disabled = true) }
        onAllNodesWithText("Delete").assertCountEquals(0)
        row.performTouchInput { swipeLeft() }
        assertEquals(listOf(53), events)
        assertFalse(row.fetchSemanticsNode().config.contains(SemanticsActions.CustomActions))
    }
}

@OptIn(ExperimentalTestApi::class)
@RunWith(RobolectricTestRunner::class)
class ListItemContextMenuInteractionTest {
    @Test
    fun `long press opens the menu, items dispatch once, and disabled items are inert`() {
        for (direction in listOf(LayoutDirection.Ltr, LayoutDirection.Rtl)) {
            runComposeUiTest {
                val actions = mutableListOf<Int>()
                var presses = 0
                setContent {
                    CompositionLocalProvider(LocalLayoutDirection provides direction) {
                        MaterialTheme {
                            FirstlightListItemControl(
                                configuration = ListItemRendererConfiguration(screenshotNode(menu = true)),
                                tokens = NativeUITheme.light,
                                onPress = { presses++ },
                                onAction = { actions += it },
                            )
                        }
                    }
                }

                val row = onNodeWithContentDescription("Account. Manage your profile and security")
                onAllNodesWithText("Reply").assertCountEquals(0)
                row.performTouchInput { longClick() }
                onNodeWithText("Reply").assertIsDisplayed()
                onNodeWithText("Forward").assertIsNotEnabled().performClick()
                assertTrue(actions.isEmpty())
                onNodeWithText("Delete").performClick()
                assertEquals(listOf(63), actions)
                onAllNodesWithText("Reply").assertCountEquals(0)
                assertEquals(0, presses)

                row.performClick()
                assertEquals(1, presses)
                assertEquals(listOf(63), actions)
            }
        }
    }

    @Test
    fun `talkback exposes enabled items without long press and disabling removes the menu`() = runComposeUiTest {
        val configuration = mutableStateOf(ListItemRendererConfiguration(screenshotNode(menu = true, actions = true)))
        val events = mutableListOf<Int>()
        setContent {
            MaterialTheme {
                FirstlightListItemControl(
                    configuration = configuration.value,
                    tokens = NativeUITheme.light,
                    onPress = {},
                    onAction = { events += it },
                )
            }
        }

        val row = onNodeWithContentDescription("Account. Manage your profile and security")
        val actions = row.fetchSemanticsNode().config[SemanticsActions.CustomActions]
        assertEquals(listOf("Archive", "Delete", "Flag", "Reply", "Delete"), actions.map { it.label })
        runOnIdle { assertTrue(actions.first { it.label == "Reply" }.action()) }
        assertEquals(listOf(61), events)
        assertTrue(row.fetchSemanticsNode().config.contains(SemanticsActions.OnLongClick))

        row.performTouchInput { longClick() }
        onNodeWithText("Reply").assertIsDisplayed()
        runOnIdle { configuration.value = configuration.value.copy(disabled = true) }
        onAllNodesWithText("Reply").assertCountEquals(0)
        row.performTouchInput { longClick() }
        onAllNodesWithText("Reply").assertCountEquals(0)
        assertFalse(row.fetchSemanticsNode().config.contains(SemanticsActions.CustomActions))
        assertFalse(row.fetchSemanticsNode().config.contains(SemanticsActions.OnLongClick))
        assertEquals(listOf(61), events)
    }
}

class ListItemScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test
    fun light() = snapshot(dark = false)

    @Test
    fun dark() = snapshot(dark = true)

    private fun snapshot(dark: Boolean) {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
                Surface(modifier = Modifier.fillMaxSize()) {
                    listItemGallery(dark)
                }
            }
        }
    }
}

class ListItemLargeTextScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2.0f))

    @Test
    fun fontScaleTwo() {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = darkColorScheme()) {
                Surface(modifier = Modifier.fillMaxSize()) {
                    listItemGallery(dark = true)
                }
            }
        }
    }
}

class ListItemToneScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test fun light() = snapshot(false)
    @Test fun dark() = snapshot(true)

    private fun snapshot(dark: Boolean) {
        paparazzi.snapshot {
            val configuration = android.content.res.Configuration(LocalConfiguration.current).apply {
                uiMode = (uiMode and android.content.res.Configuration.UI_MODE_NIGHT_MASK.inv()) or
                    if (dark) android.content.res.Configuration.UI_MODE_NIGHT_YES else android.content.res.Configuration.UI_MODE_NIGHT_NO
            }
            CompositionLocalProvider(LocalConfiguration provides configuration) {
                MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
                    val tokens = (if (dark) NativeUITheme.dark else NativeUITheme.light).copy(primary = Color.Magenta)
                    Surface {
                        Column {
                            for (tone in listOf("", "neutral", "info", "success", "warning", "danger")) {
                                FirstlightListItemControl(
                                    configuration = ListItemRendererConfiguration(screenshotNode(headline = tone.ifEmpty { "Default (brand)" }))
                                        .copy(leadingIconTone = tone, trailingIconTone = tone),
                                    tokens = tokens, onPress = {},
                                )
                            }
                            FirstlightListItemControl(
                                configuration = ListItemRendererConfiguration(screenshotNode(headline = "Explicit blue overrides danger"))
                                    .copy(leadingIconTone = "danger", trailingIconTone = "danger",
                                        leadingIconColor = "#2563EB", trailingIconColor = "#2563EB"),
                                tokens = tokens, onPress = {},
                            )
                        }
                    }
                }
            }
        }
    }
}

@androidx.compose.runtime.Composable
private fun listItemGallery(dark: Boolean) {
    val tokens = if (dark) NativeUITheme.dark else NativeUITheme.light
    Column(modifier = Modifier.padding(vertical = 8.dp)) {
        FirstlightListItemControl(
            configuration = ListItemRendererConfiguration(screenshotNode()),
            tokens = tokens,
            onPress = {},
        )
        FirstlightListItemControl(
            configuration = ListItemRendererConfiguration(screenshotNode(
                headline = "Wojt Janowski",
                supporting = "Owner",
                leadingType = "monogram",
                leadingValue = "WJ",
                trailingType = "text",
                trailingValue = "Admin",
            )),
            tokens = tokens,
            onPress = {},
        )
        FirstlightListItemControl(
            configuration = ListItemRendererConfiguration(screenshotNode(
                headline = "Unavailable account",
                supporting = "Ask an administrator for access",
                disabled = true,
            )),
            tokens = tokens,
            onPress = {},
        )
    }
}

private fun screenshotNode(
    headline: String = "Account",
    supporting: String = "Manage your profile and security",
    leadingType: String = "icon",
    leadingValue: String = "account_circle",
    trailingType: String = "icon",
    trailingValue: String = "chevron_right",
    disabled: Boolean = false,
    actions: Boolean = false,
    menu: Boolean = false,
) = NativeUINode(
    id = headline.hashCode(),
    onPress = 41,
    props = GenericProps(
        mapOf(
            "headline" to headline,
            "supporting" to supporting,
            "leading_type" to leadingType,
            "leading_value" to leadingValue,
            "trailing_type" to trailingType,
            "trailing_value" to trailingValue,
            "disabled" to disabled,
            "firstlight_leading_actions_json" to if (actions) LEADING_ACTIONS else "",
            "firstlight_trailing_actions_json" to if (actions) TRAILING_ACTIONS else "",
            "firstlight_context_menu_json" to if (menu) CONTEXT_MENU else "",
        ),
    ),
)

private const val LEADING_ACTIONS = """[{"cb":51,"label":"Archive","icon":"archive","icon_variant":"outlined","role":""}]"""
private const val TRAILING_ACTIONS = """[{"cb":52,"label":"Delete","icon":"delete","icon_variant":"outlined","role":"destructive"},{"cb":53,"label":"Flag","icon":"flag","icon_variant":"","role":""}]"""
private const val CONTEXT_MENU = """[{"cb":61,"label":"Reply","icon":"reply","icon_variant":"","role":"","disabled":false},{"cb":0,"label":"Forward","icon":"","icon_variant":"","role":"","disabled":true},{"cb":63,"label":"Delete","icon":"delete","icon_variant":"outlined","role":"destructive","disabled":false}]"""
