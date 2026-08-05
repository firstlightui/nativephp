package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Column
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

class ListItemContractTest {
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
            ),
        ),
    )
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
        ),
    ),
)
