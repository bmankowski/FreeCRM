#!/usr/bin/env python3
"""
FreeCRM - Customer Relationship Management System

@project FreeCRM
@author bmankowski@gmail.com
@copyright (c) FreeCRM

Recalculate glyph bounding boxes (xMin/yMin/xMax/yMax in the `glyf` table) for the
icon fonts shipped in `public/layouts/basic/skins/icons/`.

The original WOFF2/WOFF/TTF files were generated with a buggy tool which left
many glyph bboxes inconsistent with the actual contours, so Firefox logs
`downloadable font: glyf: Glyph bbox was incorrect; adjusting (glyph N) ...`
on every page that uses the fonts.

Usage:
    python3 scripts/fix-icon-fonts.py

Requires `fontTools` (https://github.com/fonttools/fonttools).
"""
from __future__ import annotations

import sys
from pathlib import Path

try:
    from fontTools.ttLib import TTFont
except ImportError:
    sys.stderr.write(
        "fontTools not installed. Install with: pip install --user fonttools brotli\n"
    )
    sys.exit(1)

REPO_ROOT = Path(__file__).resolve().parent.parent
ICONS_DIR = REPO_ROOT / "public" / "layouts" / "basic" / "skins" / "icons"

FONT_FAMILIES = ("additionalIcons", "adminIcons", "userIcons")
FONT_EXTENSIONS = ("woff2", "woff", "ttf")


def fix_font(path: Path) -> tuple[int, int]:
    """Recalculate bboxes for all glyphs in `path`. Returns (total, fixed)."""
    font = TTFont(str(path), recalcBBoxes=True, recalcTimestamp=False)
    if "glyf" not in font:
        return (0, 0)

    glyf = font["glyf"]
    total = 0
    fixed = 0
    for name in font.getGlyphOrder():
        glyph = glyf[name]
        if glyph.numberOfContours == 0:
            continue
        total += 1
        before = (glyph.xMin, glyph.yMin, glyph.xMax, glyph.yMax)
        glyph.recalcBounds(glyf)
        after = (glyph.xMin, glyph.yMin, glyph.xMax, glyph.yMax)
        if before != after:
            fixed += 1

    font.save(str(path), reorderTables=False)
    return (total, fixed)


def main() -> int:
    if not ICONS_DIR.is_dir():
        sys.stderr.write(f"Icons directory not found: {ICONS_DIR}\n")
        return 1

    total_fixed = 0
    for family in FONT_FAMILIES:
        for ext in FONT_EXTENSIONS:
            path = ICONS_DIR / f"{family}.{ext}"
            if not path.exists():
                print(f"  skip (missing): {path.name}")
                continue
            total, fixed = fix_font(path)
            total_fixed += fixed
            print(f"  {path.name}: {total} glyphs, {fixed} bbox(es) recalculated")

    print(f"\nDone. Recalculated {total_fixed} glyph bbox(es) in total.")
    print("Bump the cache-busting query string in the *.css files if browsers "
          "still serve the cached fonts.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
