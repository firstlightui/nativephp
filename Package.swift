// swift-tools-version: 6.2

import PackageDescription

let package = Package(
    name: "FirstlightIOSControls",
    platforms: [
        .iOS(.v18),
    ],
    products: [
        .library(name: "FirstlightIOSControls", targets: ["FirstlightIOSControls"]),
    ],
    dependencies: [
        .package(url: "https://github.com/pointfreeco/swift-snapshot-testing", from: "1.12.0"),
    ],
    targets: [
        .target(
            name: "FirstlightIOSControls",
            path: "resources/ios"
        ),
        .testTarget(
            name: "FirstlightIOSControlsTests",
            dependencies: [
                "FirstlightIOSControls",
                .product(name: "SnapshotTesting", package: "swift-snapshot-testing"),
            ],
            path: "tests/ios"
        ),
    ]
)
