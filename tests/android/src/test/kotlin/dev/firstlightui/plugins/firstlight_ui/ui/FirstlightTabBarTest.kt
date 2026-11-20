package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.lightColorScheme
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import app.cash.paparazzi.DeviceConfig
import app.cash.paparazzi.Paparazzi
import com.android.resources.ScreenOrientation
import com.nativephp.mobile.ui.nativerender.GenericProps
import com.nativephp.mobile.ui.nativerender.NativeTabDescriptor
import com.nativephp.mobile.ui.nativerender.NativeTabPresentationConfiguration
import com.nativephp.mobile.ui.nativerender.NativeTabPresentationContext
import com.nativephp.mobile.ui.nativerender.NativeTabRole
import com.nativephp.mobile.ui.nativerender.NativeUINode
import org.junit.Assert.assertEquals
import org.junit.Rule
import org.junit.Test

class FirstlightTabBarContractTest {
    @Test
    fun `keyboard hides the bar and a short window gets the short bar`() {
        assertEquals(FirstlightTabBar.Form.Standard, FirstlightTabBar.form(hidden = false, imeVisible = false, screenHeightDp = 480))
        assertEquals(FirstlightTabBar.Form.Short, FirstlightTabBar.form(hidden = false, imeVisible = false, screenHeightDp = 479))
        assertEquals(FirstlightTabBar.Form.Hidden, FirstlightTabBar.form(hidden = false, imeVisible = true, screenHeightDp = 900))
        assertEquals(FirstlightTabBar.Form.Hidden, FirstlightTabBar.form(hidden = true, imeVisible = false, screenHeightDp = 900))
    }

    @Test
    fun `snackbar clears whichever tab bar is drawn`() {
        val system = tabsRoot(presentation = "")
        val firstlight = tabsRoot(presentation = FirstlightTabBar.IDENTIFIER)

        assertEquals(80.dp, FeedbackCenterRenderingPolicy.tabBarHeight(system, imeVisible = true, screenHeightDp = 400))
        assertEquals(64.dp, FeedbackCenterRenderingPolicy.tabBarHeight(firstlight, imeVisible = false, screenHeightDp = 400))
        assertEquals(0.dp, FeedbackCenterRenderingPolicy.tabBarHeight(firstlight, imeVisible = true, screenHeightDp = 900))
        assertEquals(0.dp, FeedbackCenterRenderingPolicy.tabBarHeight(tabsRoot(presentation = "", hidden = true), false, 900))
        assertEquals(0.dp, FeedbackCenterRenderingPolicy.tabBarHeight(NativeUINode(id = 1, type = "column", props = GenericProps()), false, 900))
    }

    private fun tabsRoot(presentation: String, hidden: Boolean = false) = NativeUINode(
        id = 1,
        type = "native_root_tabs",
        props = GenericProps(mapOf("tab_presentation_id" to presentation, "hide_tab_bar" to hidden)),
        children = listOf(NativeUINode(id = 2, type = "bottom_nav_item", props = GenericProps())),
    )
}

class FirstlightTabBarScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test
    fun portrait() = snapshot()

    @Test
    fun landscape() {
        paparazzi.unsafeUpdateConfig(DeviceConfig.PIXEL_5.copy(orientation = ScreenOrientation.LANDSCAPE))
        snapshot()
    }

    private fun snapshot() {
        val tabs = listOf(
            tab("gallery", "Gallery", "auto_awesome"),
            tab("recipes", "Recipes", "layers", badge = "3"),
            tab("components", "Components", "widgets"),
            tab("docs", "Docs", "menu_book"),
        )
        val context = NativeTabPresentationContext(
            tabs = tabs,
            selectedTabIdentifier = tabs[2].id,
            configuration = NativeTabPresentationConfiguration.parse("{}")!!,
            isHidden = false,
            activation = {},
        )
        paparazzi.snapshot {
            MaterialTheme(colorScheme = lightColorScheme()) {
                Surface(Modifier.fillMaxSize()) {
                    Box(Modifier.fillMaxSize(), contentAlignment = Alignment.BottomCenter) {
                        FirstlightTabBar.Render(context)
                    }
                }
            }
        }
    }

    private fun tab(id: String, label: String, icon: String, badge: String? = null) = NativeTabDescriptor(
        id = "$id#0",
        label = label,
        accessibilityLabel = label,
        icon = icon,
        badge = badge,
        showsNews = false,
        role = NativeTabRole.NAVIGATION,
    )
}
