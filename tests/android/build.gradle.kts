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

    sourceSets {
        getByName("main") {
            kotlin.srcDir(syncFirstlightAndroidSources.map { it.destinationDir })

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

tasks.matching { it.name == "preBuild" }.configureEach {
    dependsOn("verifyFirstlightAndroidSourceLayout")
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
    implementation("io.coil-kt.coil3:coil-compose:3.1.0")
    implementation("io.coil-kt.coil3:coil-network-okhttp:3.1.0")

    testImplementation("junit:junit:4.13.2")
}
