package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.semantics.SemanticsProperties
import androidx.compose.ui.test.ExperimentalTestApi
import androidx.compose.ui.test.SemanticsMatcher
import androidx.compose.ui.test.assert
import androidx.compose.ui.test.assertHeightIsAtLeast
import androidx.compose.ui.test.assertIsEnabled
import androidx.compose.ui.test.assertIsNotEnabled
import androidx.compose.ui.test.assertIsNotSelected
import androidx.compose.ui.test.assertIsSelected
import androidx.compose.ui.test.onNodeWithContentDescription
import androidx.compose.ui.test.performClick
import androidx.compose.ui.test.runComposeUiTest
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

private fun tabsNode(
    id: Int = 7,
    selectedValue: String? = "overview",
    enabled: List<String> = listOf("1", "1", "1", "0"),
    disabled: Boolean = false,
    onChange: Int = 41,
) = NativeUINode(
    id = id,
    props = GenericProps(
        mapOf(
            "tab_values" to listOf("overview", "sites", "deploys", "manage"),
            "tab_labels" to listOf("Overview", "Sites", "Deployments", "Manage"),
            "tab_badges" to listOf("", "", "2", ""),
            "tab_a11y_labels" to listOf("Overview", "Sites", "Deployments, 2 failed", "Manage"),
            "tab_enabled" to enabled,
            "has_selection" to (selectedValue != null),
            "selected_value" to (selectedValue ?: ""),
            "on_change" to onChange,
            "disabled" to disabled,
            "a11y_label" to "Server sections",
            "a11y_hint" to "Shows one section",
        ),
    ),
)

private fun treeWith(target: NativeUINode) = NativeUITree(
    root = NativeUINode(id = 99, props = GenericProps(), children = listOf(target)),
)

class TabsRendererContractTest {
    @Test
    fun `configuration decodes values badges accessibility and selection`() {
        val configuration = TabsRendererConfiguration(tabsNode(selectedValue = "deploys"))

        assertEquals(listOf("overview", "sites", "deploys", "manage"), configuration.values)
        assertEquals(listOf("", "", "2", ""), configuration.badges)
        assertEquals("Deployments, 2 failed", configuration.accessibilityLabels[2])
        assertEquals(listOf(true, true, true, false), configuration.enabled)
        assertEquals(2, configuration.selectedIndex)
        assertEquals("Server sections. Shows one section", configuration.groupDescription)
    }

    @Test
    fun `null and unknown values leave every tab unselected`() {
        assertNull(TabsRendererConfiguration(tabsNode(selectedValue = null)).selectedIndex)
        assertNull(TabsRendererConfiguration(tabsNode(selectedValue = "archived")).selectedIndex)
    }

    @Test
    fun `tap proposes the stable value without moving selection`() {
        val state = TabsRendererState(tabsNode())

        val event = state.userSelected(2)

        assertEquals(TabsRendererEvent(41, 7, "deploys"), event)
        assertEquals("SELECT_CHANGE", event?.wireName)
        assertEquals(0, state.configuration.selectedIndex)
    }

    @Test
    fun `selected disabled out of range and unbound taps emit nothing`() {
        assertNull(TabsRendererState(tabsNode()).userSelected(0))
        assertNull(TabsRendererState(tabsNode()).userSelected(3))
        assertNull(TabsRendererState(tabsNode()).userSelected(9))
        assertNull(TabsRendererState(tabsNode(disabled = true)).userSelected(1))
        assertNull(TabsRendererState(tabsNode(onChange = 0)).userSelected(1))
    }

    @Test
    fun `server publication decides the selected tab including rejection`() {
        val state = TabsRendererState(tabsNode())
        state.userSelected(1)

        assertFalse(state.serverPublished(treeWith(tabsNode(selectedValue = "overview"))))
        assertEquals(0, state.configuration.selectedIndex)

        assertTrue(state.serverPublished(treeWith(tabsNode(selectedValue = "sites"))))
        assertEquals(1, state.configuration.selectedIndex)
    }

    @Test
    fun `publications for other nodes are ignored`() {
        val state = TabsRendererState(tabsNode())

        assertFalse(state.serverPublished(NativeUITree(root = NativeUINode(id = 99, props = GenericProps()))))
        assertFalse(state.serverPublished(tabsNode(id = 8, selectedValue = "sites")))
        assertEquals(0, state.configuration.selectedIndex)
    }

    @Test
    fun `fixed row is used only when the widest label fits its equal share`() {
        assertTrue(tabsFitFixedRow(listOf(60f, 30f), availableWidth = 184f))
        assertFalse(tabsFitFixedRow(listOf(60f, 30f), availableWidth = 183.9f))
        assertFalse(tabsFitFixedRow(listOf(30f, 30f, 30f, 30f, 120f), availableWidth = 393f))
        assertTrue(tabsFitFixedRow(emptyList(), availableWidth = 0f))
    }

    @Test
    fun `token colours keep selected text and badges legible`() {
        listOf(NativeUITheme.light, NativeUITheme.dark).forEach { tokens ->
            val colors = resolveTabsTokenColors(tokens)

            assertTrue(contrastRatio(colors.selectedContent, colors.container) >= 4.5f)
            assertTrue(contrastRatio(colors.badgeContent, colors.badgeContainer) >= 4.5f)
            assertEquals(1f, colors.container.alpha)
        }
    }
}

@OptIn(ExperimentalTestApi::class)
@RunWith(RobolectricTestRunner::class)
class TabsSemanticsTest {
    @Test
    fun `production renderer exposes tab roles position badge text and server-owned selection`() = runComposeUiTest {
        val events = mutableListOf<TabsRendererEvent>()
        setContent {
            MaterialTheme {
                TabsRenderer.Render(
                    node = tabsNode(),
                    modifier = Modifier,
                    events = TabsRendererEvents { events += it },
                )
            }
        }

        val tab = SemanticsMatcher.expectValue(SemanticsProperties.Role, Role.Tab)
        onNodeWithContentDescription("Overview").assert(tab).assertIsSelected()
            .assertHeightIsAtLeast(48.dp)
        onNodeWithContentDescription("Deployments, 2 failed").assert(tab).assertIsNotSelected()
            .assert(
                SemanticsMatcher("third of four tabs") {
                    val item = it.config[SemanticsProperties.CollectionItemInfo]
                    item.rowIndex == 0 && item.columnIndex == 2 && item.columnSpan == 1
                },
            )
            .assertIsEnabled()
        onNodeWithContentDescription("Manage").assertIsNotEnabled()
        onNodeWithContentDescription("Server sections. Shows one section").assert(
            SemanticsMatcher("four-column collection") {
                it.config[SemanticsProperties.CollectionInfo].columnCount == 4
            },
        )

        onNodeWithContentDescription("Deployments, 2 failed").performClick()
        onNodeWithContentDescription("Overview").performClick()
        onNodeWithContentDescription("Manage").performClick()

        runOnIdle { assertEquals(listOf(TabsRendererEvent(41, 7, "deploys")), events) }
        onNodeWithContentDescription("Overview").assertIsSelected()
        onNodeWithContentDescription("Deployments, 2 failed").assertIsNotSelected()
    }

    @Test
    fun `unselected value draws no selected tab`() = runComposeUiTest {
        setContent {
            MaterialTheme {
                TabsRenderer.Render(node = tabsNode(selectedValue = null), modifier = Modifier)
            }
        }

        listOf("Overview", "Sites", "Deployments, 2 failed", "Manage").forEach {
            onNodeWithContentDescription(it).assertIsNotSelected()
        }
    }
}

class TabsControlScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5)

    @Test
    fun light() = snapshot(dark = false)

    @Test
    fun dark() = snapshot(dark = true)

    @Test
    fun unselectedAndDisabled() {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = lightColorScheme()) {
                val tokens = NativeUITheme.light
                Surface(modifier = Modifier.fillMaxSize()) {
                    Column(modifier = Modifier.padding(vertical = 16.dp)) {
                        sample(tokens, selectedIndex = null, groupEnabled = true)
                        Text("Disabled group", modifier = Modifier.padding(16.dp))
                        sample(tokens, selectedIndex = 1, groupEnabled = false)
                        Text("Five long labels scroll", modifier = Modifier.padding(16.dp))
                        FirstlightTabsControl(
                            labels = listOf("Overview", "Deployments", "Environment", "Scheduler", "Manage"),
                            badges = listOf("", "99+", "", "", ""),
                            accessibilityLabels = listOf("Overview", "Deployments, 99+", "Environment", "Scheduler", "Manage"),
                            enabled = listOf(true, true, true, true, true),
                            selectedIndex = 1,
                            groupEnabled = true,
                            onSelection = {},
                            colors = resolveTabsTokenColors(tokens),
                        )
                    }
                }
            }
        }
    }

    private fun snapshot(dark: Boolean) {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = if (dark) darkColorScheme() else lightColorScheme()) {
                val tokens = if (dark) NativeUITheme.dark else NativeUITheme.light
                Surface(modifier = Modifier.fillMaxSize()) {
                    Column(modifier = Modifier.padding(vertical = 16.dp)) {
                        sample(tokens, selectedIndex = 2, groupEnabled = true)
                        Text("Short labels stay fixed", modifier = Modifier.padding(16.dp))
                        FirstlightTabsControl(
                            labels = listOf("Overview", "Sites", "Logs"),
                            badges = listOf("", "3", ""),
                            accessibilityLabels = listOf("Overview", "Sites, 3", "Logs"),
                            enabled = listOf(true, true, true),
                            selectedIndex = 0,
                            groupEnabled = true,
                            onSelection = {},
                            colors = resolveTabsTokenColors(tokens),
                        )
                    }
                }
            }
        }
    }
}

class TabsControlLargeTextScreenshotTest {
    @get:Rule
    val paparazzi = Paparazzi(deviceConfig = DeviceConfig.PIXEL_5.copy(fontScale = 2.0f))

    @Test
    fun fontScaleTwo() {
        paparazzi.snapshot {
            MaterialTheme(colorScheme = darkColorScheme()) {
                Surface(modifier = Modifier.fillMaxSize()) {
                    Column(modifier = Modifier.padding(vertical = 16.dp)) {
                        sample(NativeUITheme.dark, selectedIndex = 0, groupEnabled = true)
                    }
                }
            }
        }
    }
}

@androidx.compose.runtime.Composable
private fun sample(
    tokens: com.nativephp.plugins.native_ui.NativeUITokens,
    selectedIndex: Int?,
    groupEnabled: Boolean,
) {
    FirstlightTabsControl(
        labels = listOf("Overview", "Sites", "Deployments", "Manage"),
        badges = listOf("", "", "2", ""),
        accessibilityLabels = listOf("Overview", "Sites", "Deployments, 2 failed", "Manage"),
        enabled = listOf(true, true, true, false),
        selectedIndex = selectedIndex,
        groupEnabled = groupEnabled,
        onSelection = {},
        colors = resolveTabsTokenColors(tokens),
        groupDescription = "Server sections",
    )
}
