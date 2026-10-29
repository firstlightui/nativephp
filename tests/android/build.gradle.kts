import org.jetbrains.kotlin.gradle.dsl.JvmTarget
import org.gradle.api.tasks.Sync

plugins {
    id("com.android.library") version "8.13.2"
    id("org.jetbrains.kotlin.android") version "2.3.0"
    id("org.jetbrains.kotlin.plugin.compose") version "2.3.0"
    id("app.cash.paparazzi") version "2.0.0-alpha05"
}

val productionAndroidSourceDirectory = layout.projectDirectory.dir("../../resources/android")
val syncFirstlightAndroidSources = tasks.register<Sync>("syncFirstlightAndroidSources") {
    from(productionAndroidSourceDirectory) {
        include("*.kt")
    }
    into(layout.buildDirectory.dir("generated/firstlightAndroidSources"))
}

// Exercise the official focus/flush seam, not a test-double implementation.
val syncNativePhpFocusPolicy = tasks.register<Sync>("syncNativePhpFocusPolicy") {
    from("../../vendor/nativephp/mobile/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/KeyboardFocusPolicy.kt")
    into(layout.buildDirectory.dir("generated/nativePhpFocusPolicy"))
}

// Compile NativePHP's own MaterialIcon and its Material Icons font so icon
// snapshots show the real ligature glyphs that ship in the host app.
val nativePhpAndroidApp = layout.projectDirectory.dir("../../vendor/nativephp/mobile/resources/androidstudio/app/src/main")
val nativePhpIconHelper = nativePhpAndroidApp.file("java/com/nativephp/mobile/ui/IconHelper.kt")
val nativePhpIconFont = nativePhpAndroidApp.file("res/font/material_icons.ttf")

val syncNativePhpIconSources = tasks.register<Sync>("syncNativePhpIconSources") {
    doFirst {
        check(nativePhpIconHelper.asFile.isFile && nativePhpIconFont.asFile.isFile) {
            "NativePHP's MaterialIcon sources are missing from vendor/nativephp/mobile; run composer install in the repository root"
        }
    }
    from(nativePhpIconHelper)
    // AGP drops classes named R from library classes, so point IconHelper at
    // this test library's generated R, which carries the synced font.
    filter { line ->
        if (line == "import com.nativephp.mobile.R") "import dev.firstlightui.plugins.firstlight_ui.tests.R" else line
    }
    into(layout.buildDirectory.dir("generated/nativePhpIconSources"))
}

val nativePhpIconRes = layout.buildDirectory.dir("generated/nativePhpIconRes")
val syncNativePhpIconFont = tasks.register<Sync>("syncNativePhpIconFont") {
    from(nativePhpIconFont)
    into(nativePhpIconRes.map { it.dir("font") })
}

tasks.register("verifyFirstlightAndroidSourceLayout") {
    dependsOn(syncFirstlightAndroidSources)
    inputs.dir(productionAndroidSourceDirectory)
    inputs.dir(syncFirstlightAndroidSources.map { it.destinationDir })

    doLast {
        val shadowSourceDirectory = productionAndroidSourceDirectory.dir("src").asFile
        check(!shadowSourceDirectory.exists()) {
            "resources/android/src shadows NativePHP's flat Android renderer sources"
        }

        val productionSources = productionAndroidSourceDirectory.asFile
            .listFiles { file -> file.isFile && file.extension == "kt" }
            .orEmpty()
            .map { it.name }
            .sorted()
        val stagedSources = syncFirstlightAndroidSources.get().destinationDir
            .listFiles { file -> file.isFile && file.extension == "kt" }
            .orEmpty()
            .map { it.name }
            .sorted()

        check(productionSources.isNotEmpty()) {
            "No flat Android renderer sources found in resources/android"
        }
        check(stagedSources == productionSources) {
            "Generated Android test sources $stagedSources do not match production $productionSources"
        }
    }
}

android {
    namespace = "dev.firstlightui.plugins.firstlight_ui.tests"
    compileSdk = 36

    defaultConfig {
        minSdk = 29
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    buildFeatures {
        compose = true
    }

    testOptions {
        unitTests.isIncludeAndroidResources = true
    }

    sourceSets {
        getByName("main") {
            kotlin.srcDir(syncFirstlightAndroidSources.map { it.destinationDir })
            kotlin.srcDir(syncNativePhpFocusPolicy.map { it.destinationDir })
            kotlin.srcDir(syncNativePhpIconSources.map { it.destinationDir })
            res.srcDir(nativePhpIconRes)

            val nativePhpFork = providers.gradleProperty("nativePhpFork").orNull
            if (nativePhpFork == null) {
                kotlin.srcDir("src/shim/kotlin")
            } else {
                val syncNativePhpBridge = tasks.register<Sync>("syncNativePhpBridge") {
                    from("$nativePhpFork/resources/androidstudio/app/src/main/java/com/nativephp/mobile/ui/nativerender/NativeUIBridge.kt")
                    into(layout.buildDirectory.dir("generated/nativePhpBridge"))
                }
                kotlin.srcDir(syncNativePhpBridge.map { it.destinationDir })
                tasks.matching { it.name == "compileDebugKotlin" }.configureEach {
                    dependsOn(syncNativePhpBridge)
                }
            }
        }
    }
}

tasks.withType<Test>().configureEach {
    inputs.dir(layout.projectDirectory.dir("../fixtures"))
        .withPropertyName("sharedFixtures")
        .withPathSensitivity(PathSensitivity.RELATIVE)
}

tasks.matching { it.name == "preBuild" }.configureEach {
    dependsOn("verifyFirstlightAndroidSourceLayout", syncNativePhpFocusPolicy, syncNativePhpIconSources, syncNativePhpIconFont)
}

kotlin {
    compilerOptions {
        jvmTarget.set(JvmTarget.JVM_17)
    }
}

dependencies {
    val composeBom = platform("androidx.compose:compose-bom:2025.12.00")

    implementation(composeBom)
    implementation("androidx.compose.foundation:foundation")
    implementation("androidx.compose.material3:material3")
    implementation("androidx.compose.ui:ui")
    implementation("androidx.compose.ui:ui-tooling")
    // Matches the NativePHP Android host, which supplies these at runtime.
    implementation("androidx.activity:activity-compose:1.8.2")
    implementation("androidx.lifecycle:lifecycle-runtime-compose:2.9.4")
    implementation("io.coil-kt.coil3:coil-compose:3.1.0")
    implementation("io.coil-kt.coil3:coil-network-okhttp:3.1.0")

    testImplementation("junit:junit:4.13.2")
    testImplementation("androidx.compose.ui:ui-test-junit4")
    debugImplementation("androidx.compose.ui:ui-test-manifest")
    testImplementation("org.robolectric:robolectric:4.16.1")
}
