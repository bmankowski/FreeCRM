# FreeCRM — Verama job sync
# @project FreeCRM
# @author bmankowski@gmail.com

from __future__ import annotations

from typing import Any, Iterable


def is_poland(item: dict[str, Any]) -> bool:
    return str(item.get("countryCode") or "").upper() == "POL"


def filter_poland(items: Iterable[dict[str, Any]]) -> list[dict[str, Any]]:
    return [item for item in items if is_poland(item)]
