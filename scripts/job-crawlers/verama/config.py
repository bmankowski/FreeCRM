# FreeCRM — Verama job crawler
# @project FreeCRM
# @author bmankowski@gmail.com

from __future__ import annotations

import os
from dataclasses import dataclass
from pathlib import Path


class ConfigError(ValueError):
    pass


@dataclass(frozen=True)
class Config:
    email: str
    password: str
    base_url: str
    output_dir: Path
    request_delay_sec: float
    list_page_size: int
    log_path: Path

    @property
    def pending_dir(self) -> Path:
        return self.output_dir / "pending"

    @property
    def failed_dir(self) -> Path:
        return self.output_dir / "failed"

    @property
    def seen_path(self) -> Path:
        return self.output_dir / ".verama_seen.json"

    @classmethod
    def from_env(cls) -> Config:
        email = os.environ.get("VERAMA_EMAIL", "").strip()
        password = os.environ.get("VERAMA_PASSWORD", "")
        if not email or not password:
            raise ConfigError(
                "VERAMA_EMAIL and VERAMA_PASSWORD must be set "
                "(see docker/verama-crawler/verama.env.example)"
            )

        base_url = os.environ.get("VERAMA_BASE_URL", "https://app.verama.com").rstrip("/")
        output_dir = Path(
            os.environ.get("VERAMA_OUTPUT_DIR", "/var/www/import/jobs")
        )
        delay = float(os.environ.get("VERAMA_REQUEST_DELAY_SEC", "2"))
        page_size = int(os.environ.get("VERAMA_LIST_PAGE_SIZE", "100"))
        log_path = Path(
            os.environ.get(
                "VERAMA_LOG_PATH",
                "/var/www/html/cache/logs/verama-crawler.log",
            )
        )
        return cls(
            email=email,
            password=password,
            base_url=base_url,
            output_dir=output_dir,
            request_delay_sec=delay,
            list_page_size=page_size,
            log_path=log_path,
        )
