#!/usr/bin/env python3
"""
FreeCRM - Customer Relationship Management System

@project FreeCRM
@author bmankowski@gmail.com
@copyright (c) FreeCRM

Remove legacy IE/Old-Firefox CSS from public/layouts/basic/skins/style.css that
modern browsers reject (progid filters, alpha(), * hacks, -moz-box-shadow dupes).

Usage:
    python3 scripts/clean-style-css-legacy.py
"""
from __future__ import annotations

import re
import sys
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent
STYLE_CSS = REPO_ROOT / "public" / "layouts" / "basic" / "skins" / "style.css"

DROP_LINE = re.compile(
    r"^\s*(?:"
    r"\*zoom\s*:.*|"
    r"\*margin-right\s*:.*|"
    r"filter\s*:\s*progid:.*|"
    r"-ms-filter\s*:\s*\"progid:.*|"
    r"filter\s*:\s*alpha\(.*|"
    r"-moz-box-shadow\s*:.*|"
    r"-webkit-border-radius\s*:\s*2\s*;|"
    r"-moz-border-radius\s*:\s*2\s*;"
    r")\s*$",
    re.IGNORECASE,
)

DROP_COMMENT_IE = re.compile(r"^\s*/\*\s*for\s+IE\s*\*/\s*$", re.IGNORECASE)
I_BLOCK_CHROME = re.compile(r"::i-block-chrome,\s*", re.IGNORECASE)

OLD_PLACEHOLDERS = """
.tagText::-webkit-input-placeholder { 
\tcolor:#b1e5f4; 
}
.tagText::-moz-placeholder { 
\tcolor:#b1e5f4; 
}
.tagText::-ms-input-placeholder { 
\tcolor:#b1e5f4; 
}""".lstrip("\n")

NEW_PLACEHOLDER = """.tagText::placeholder {
\tcolor:#b1e5f4;
}"""


def clean_line(line: str) -> str | None:
    """Return cleaned line, or None to drop the line."""
    if DROP_LINE.match(line) or DROP_COMMENT_IE.match(line):
        return None
    line = I_BLOCK_CHROME.sub("", line)
    line = line.replace("nowrap: nowrap;", "white-space: nowrap;")
    line = line.replace("box-shadow: 0;", "box-shadow: none;")
    return line


def clean_content(text: str) -> tuple[str, dict[str, int]]:
    stats = {"lines_removed": 0, "placeholder_block": 0}
    out_lines: list[str] = []
    for line in text.splitlines():
        cleaned = clean_line(line)
        if cleaned is None:
            stats["lines_removed"] += 1
            continue
        out_lines.append(cleaned)

    result = "\n".join(out_lines) + "\n"
    if OLD_PLACEHOLDERS in result:
        result = result.replace(OLD_PLACEHOLDERS, NEW_PLACEHOLDER + "\n")
        stats["placeholder_block"] = 1
    return result, stats


def main() -> int:
    if not STYLE_CSS.is_file():
        sys.stderr.write(f"Not found: {STYLE_CSS}\n")
        return 1

    original = STYLE_CSS.read_text(encoding="utf-8")
    cleaned, stats = clean_content(original)
    if cleaned == original:
        print("No changes needed.")
        return 0

    STYLE_CSS.write_text(cleaned, encoding="utf-8")
    print(f"Updated {STYLE_CSS.relative_to(REPO_ROOT)}")
    print(f"  lines removed: {stats['lines_removed']}")
    if stats["placeholder_block"]:
        print("  placeholder rules: merged to ::placeholder")
    print("Regenerate minified CSS: npm run minify-css -- public/layouts/basic/skins/style.css")
    return 0


if __name__ == "__main__":
    sys.exit(main())
