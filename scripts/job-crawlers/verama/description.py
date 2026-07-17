# FreeCRM — Verama job crawler
# @project FreeCRM
# @author bmankowski@gmail.com

from __future__ import annotations

import logging
import re
from typing import Any

from bs4 import BeautifulSoup, NavigableString, Tag

logger = logging.getLogger(__name__)

SECTION_ALIASES: dict[str, tuple[str, ...]] = {
    "about": (
        "about",
        "about us",
        "about the role",
        "about the assignment",
        "company",
        "overview",
        "introduction",
        "o nas",
        "o firmie",
        "o projekcie",
    ),
    "responsibilities": (
        "responsibilities",
        "your responsibilities",
        "tasks",
        "duties",
        "role",
        "the role",
        "what you will do",
        "scope",
        "assignment",
        "zakres",
        "obowiązki",
        "zadania",
    ),
    "requirements": (
        "requirements",
        "must have",
        "must-have",
        "required",
        "skills",
        "competence",
        "competences",
        "qualifications",
        "experience",
        "wymagania",
        "kwalifikacje",
        "umiejętności",
    ),
    "offer": (
        "we offer",
        "offer",
        "benefits",
        "what we offer",
        "nice to have",
        "nice-to-have",
        "preferred",
        "oferujemy",
        "benefity",
    ),
}

_HEADING_TAGS = frozenset({"h1", "h2", "h3", "h4", "h5", "h6"})


def _normalize_heading(text: str) -> str:
    text = re.sub(r"\s+", " ", text).strip().lower()
    text = re.sub(r"[:\-–—]+\s*$", "", text).strip()
    return text


def _map_section_key(heading: str) -> str:
    normalized = _normalize_heading(heading)
    if not normalized:
        return "other"
    for key, aliases in SECTION_ALIASES.items():
        for alias in aliases:
            if normalized == alias or normalized.startswith(alias + " "):
                return key
    slug = re.sub(r"[^a-z0-9]+", "_", normalized).strip("_")
    return slug or "other"


def _is_heading(node: Tag) -> bool:
    if node.name in _HEADING_TAGS:
        return True
    if node.name in {"p", "div", "span", "strong", "b"}:
        text = node.get_text(" ", strip=True)
        if not text or len(text) > 120:
            return False
        # Standalone bold line often used as section title
        if node.name in {"strong", "b"}:
            return True
        strong_only = list(node.find_all(["strong", "b"], recursive=False))
        if strong_only and _normalize_heading(strong_only[0].get_text()) == _normalize_heading(
            text
        ):
            return True
    return False


def parse_description(html: str | None) -> dict[str, Any]:
    """Split Verama job description HTML into text + best-effort sections."""
    if not html or not str(html).strip():
        return {
            "description_html": "",
            "description_text": "",
            "description_sections": {},
        }

    soup = BeautifulSoup(html, "lxml")
    body = soup.body if soup.body else soup

    plain = body.get_text("\n", strip=True)
    plain = re.sub(r"\n{3,}", "\n\n", plain).strip()

    sections: dict[str, list[str]] = {}
    current_key = "about"
    sections.setdefault(current_key, [])
    found_heading = False

    for child in list(body.children):
        if isinstance(child, NavigableString):
            text = str(child).strip()
            if text:
                sections.setdefault(current_key, []).append(text)
            continue
        if not isinstance(child, Tag):
            continue
        if child.name in {"script", "style"}:
            continue

        if _is_heading(child):
            heading_text = child.get_text(" ", strip=True)
            if heading_text:
                found_heading = True
                current_key = _map_section_key(heading_text)
                sections.setdefault(current_key, [])
                continue

        chunk = child.get_text("\n", strip=True)
        if chunk:
            sections.setdefault(current_key, []).append(chunk)

    # If nothing looked like a heading, keep a single "other" bucket with full text
    if not found_heading:
        sections = {"other": [plain]} if plain else {}
        logger.warning("Description has no detectable section headings")

    merged: dict[str, str] = {}
    for key, parts in sections.items():
        text = "\n\n".join(p for p in parts if p).strip()
        if text:
            if key in merged:
                merged[key] = f"{merged[key]}\n\n{text}".strip()
            else:
                merged[key] = text

    if found_heading and not merged:
        logger.warning("Description headings found but sections empty after parse")

    return {
        "description_html": html,
        "description_text": plain,
        "description_sections": merged,
    }
