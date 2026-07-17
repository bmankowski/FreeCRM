# FreeCRM — Verama job crawler
# @project FreeCRM
# @author bmankowski@gmail.com

from __future__ import annotations

import logging
import time
from typing import Any

from .closed import build_closed_record, closed_ids, load_seen, save_seen
from .client import VeramaClient
from .config import Config
from .filter_pl import filter_poland
from .report import CrawlReport
from .writer import JobWriter, utc_now_iso

logger = logging.getLogger(__name__)


def run_crawl(config: Config) -> CrawlReport:
    report = CrawlReport()
    started = time.monotonic()
    writer = JobWriter(config.pending_dir, config.failed_dir)
    client = VeramaClient(
        base_url=config.base_url,
        email=config.email,
        password=config.password,
        request_delay_sec=config.request_delay_sec,
        list_page_size=config.list_page_size,
    )

    try:
        client.authenticate()
        pl_items = _collect_poland_list(client, report)
        current_ids = {str(item["id"]) for item in pl_items if item.get("id") is not None}
        report.listed_pl = len(current_ids)

        previous = load_seen(config.seen_path)
        to_close = closed_ids(previous, current_ids)

        for item in pl_items:
            job_id = item.get("id")
            if job_id is None:
                raise RuntimeError("List item missing id")
            try:
                detail = client.fetch_job_detail(job_id)
                record = writer.build_open_record(detail)
                writer.write_pending(record)
                report.written += 1
            except Exception as detail_exc:
                writer.write_failed(
                    job_id,
                    {
                        "external_id": str(job_id),
                        "error": str(detail_exc),
                        "list_item": item,
                        "scraped_at": utc_now_iso(),
                    },
                )
                raise

        scraped_at = utc_now_iso()
        for job_id in sorted(to_close, key=lambda x: int(x) if x.isdigit() else x):
            existing = writer.read_pending(job_id)
            record = build_closed_record(job_id, existing, scraped_at=scraped_at)
            writer.write_pending(record)
            report.closed += 1
            logger.info("Marked CLOSED: %s", job_id)

        save_seen(config.seen_path, current_ids)
    except Exception as exc:
        report.failed += 1
        report.errors.append(str(exc))
        logger.exception("Crawl aborted: %s", exc)
        raise
    finally:
        report.duration_sec = time.monotonic() - started
        report.log_summary()

    return report


def _collect_poland_list(client: VeramaClient, report: CrawlReport) -> list[dict[str, Any]]:
    pl_items: list[dict[str, Any]] = []
    seen_ids: set[str] = set()
    for page_items in client.iter_job_list_pages():
        report.listed_total += len(page_items)
        for item in filter_poland(page_items):
            job_id = item.get("id")
            if job_id is None:
                continue
            key = str(job_id)
            if key in seen_ids:
                continue
            seen_ids.add(key)
            pl_items.append(item)
    logger.info(
        "List complete: total=%s poland=%s",
        report.listed_total,
        len(pl_items),
    )
    return pl_items
