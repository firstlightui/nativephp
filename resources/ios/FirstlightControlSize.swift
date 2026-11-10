import UIKit

/// The `size` prop shared by Segmented and Pill Group. `lg` matches Button
/// `size="lg"`, so the controls line up in one row of large actions.
enum FirstlightControlSize: String {
    case md
    case lg

    init(wire: String) {
        self = Self(rawValue: wire) ?? .md
    }

    /// The minimum drawn height, which is also the interaction target.
    func minimumHeight(compatibleWith traits: UITraitCollection) -> CGFloat {
        switch self {
        case .md:
            return 44
        case .lg:
            return Self.largeButtonHeight(compatibleWith: traits)
        }
    }

    /// Mobile UI draws Button `size="lg"` as a large SwiftUI button around a
    /// 20pt medium label that scales relative to body: 54pt at the default
    /// text size. Its padding is fixed, so only the label's line height grows
    /// with Dynamic Type.
    static func largeButtonHeight(compatibleWith traits: UITraitCollection) -> CGFloat {
        let standardLineHeight = UIFont.systemFont(ofSize: 20, weight: .medium).lineHeight
        let scaledSize = UIFontMetrics(forTextStyle: .body).scaledValue(for: 20, compatibleWith: traits)
        let scaledLineHeight = UIFont.systemFont(ofSize: scaledSize, weight: .medium).lineHeight
        let height = 54 + (scaledLineHeight - standardLineHeight)
        let scale = max(traits.displayScale, 1)

        return (height * scale).rounded(.up) / scale
    }
}
