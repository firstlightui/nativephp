import SwiftUI

/// UI tests need a host app; the driver never shows it.
@main
struct OrientationHost: App {
    var body: some Scene {
        WindowGroup { EmptyView() }
    }
}
