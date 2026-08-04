---
title: Shared icon contract
description: Current naming, resolution, accessibility, validation, and evidence rules for every icon-bearing Firstlight control.
status: current
audience: maintainer
sources:
  - Constitution.md
  - vendor/nativephp/mobile/src/Icon/IconResolver.php
  - vendor/nativephp/mobile/src/Icon/IosSymbol.php
  - vendor/nativephp/mobile/src/Icon/AndroidSymbol.php
  - vendor/nativephp/mobile-ui/src/Elements/BaseTextInput.php
---

# Shared Icon Contract

## Scope

Every Firstlight component that exposes authored icons uses NativePHP's icon vocabulary and `IconResolver`. A component retains the base prop already established for that intent and appends platform overrides consistently; it does not invent a Firstlight-only icon catalogue, resolver, or platform escape prop.

## Public naming

Blade documentation uses kebab-case. PHP attribute adapters may also accept the matching camelCase aliases when NativePHP does.

| Slot established by the component | Shared fallback | iOS override | Android override |
| --- | --- | --- | --- |
| Primary icon | `icon` | `icon-ios` | `icon-android` |
| Leading icon | `leading-icon` | `leading-icon-ios` | `leading-icon-android` |
| Trailing icon | `trailing-icon` | `trailing-icon-ios` | `trailing-icon-android` |
| Existing compound slot such as `icon-trailing` | `icon-trailing` | `icon-trailing-ios` | `icon-trailing-android` |

The platform suffix follows the complete established base name. Components must not publish alternatives such as `ios-icon`, `android-icon`, `sf-symbol`, `material-icon`, `leading-ios-icon`, or `trailing-android-icon`.

For a leading or trailing slot, the fluent API uses NativePHP's argument order and types:

```php
public function leadingIcon(
    ?string $name = null,
    IosSymbol|string|null $ios = null,
    AndroidSymbol|string|null $android = null,
): static;

public function trailingIcon(
    ?string $name = null,
    IosSymbol|string|null $ios = null,
    AndroidSymbol|string|null $android = null,
): static;
```

A differently named established fluent method, such as `icon()` or `iconTrailing()`, retains its NativePHP name but uses the same `(name, ios, android)` argument contract.

## Resolution and wire contract

Resolution occurs in PHP before Element Tree publication:

1. On iOS, the iOS override wins; otherwise the shared fallback is used.
2. On Android, the Android override wins; otherwise the shared fallback is used.
3. On an unknown platform, the shared fallback is used so tests and tooling remain deterministic.
4. A typed `AndroidSymbol` publishes its `filled` or `outlined` variant beside the resolved icon name.
5. A string override publishes the string without inventing a Material variant.

Renderers consume only resolved primitive props such as `leading_icon`, `leading_icon_variant`, `trailing_icon`, and `trailing_icon_variant`. Swift and Kotlin do not choose between authored fallback and override values.

## Accessibility and interaction

Decorative icons are hidden from VoiceOver and TalkBack and do not duplicate the control's label. An icon-only action requires a separate explicit accessible label using the NativePHP name established for its slot, such as `a11y-label` for Icon Button or `trailing-a11y-label` for a trailing action. It owns a distinct native accessibility node and a minimum 44-point iOS or 48-dp Android target.

Semantic built-in affordances such as `clearable` or `revealable` own their platform-native icons and localized accessibility state internally. They do not require consumer-authored icon names and must reject conflicting authored content for the same slot.

## Validation and failure behaviour

An icon-bearing component fails with actionable guidance when authored combinations are incomplete or incompatible: an interactive icon without its accessible label, a platform override of an unsupported type, mutually exclusive semantic affordances, or two features claiming the same slot. Missing optional icons remain absent; Firstlight never substitutes an unrelated glyph.

## Evidence

Every icon-bearing contract proves shared fallback, iOS override, Android override, unknown-platform fallback, Android variant preservation, camelCase aliases where supported, renderer prop decoding, decorative semantics, action labels and targets, right-to-left placement, light/dark rendering, and conflict diagnostics. Public component documentation lists every applicable shared and platform override prop.
