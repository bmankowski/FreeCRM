# FreeCRM — Verama job crawler
# @project FreeCRM
# @author bmankowski@gmail.com

from __future__ import annotations

import logging
from dataclasses import dataclass, field

logger = logging.getLogger(__name__)


@dataclass
class CrawlReport:
    listed_total: int = 0
    listed_pl: int = 0
    written: int = 0
    closed: int = 0
    failed: int = 0
    duration_sec: float = 0.0
    errors: list[str] = field(default_factory=list)

    def log_summary(self) -> None:
        logger.info(
            "Report: listed_total=%s listed_pl=%s written=%s closed=%s failed=%s "
            "duration_sec=%.1f",
            self.listed_total,
            self.listed_pl,
            self.written,
            self.closed,
            self.failed,
            self.duration_sec,
        )
        for err in self.errors:
            logger.error("Report error: %s", err)

    def as_dict(self) -> dict:
        return {
            "listed_total": self.listed_total,
            "listed_pl": self.listed_pl,
            "written": self.written,
            "closed": self.closed,
            "failed": self.failed,
            "duration_sec": round(self.duration_sec, 2),
            "errors": list(self.errors),
        }
