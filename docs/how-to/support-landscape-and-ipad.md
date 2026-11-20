---
title: Support landscape and iPad
description: Turn on landscape and iPad in NativePHP's config, and use Firstlight's Android tab bar so tabs get out of the way of the keyboard and short landscape screens.
type: how-to
audience: consumer
sources:
  - vendor/nativephp/mobile/config/nativephp.php
  - vendor/nativephp/mobile/src/Edge/Layouts/Builders/TabBar.php
  - resources/android/FirstlightTabBar.kt
  - resources/android/FirstlightUIInit.kt
  - resources/android/FeedbackCenterControl.kt
  - resources/android/FirstlightPickerDialog.kt
  - resources/ios/FirstlightPickerPresentation.swift
  - resources/ios/ConfirmationDialogControl.swift
  - spec/screenshots.json
---

# Support Landscape and iPad

NativePHP apps are portrait-only on phones until you say otherwise. Firstlight
controls lay out in every orientation and on iPad, so turning these on is a
config change plus one line for the Android tab bar.

## Turn on landscape

Orientation comes from `config/nativephp.php`, which every build applies to
`Info.plist` on iOS and `AndroidManifest.xml` on Android. NativePHP's default
allows portrait only:

```php
'orientation' => [
    'iphone' => [
        'portrait' => true,
        'upside_down' => false,
        'landscape_left' => true,
        'landscape_right' => true,
    ],
    'android' => [
        'portrait' => true,
        'upside_down' => true,
        'landscape_left' => true,
        'landscape_right' => true,
    ],
],
```

The change takes effect in the next build.

On Android, turn on all four. Any other mix that includes landscape becomes
`fullSensor`, which follows the sensor and ignores the user's rotation lock.

## Turn on iPad

Set `'ipad' => true` in the same file. iPad apps support all four
orientations, as Apple requires, whatever the `iphone` settings say.

Once an App Store version supports iPad, later versions can't drop it, and App
Review needs iPad screenshots. Decide before you upload.

On iPad, Firstlight uses the regular-width presentations: Date Picker, Time
Picker and Select open as popovers next to their field, and Confirmation
Dialog is a centred alert with both actions.

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | ![Date Picker popover on iPad in landscape on iOS in light mode](../screenshots/ipad-date-picker/ios-light.png) | ![Date Picker popover on iPad in landscape on iOS in dark mode](../screenshots/ipad-date-picker/ios-dark.png) |

## Use Firstlight's tab bar on Android

On Android, NativePHP draws a layout's tabs as Material's 80 dp navigation bar.
It stays above the keyboard, so it can cover the field being edited, and in
landscape it and the app bar leave little room for content. Select
Firstlight's tab bar in your layout instead:

```php
use Native\Mobile\Edge\Layouts\Builders\Tab;
use Native\Mobile\Edge\Layouts\Builders\TabBar;
use Native\Mobile\Edge\NativeComponent;

public function tabBar(NativeComponent $screen): ?TabBar
{
    return TabBar::make()
        ->presentation('firstlight:tabs')
        ->add(Tab::link('Home', '/', icon: 'home'))
        ->add(Tab::link('Settings', '/settings', icon: 'settings'));
}
```

NativePHP still owns the tabs, selection, badges and navigation. Firstlight
draws the bar the way Material recommends:

| When | Bar |
| --- | --- |
| Keyboard up | Hidden, so the field stays visible |
| Window shorter than 480 dp, such as a phone in landscape | Short bar, 64 dp, label beside the icon |
| Otherwise | Standard navigation bar, 80 dp |

Transient Feedback snackbars sit above whichever bar is showing.

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | ![Tabs on a phone in landscape on iOS in light mode](../screenshots/landscape-tab-bar/ios-light.png) | ![Tabs on a phone in landscape on iOS in dark mode](../screenshots/landscape-tab-bar/ios-dark.png) |
| Android | ![Tabs on a phone in landscape on Android in light mode](../screenshots/landscape-tab-bar/android-light.png) | ![Tabs on a phone in landscape on Android in dark mode](../screenshots/landscape-tab-bar/android-dark.png) |

iOS keeps the system tab bar, which already behaves this way. In debug builds
NativePHP logs one line saying no iOS presenter is registered for
`firstlight:tabs`; that is expected.

## What changes in landscape

- Date Picker and Time Picker on a short Android screen put the header beside
  the calendar and the time beside the clock dial, in a dialog that fits the
  screen and scrolls, with Cancel and Confirm always visible. iPhone keeps a
  sheet sized to the picker.
- The Media crop sheet puts its controls beside the image.
- Bottom Sheet detents don't apply on a phone in landscape: iOS shows the
  sheet at full height.

| Platform | Light | Dark |
| --- | --- | --- |
| iOS | ![Date Picker open on a phone in landscape on iOS in light mode](../screenshots/landscape-date-picker/ios-light.png) | ![Date Picker open on a phone in landscape on iOS in dark mode](../screenshots/landscape-date-picker/ios-dark.png) |
| Android | ![Date Picker open on a phone in landscape on Android in light mode](../screenshots/landscape-date-picker/android-light.png) | ![Date Picker open on a phone in landscape on Android in dark mode](../screenshots/landscape-date-picker/android-dark.png) |
