package dev.firstlightui.plugins.firstlight_ui.ui

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material3.ListItem
import androidx.compose.material3.ListItemDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.minimumInteractiveComponentSize
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.clip
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.disabled
import androidx.compose.ui.semantics.onClick
import androidx.compose.ui.semantics.role
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import coil3.compose.AsyncImage
import com.nativephp.mobile.ui.MaterialIcon
import com.nativephp.plugins.native_ui.NativeUITokens

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
