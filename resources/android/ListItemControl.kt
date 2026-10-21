package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.animation.core.snap
import androidx.compose.animation.core.spring
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.gestures.Orientation
import androidx.compose.foundation.gestures.draggable
import androidx.compose.foundation.gestures.rememberDraggableState
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.BoxWithConstraints
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.offset
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material3.ListItem
import androidx.compose.material3.ListItemDefaults
import androidx.compose.material3.LocalContentColor
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.minimumInteractiveComponentSize
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableFloatStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.clipToBounds
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.platform.LocalLayoutDirection
import androidx.compose.ui.semantics.CustomAccessibilityAction
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.customActions
import androidx.compose.ui.semantics.disabled
import androidx.compose.ui.semantics.onClick
import androidx.compose.ui.semantics.role
import androidx.compose.ui.text.AnnotatedString
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.rememberTextMeasurer
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.Density
import androidx.compose.ui.unit.IntOffset
import androidx.compose.ui.unit.LayoutDirection
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import coil3.compose.AsyncImage
import com.nativephp.mobile.ui.MaterialIcon
import com.nativephp.plugins.native_ui.NativeUITokens
import kotlin.math.roundToInt

enum class FirstlightListItemLeadingType(val wireName: String) {
    None(""),
    Icon("icon"),
    Avatar("avatar"),
    Monogram("monogram");

    companion object {
        fun fromWire(value: String): FirstlightListItemLeadingType = entries.firstOrNull {
            it.wireName == value
        } ?: None
    }
}

enum class FirstlightListItemTrailingType(val wireName: String) {
    None(""),
    Icon("icon"),
    Text("text");

    companion object {
        fun fromWire(value: String): FirstlightListItemTrailingType = entries.firstOrNull {
            it.wireName == value
        } ?: None
    }
}

val FIRSTLIGHT_LIST_ITEM_MINIMUM_TARGET: Dp = 48.dp
const val FIRSTLIGHT_LIST_ITEM_LEADING_CONTENT_DECORATIVE = true
const val FIRSTLIGHT_LIST_ITEM_TRAILING_CONTENT_DECORATIVE = true

internal fun firstlightListItemDescription(
    headline: String,
    supporting: String,
    accessibilityLabel: String,
    accessibilityHint: String,
): String {
    val label = accessibilityLabel.ifEmpty {
        listOf(headline, supporting).filter(String::isNotEmpty).joinToString(". ")
    }

    return listOf(label, accessibilityHint).filter(String::isNotEmpty).joinToString(". ")
}

@Composable
fun FirstlightListItemControl(
    configuration: ListItemRendererConfiguration,
    tokens: NativeUITokens,
    onPress: () -> Unit,
    modifier: Modifier = Modifier,
    onAction: (Int) -> Unit = {},
) {
    val activate: (Int) -> Unit = { callbackId ->
        if (configuration.actionEvent(callbackId) != null) onAction(callbackId)
    }
    if (!configuration.enabled || (configuration.leadingActions + configuration.trailingActions).isEmpty()) {
        FirstlightListItemContent(configuration, tokens, onPress, modifier, activate)
        return
    }

    val density = LocalDensity.current
    val direction = if (LocalLayoutDirection.current == LayoutDirection.Rtl) -1f else 1f
    var offset by remember(configuration) { mutableFloatStateOf(0f) }
    var dragging by remember(configuration) { mutableStateOf(false) }
    val animatedOffset by animateFloatAsState(offset, if (dragging) snap() else spring(), label = "list-item-reveal")
    val textMeasurer = rememberTextMeasurer()
    val labelStyle = MaterialTheme.typography.labelLarge

    BoxWithConstraints(modifier = modifier.clipToBounds()) {
        val widestLabel = (configuration.leadingActions + configuration.trailingActions).maxOf { action ->
            textMeasurer.measure(AnnotatedString(action.label), style = labelStyle).size.width
        }
        val actionWidth = with(density) { (widestLabel.toDp() + 16.dp).coerceAtLeast(88.dp).coerceAtMost(maxWidth) }
        val leadingWidth = with(density) { (actionWidth * configuration.leadingActions.size).coerceAtMost(maxWidth).toPx() }
        val trailingWidth = with(density) { (actionWidth * configuration.trailingActions.size).coerceAtMost(maxWidth).toPx() }
        val actions = if (offset > 0) configuration.leadingActions else configuration.trailingActions.asReversed()
        if (offset != 0f) {
            Row(
                modifier = Modifier
                    .matchParentSize(),
                horizontalArrangement = if (offset > 0) Arrangement.Start else Arrangement.End,
            ) {
                Row(
                    modifier = Modifier
                        .width(with(density) { (if (offset > 0) leadingWidth else trailingWidth).toDp() })
                        .fillMaxHeight()
                        .horizontalScroll(rememberScrollState(), reverseScrolling = offset < 0),
                ) {
                    actions.forEach { action ->
                        val destructive = action.role == "destructive"
                        val background = if (destructive) tokens.destructive else tokens.primary
                        val foreground = if (destructive) tokens.onDestructive else tokens.onPrimary
                        CompositionLocalProvider(LocalContentColor provides foreground) {
                            Column(
                                modifier = Modifier
                                    .width(actionWidth)
                                    .fillMaxHeight()
                                    .background(background)
                                    .clickable(role = Role.Button) {
                                        offset = 0f
                                        activate(action.callbackId)
                                    }
                                    .padding(8.dp),
                                horizontalAlignment = Alignment.CenterHorizontally,
                                verticalArrangement = Arrangement.spacedBy(4.dp, Alignment.CenterVertically),
                            ) {
                                if (action.icon.isNotEmpty()) {
                                    // Core renders icons as text in a fixed 24dp box.
                                    // Scale action labels, not the decorative icon font.
                                    CompositionLocalProvider(LocalDensity provides Density(density.density, 1f)) {
                                        MaterialIcon(action.icon, contentDescription = null, modifier = Modifier.size(24.dp))
                                    }
                                }
                                Text(action.label, color = foreground, style = MaterialTheme.typography.labelLarge,
                                    textAlign = TextAlign.Center, maxLines = 2, overflow = TextOverflow.Ellipsis)
                            }
                        }
                    }
                }
            }
        }
        FirstlightListItemContent(
            configuration, tokens,
            onPress = { if (offset != 0f) offset = 0f else onPress() },
            modifier = Modifier
                .offset { IntOffset(animatedOffset.roundToInt(), 0) }
                .heightIn(min = 64.dp * density.fontScale.coerceAtLeast(1f))
                .draggable(
                    state = rememberDraggableState { delta ->
                        offset = (offset + delta * direction).coerceIn(-trailingWidth, leadingWidth)
                    },
                    orientation = Orientation.Horizontal,
                    onDragStarted = { dragging = true },
                    onDragStopped = {
                        dragging = false
                        offset = when {
                            offset > leadingWidth / 2 -> leadingWidth
                            offset < -trailingWidth / 2 -> -trailingWidth
                            else -> 0f
                        }
                    },
                ),
            onAction = activate,
        )
    }
}

@Composable
private fun FirstlightListItemContent(
    configuration: ListItemRendererConfiguration,
    tokens: NativeUITokens,
    onPress: () -> Unit,
    modifier: Modifier,
    onAction: (Int) -> Unit,
) {
    val description = firstlightListItemDescription(
        headline = configuration.headline,
        supporting = configuration.supporting,
        accessibilityLabel = configuration.accessibilityLabel,
        accessibilityHint = configuration.accessibilityHint,
    )
    val enabled = configuration.enabled
    val rowModifier = modifier
        .minimumInteractiveComponentSize()
        .clickable(enabled = enabled, role = Role.Button, onClick = onPress)
        .clearAndSetSemantics {
            contentDescription = description
            role = Role.Button
            if (!enabled) {
                disabled()
            } else if ((configuration.leadingActions + configuration.trailingActions).isNotEmpty()) {
                customActions = (configuration.leadingActions + configuration.trailingActions).map { action ->
                    CustomAccessibilityAction(action.label) {
                        if (configuration.actionEvent(action.callbackId) == null) false else {
                            onAction(action.callbackId)
                            true
                        }
                    }
                }
            }
            onClick {
                if (enabled) {
                    onPress()
                    true
                } else {
                    false
                }
            }
        }
        .alpha(if (configuration.disabled) 0.5f else 1f)

    ListItem(
        headlineContent = {
            Text(
                text = configuration.headline,
                style = MaterialTheme.typography.bodyLarge,
            )
        },
        modifier = rowModifier,
        supportingContent = if (configuration.supporting.isNotEmpty()) {
            {
                Text(
                    text = configuration.supporting,
                    style = MaterialTheme.typography.bodyMedium,
                )
            }
        } else {
            null
        },
        leadingContent = firstlightListItemLeadingContent(configuration, tokens),
        trailingContent = firstlightListItemTrailingContent(configuration),
        colors = ListItemDefaults.colors(
            containerColor = tokens.surface,
            headlineColor = tokens.onSurface,
            supportingColor = tokens.onSurfaceVariant,
            leadingIconColor = tokens.primary,
            trailingIconColor = tokens.onSurfaceVariant,
        ),
    )
}

private fun firstlightListItemLeadingContent(
    configuration: ListItemRendererConfiguration,
    tokens: NativeUITokens,
): (@Composable () -> Unit)? = when (configuration.leadingType) {
    FirstlightListItemLeadingType.None -> null
    FirstlightListItemLeadingType.Icon -> ({
        MaterialIcon(
            name = configuration.leadingValue,
            contentDescription = null,
            modifier = Modifier.size(24.dp),
        )
    })
    FirstlightListItemLeadingType.Avatar -> ({
        AsyncImage(
            model = configuration.leadingValue,
            contentDescription = null,
            modifier = Modifier
                .size(40.dp)
                .clip(CircleShape),
            contentScale = ContentScale.Crop,
        )
    })
    FirstlightListItemLeadingType.Monogram -> ({
        Box(
            modifier = Modifier
                .size(40.dp)
                .background(tokens.primary, CircleShape),
            contentAlignment = Alignment.Center,
        ) {
            Text(
                text = configuration.leadingValue.uppercase(),
                color = tokens.onPrimary,
                fontSize = 14.sp,
                fontWeight = FontWeight.SemiBold,
                textAlign = TextAlign.Center,
                maxLines = 1,
            )
        }
    })
}

private fun firstlightListItemTrailingContent(
    configuration: ListItemRendererConfiguration,
): (@Composable () -> Unit)? = when (configuration.trailingType) {
    FirstlightListItemTrailingType.None -> null
    FirstlightListItemTrailingType.Icon -> ({
        MaterialIcon(
            name = configuration.trailingValue,
            contentDescription = null,
            modifier = Modifier.size(24.dp),
        )
    })
    FirstlightListItemTrailingType.Text -> ({
        Text(
            text = configuration.trailingValue,
            style = MaterialTheme.typography.labelLarge,
        )
    })
}
