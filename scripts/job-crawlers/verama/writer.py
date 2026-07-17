# FreeCRM — Verama job crawler
# @project FreeCRM
# @author bmankowski@gmail.com

from __future__ import annotations

import json
import logging
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

from .description import parse_description

logger = logging.getLogger(__name__)


def utc_now_iso() -> str:
    return datetime.now(timezone.utc).replace(microsecond=0).isoformat()


def job_filename(job_id: int | str) -> str:
    return f"verama_{job_id}.json"


class JobWriter:
    def __init__(self, pending_dir: Path, failed_dir: Path) -> None:
        self.pending_dir = pending_dir
        self.failed_dir = failed_dir
        self.pending_dir.mkdir(parents=True, exist_ok=True)
        self.failed_dir.mkdir(parents=True, exist_ok=True)

    def pending_path(self, job_id: int | str) -> Path:
        return self.pending_dir / job_filename(job_id)

    def read_pending(self, job_id: int | str) -> dict[str, Any] | None:
        path = self.pending_path(job_id)
        if not path.exists():
            return None
        try:
            data = json.loads(path.read_text(encoding="utf-8"))
        except (OSError, json.JSONDecodeError) as exc:
            logger.warning("Cannot read existing pending file %s: %s", path, exc)
            return None
        return data if isinstance(data, dict) else None

    def build_open_record(self, detail: dict[str, Any], scraped_at: str | None = None) -> dict[str, Any]:
        job_id = detail.get("id")
        if job_id is None:
            raise ValueError("Detail payload missing id")
        scraped = scraped_at or utc_now_iso()
        desc = parse_description(detail.get("description"))
        status = detail.get("status") or "OPEN"
        return {
            "source": "verama",
            "external_id": str(job_id),
            "system_id": detail.get("systemId"),
            "url": f"https://app.verama.com/app/job-requests/{job_id}",
            "scraped_at": scraped,
            "status": status,
            "closed_detected_at": None,
            "description_html": desc["description_html"],
            "description_text": desc["description_text"],
            "description_sections": desc["description_sections"],
            "api": detail,
        }

    def write_pending(self, record: dict[str, Any]) -> Path:
        job_id = record.get("external_id")
        if not job_id:
            raise ValueError("Record missing external_id")
        path = self.pending_path(job_id)
        path.write_text(
            json.dumps(record, ensure_ascii=False, indent=2) + "\n",
            encoding="utf-8",
        )
        logger.info("Wrote %s", path)
        return path

    def write_failed(self, job_id: int | str, payload: dict[str, Any]) -> Path:
        path = self.failed_dir / job_filename(job_id)
        path.write_text(
            json.dumps(payload, ensure_ascii=False, indent=2) + "\n",
            encoding="utf-8",
        )
        logger.error("Wrote failed artifact %s", path)
        return path
