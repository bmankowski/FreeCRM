# FreeCRM — Verama job crawler
# @project FreeCRM
# @author bmankowski@gmail.com

from __future__ import annotations

import json
import logging
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

logger = logging.getLogger(__name__)


def _utc_now_iso() -> str:
    return datetime.now(timezone.utc).replace(microsecond=0).isoformat()


def load_seen(path: Path) -> set[str]:
    if not path.exists():
        return set()
    try:
        raw = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as exc:
        raise RuntimeError(f"Cannot read seen state {path}: {exc}") from exc

    if isinstance(raw, dict) and "ids" in raw:
        ids = raw["ids"]
    elif isinstance(raw, list):
        ids = raw
    else:
        raise RuntimeError(f"Unexpected seen state format in {path}")

    return {str(i) for i in ids}


def save_seen(path: Path, ids: set[str]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    payload = {
        "updated_at": _utc_now_iso(),
        "ids": sorted(ids, key=lambda x: int(x) if x.isdigit() else x),
    }
    path.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    logger.info("Wrote seen state (%s ids) to %s", len(ids), path)


def closed_ids(previous: set[str], current: set[str]) -> set[str]:
    """Ids present previously but missing now. Empty previous => first run, no CLOSED."""
    if not previous:
        return set()
    return previous - current


def build_closed_record(
    job_id: str,
    existing: dict[str, Any] | None,
    scraped_at: str | None = None,
) -> dict[str, Any]:
    now = scraped_at or _utc_now_iso()
    if existing:
        record = dict(existing)
        record["status"] = "CLOSED"
        record["closed_detected_at"] = now
        record["scraped_at"] = now
        return record

    return {
        "source": "verama",
        "external_id": str(job_id),
        "system_id": None,
        "url": f"https://app.verama.com/app/job-requests/{job_id}",
        "scraped_at": now,
        "status": "CLOSED",
        "closed_detected_at": now,
        "description_html": "",
        "description_text": "",
        "description_sections": {},
        "api": {},
    }
