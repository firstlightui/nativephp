package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.BoxWithConstraints
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.material3.Badge
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.PrimaryScrollableTabRow
import androidx.compose.material3.PrimaryTabRow
import androidx.compose.material3.Tab
import androidx.compose.material3.TabIndicatorScope
import androidx.compose.material3.TabRowDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.semantics.CollectionInfo
import androidx.compose.ui.semantics.CollectionItemInfo
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.collectionInfo
import androidx.compose.ui.semantics.collectionItemInfo
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.text.rememberTextMeasurer
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp

internal data class TabsTokenColors(
    val container: Color,
    val selectedContent: Color,
    val unselectedContent: Color,
    val indicator: Color,
    val divider: Color,
    val badgeContainer: Color,
    val badgeContent: Color,
)

/**
 * Material's automatic tab mode: a fixed row when every label fits its equal
 * share of the width on one line, otherwise a scrollable row sized to labels.
 */
internal fun tabsFitFixedRow(contentWidths: List<Float>, availableWidth: Float): Boolean =
    contentWidths.isEmpty() ||
        (contentWidths.max() + TabHorizontalPaddingDp * 2) * contentWidths.size <= availableWidth

private const val TabHorizontalPaddingDp = 16f
private const val BadgeGapDp = 4f
private const val BadgeHorizontalPaddingDp = 4f
private const val BadgeMinimumWidthDp = 16f
private const val ScrollableTabMinimumWidthDp = 72f
private const val DisabledContentAlpha = 0.38f

@Composable
internal fun FirstlightTabsControl(
    labels: List<String>,
    badges: List<String>,
    accessibilityLabels: List<String>,
    enabled: List<Boolean>,
    selectedIndex: Int?,
    groupEnabled: Boolean,
    onSelection: (Int) -> Unit,
    colors: TabsTokenColors,
    modifier: Modifier = Modifier,
    groupDescription: String = "",
) {
    val indicatorIndex = selectedIndex?.takeIf(labels.indices::contains)
    val labelStyle = MaterialTheme.typography.titleSmall
    val badgeStyle = MaterialTheme.typography.labelSmall
    val measurer = rememberTextMeasurer()
    val density = LocalDensity.current

    BoxWithConstraints(modifier = modifier.fillMaxWidth()) {
        val contentWidths = remember(labels, badges, labelStyle, badgeStyle, density) {
            with(density) {
                labels.mapIndexed { index, label ->
                    val labelWidth = measurer.measure(label, labelStyle, maxLines = 1).size.width.toDp().value
                    val badge = badges.getOrElse(index) { "" }
                    if (badge.isEmpty()) {
                        labelWidth
                    } else {
                        val badgeText = measurer.measure(badge, badgeStyle, maxLines = 1).size.width.toDp().value
                        labelWidth + BadgeGapDp + maxOf(BadgeMinimumWidthDp, badgeText + BadgeHorizontalPaddingDp * 2)
                    }
                }
            }
        }
        val fixed = tabsFitFixedRow(contentWidths, maxWidth.value)

        val rowModifier = Modifier
            .fillMaxWidth()
            .semantics {
                if (groupDescription.isNotEmpty()) {
                    contentDescription = groupDescription
                }
                collectionInfo = CollectionInfo(rowCount = 1, columnCount = labels.size)
            }
        val indicator: @Composable TabIndicatorScope.() -> Unit = {
            if (indicatorIndex != null) {
                TabRowDefaults.PrimaryIndicator(
                    modifier = Modifier.tabIndicatorOffset(indicatorIndex, matchContentSize = true),
                    width = Dp.Unspecified,
                    color = colors.indicator,
                )
            }
        }
        val divider: @Composable () -> Unit = { HorizontalDivider(color = colors.divider) }
        val tabs: @Composable () -> Unit = {
            labels.forEachIndexed { index, label ->
                FirstlightTab(
                    index = index,
                    label = label,
                    badge = badges.getOrElse(index) { "" },
                    description = accessibilityLabels.getOrElse(index) { label }.ifEmpty { label },
                    selected = indicatorIndex == index,
                    enabled = groupEnabled && enabled.getOrElse(index) { false },
                    onClick = { onSelection(index) },
                    colors = colors,
                )
            }
        }

        // Material requires an index even with no selection; the indicator
        // is omitted in that case so no tab appears selected.
        if (fixed) {
            PrimaryTabRow(
                selectedTabIndex = indicatorIndex ?: 0,
                modifier = rowModifier,
                containerColor = colors.container,
                contentColor = colors.selectedContent,
                indicator = indicator,
                divider = divider,
                tabs = tabs,
            )
        } else {
            PrimaryScrollableTabRow(
                selectedTabIndex = indicatorIndex ?: 0,
                modifier = rowModifier,
                containerColor = colors.container,
                contentColor = colors.selectedContent,
                // MDC TabLayout's phone metrics: the row starts at the edge
                // and short labels are not padded to 90 dp, so four typical
                // sections stay visible before scrolling.
                edgePadding = 0.dp,
                minTabWidth = ScrollableTabMinimumWidthDp.dp,
                indicator = indicator,
                divider = divider,
                tabs = tabs,
            )
        }
    }
}

@Composable
private fun FirstlightTab(
    index: Int,
    label: String,
    badge: String,
    description: String,
    selected: Boolean,
    enabled: Boolean,
    onClick: () -> Unit,
    colors: TabsTokenColors,
) {
    Tab(
        // Tab supplies the Material tab role, selected state, disabled
        // state, and a 48 dp minimum target.
        selected = selected,
        onClick = onClick,
        enabled = enabled,
        modifier = Modifier.semantics {
            contentDescription = description
            collectionItemInfo = CollectionItemInfo(
                rowIndex = 0,
                rowSpan = 1,
                columnIndex = index,
                columnSpan = 1,
            )
        },
        // Material Tab has no disabled colour; apply the standard disabled
        // content alpha so unavailable sections read as unavailable.
        selectedContentColor = colors.selectedContent.copy(alpha = if (enabled) 1f else DisabledContentAlpha),
        unselectedContentColor = colors.unselectedContent.copy(alpha = if (enabled) 1f else DisabledContentAlpha),
        text = {
            // BadgedBox overlaps its anchor, which suits icons but hides the
            // end of a text label, so the Material badge follows the label.
            Row(
                modifier = Modifier.clearAndSetSemantics {},
                horizontalArrangement = Arrangement.spacedBy(BadgeGapDp.dp),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                TabLabel(label)
                if (badge.isNotEmpty()) {
                    Badge(
                        modifier = Modifier.alpha(if (enabled) 1f else DisabledContentAlpha),
                        containerColor = colors.badgeContainer,
                        contentColor = colors.badgeContent,
                    ) {
                        Text(badge)
                    }
                }
            }
        },
    )
}

@Composable
private fun TabLabel(label: String) {
    Text(text = label, maxLines = 1, overflow = TextOverflow.Ellipsis)
}
