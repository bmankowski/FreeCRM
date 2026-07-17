# FreeCRM — Verama job sync
# @project FreeCRM
# @author bmankowski@gmail.com

from __future__ import annotations

import unittest

from verama_job_sync.closed import build_closed_record, closed_ids


class ClosedDetectionTests(unittest.TestCase):
    def test_first_run_no_closed(self) -> None:
        self.assertEqual(closed_ids(set(), {"1", "2"}), set())

    def test_missing_marked(self) -> None:
        self.assertEqual(closed_ids({"1", "2", "3"}, {"1", "3"}), {"2"})

    def test_tombstone(self) -> None:
        record = build_closed_record("99", None, scraped_at="2026-07-17T00:00:00+00:00")
        self.assertEqual(record["status"], "CLOSED")
        self.assertEqual(record["external_id"], "99")
        self.assertEqual(record["closed_detected_at"], "2026-07-17T00:00:00+00:00")

    def test_update_existing(self) -> None:
        existing = {
            "source": "verama",
            "external_id": "5",
            "status": "OPEN",
            "api": {"id": 5, "title": "X"},
        }
        record = build_closed_record("5", existing, scraped_at="2026-07-17T01:00:00+00:00")
        self.assertEqual(record["status"], "CLOSED")
        self.assertEqual(record["api"]["title"], "X")


if __name__ == "__main__":
    unittest.main()
