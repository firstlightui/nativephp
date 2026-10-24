import Foundation
import SnapshotTesting
import XCTest

/// Snapshot references are recorded per iOS major version because each runtime
/// rasterises glyphs and system chrome slightly differently. Add a set here
/// (and record it) before running the suite on a new runtime.
enum FirstlightSnapshotReferences {
    static let directoriesByMajorVersion: [Int: String] = [
        26: "__Snapshots__",
        27: "__Snapshots__iOS27",
    ]

    static let record: SnapshotTestingConfiguration.Record =
        ProcessInfo.processInfo.environment["FIRSTLIGHT_RECORD_SNAPSHOTS"] == "1" ? .all : .never
}

/// Asserts a snapshot against the reference set for the running iOS major version,
/// stored at `tests/ios/<set>/<TestFile>/`.
func assertFirstlightSnapshot<Value, Format>(
    of value: @autoclosure () throws -> Value,
    as snapshotting: Snapshotting<Value, Format>,
    named name: String? = nil,
    fileID: StaticString = #fileID,
    file filePath: StaticString = #filePath,
    testName: String = #function,
    line: UInt = #line,
    column: UInt = #column
) {
    let major = ProcessInfo.processInfo.operatingSystemVersion.majorVersion
    guard let set = FirstlightSnapshotReferences.directoriesByMajorVersion[major] else {
        XCTFail(
            "No iOS \(major) snapshot reference set. Add it to FirstlightSnapshotReferences.directoriesByMajorVersion "
                + "in tests/ios/FirstlightSnapshot.swift and record it with FIRSTLIGHT_RECORD_SNAPSHOTS=1 on an iOS \(major) simulator.",
            file: filePath,
            line: line
        )
        return
    }

    let testFile = URL(fileURLWithPath: "\(filePath)", isDirectory: false)
    let directory = testFile.deletingLastPathComponent()
        .appendingPathComponent(set, isDirectory: true)
        .appendingPathComponent(testFile.deletingPathExtension().lastPathComponent, isDirectory: true)

    if let message = verifySnapshot(
        of: try value(),
        as: snapshotting,
        named: name,
        record: FirstlightSnapshotReferences.record,
        snapshotDirectory: directory.path,
        fileID: fileID,
        file: filePath,
        testName: testName,
        line: line,
        column: column
    ) {
        XCTFail(message, file: filePath, line: line)
    }
}
