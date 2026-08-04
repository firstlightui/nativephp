import SwiftUI
import UIKit

struct FirstlightPillGroupTokens {
    let selectedBackground: Color
    let selectedForeground: Color
    let unselectedBackground: Color
    let unselectedForeground: Color
    let outline: Color
    let label: Color
    let helper: Color
    let error: Color

    static func from(theme: NativeUITokens, traits: UITraitCollection) -> Self {
        let primary = UIColor(theme.primary).resolvedColor(with: traits)
        let preferred = UIColor(theme.onPrimary).resolvedColor(with: traits)

        return Self(
            selectedBackground: Color(uiColor: primary),
            selectedForeground: Color(
                uiColor: FirstlightSegmentedTokens.contrastSafeSelectedTextColor(
                    primary: primary,
                    preferred: preferred
                )
            ),
            unselectedBackground: theme.surfaceVariant,
            unselectedForeground: theme.onSurface,
            outline: theme.onSurfaceVariant.opacity(0.55),
            label: theme.onSurface,
            helper: theme.onSurfaceVariant,
            error: theme.destructive
        )
    }
}

struct PillFlowLayout: Layout {
    var layoutDirection: LayoutDirection = .leftToRight
    var horizontalSpacing: CGFloat = 8
    var verticalSpacing: CGFloat = 8

    func sizeThatFits(
        proposal: ProposedViewSize,
        subviews: Subviews,
        cache: inout ()
    ) -> CGSize {
        let unconstrainedSizes = subviews.map { $0.sizeThatFits(.unspecified) }
        let intrinsicWidth = unconstrainedSizes.reduce(0) { $0 + $1.width }
            + horizontalSpacing * CGFloat(max(0, unconstrainedSizes.count - 1))
        let width = proposal.width ?? intrinsicWidth
        let sizes = subviews.map {
            $0.sizeThatFits(ProposedViewSize(width: width, height: nil))
        }
        let frames = Self.frames(
            containerWidth: width,
            sizes: sizes,
            horizontalSpacing: horizontalSpacing,
            verticalSpacing: verticalSpacing,
            layoutDirection: layoutDirection
        )

        return CGSize(
            width: width,
            height: frames.map(\.maxY).max() ?? 0
        )
    }

    func placeSubviews(
        in bounds: CGRect,
        proposal: ProposedViewSize,
        subviews: Subviews,
        cache: inout ()
    ) {
        let sizes = subviews.map {
            $0.sizeThatFits(ProposedViewSize(width: bounds.width, height: nil))
        }
        let frames = Self.frames(
            containerWidth: bounds.width,
            sizes: sizes,
            horizontalSpacing: horizontalSpacing,
            verticalSpacing: verticalSpacing,
            layoutDirection: layoutDirection
        )

        for (subview, frame) in zip(subviews, frames) {
            subview.place(
                at: CGPoint(x: bounds.minX + frame.minX, y: bounds.minY + frame.minY),
                anchor: .topLeading,
                proposal: ProposedViewSize(frame.size)
            )
        }
    }

    static func frames(
        containerWidth: CGFloat,
        sizes: [CGSize],
        horizontalSpacing: CGFloat,
        verticalSpacing: CGFloat,
        layoutDirection: LayoutDirection
    ) -> [CGRect] {
        guard !sizes.isEmpty else { return [] }

        var rows: [[Int]] = [[]]
        var rowWidths: [CGFloat] = [0]
        var rowHeights: [CGFloat] = [0]

        for (index, size) in sizes.enumerated() {
            let row = rows.count - 1
            let proposedWidth = rowWidths[row]
                + (rows[row].isEmpty ? 0 : horizontalSpacing)
                + size.width

            if !rows[row].isEmpty, proposedWidth > containerWidth {
                rows.append([index])
                rowWidths.append(size.width)
                rowHeights.append(size.height)
            } else {
                rows[row].append(index)
                rowWidths[row] = proposedWidth
                rowHeights[row] = max(rowHeights[row], size.height)
            }
        }

        var frames = Array(repeating: CGRect.zero, count: sizes.count)
        var y: CGFloat = 0

        for rowIndex in rows.indices {
            let indices = rows[rowIndex]
            var x: CGFloat = layoutDirection == .rightToLeft
                ? containerWidth
                : 0

            for index in indices {
                let size = sizes[index]
                if layoutDirection == .rightToLeft {
                    x -= size.width
                    frames[index] = CGRect(origin: CGPoint(x: x, y: y), size: size)
                    x -= horizontalSpacing
                } else {
                    frames[index] = CGRect(origin: CGPoint(x: x, y: y), size: size)
                    x += size.width + horizontalSpacing
                }
            }

            y += rowHeights[rowIndex] + verticalSpacing
        }

        return frames
    }
}

struct FirstlightPillGroupField: View {
    @Environment(\.layoutDirection) private var layoutDirection

    let label: String
    let helper: String
    let error: String
    let required: Bool
    let labels: [String]
    let values: [String]
    let optionEnabled: [Bool]
    let selectedValues: [String]
    let disabled: Bool
    let awaitingPublication: Bool
    let tokens: FirstlightPillGroupTokens
    let accessibilityLabel: String
    let accessibilityHint: String
    let onSelection: (Int) -> Void

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            if !label.isEmpty {
                HStack(spacing: 2) {
                    Text(label)
                        .fixedSize(horizontal: false, vertical: true)
                        .layoutPriority(1)
                    if required {
                        Text("*")
                            .accessibilityHidden(true)
                    }
                }
                .font(.subheadline.weight(.medium))
                .foregroundStyle(tokens.label)
            }

            PillFlowLayout(
                layoutDirection: layoutDirection,
                horizontalSpacing: 8,
                verticalSpacing: 8
            ) {
                ForEach(labels.indices, id: \.self) { index in
                    pill(index: index)
                }
            }
            .frame(maxWidth: .infinity, alignment: .leading)
            .allowsHitTesting(!awaitingPublication)
            .accessibilityElement(children: .contain)
            .accessibilityLabel(groupAccessibilityLabel)
            .accessibilityHint(accessibilityHint)

            if !helper.isEmpty {
                Text(helper)
                    .font(.footnote)
                    .foregroundStyle(tokens.helper)
                    .fixedSize(horizontal: false, vertical: true)
            }

            if !error.isEmpty {
                Text(error)
                    .font(.footnote)
                    .foregroundStyle(tokens.error)
                    .fixedSize(horizontal: false, vertical: true)
                    .accessibilityLabel("Error: \(error)")
            }
        }
        .opacity(disabled ? 0.6 : 1)
    }

    @ViewBuilder
    private func pill(index: Int) -> some View {
        let value = values.indices.contains(index) ? values[index] : ""
        let selected = selectedValues.contains(value)
        let enabled = !disabled
            && optionEnabled.indices.contains(index)
            && optionEnabled[index]

        Button {
            onSelection(index)
        } label: {
            HStack(spacing: 6) {
                if selected {
                    Image(systemName: "checkmark")
                        .font(.caption.weight(.bold))
                        .accessibilityHidden(true)
                }

                Text(labels[index])
                    .font(.subheadline.weight(.medium))
                    .multilineTextAlignment(.leading)
                    .fixedSize(horizontal: false, vertical: true)
            }
            .foregroundStyle(selected ? tokens.selectedForeground : tokens.unselectedForeground)
            .padding(.horizontal, 14)
            .padding(.vertical, 8)
            .frame(minHeight: 44)
            .background(
                Capsule().fill(
                    selected ? tokens.selectedBackground : tokens.unselectedBackground
                )
            )
            .overlay(
                Capsule().stroke(
                    selected ? tokens.selectedBackground : tokens.outline,
                    lineWidth: selected ? 2 : 1
                )
            )
            .clipShape(Capsule())
            .contentShape(Capsule())
        }
        .buttonStyle(.plain)
        .disabled(!enabled)
        .accessibilityLabel(labels[index])
        .accessibilityValue(selected ? "Selected" : "Not selected")
        .accessibilityAddTraits(selected ? .isSelected : [])
        .accessibilityHint(accessibilityHint)
    }

    private var groupAccessibilityLabel: String {
        let base = accessibilityLabel.isEmpty ? label : accessibilityLabel
        return required && !base.isEmpty ? "\(base), required" : base
    }
}
