# FreeCRM — Verama job sync
# @project FreeCRM
# @author bmankowski@gmail.com

from __future__ import annotations

import argparse
import logging
import sys
from pathlib import Path

from .config import Config, ConfigError
from .sync import run_sync


def _setup_logging(log_path: Path) -> None:
    log_path.parent.mkdir(parents=True, exist_ok=True)
    root = logging.getLogger()
    root.setLevel(logging.INFO)
    root.handlers.clear()

    fmt = logging.Formatter(
        "%(asctime)s %(levelname)s [%(name)s] %(message)s",
        datefmt="%Y-%m-%dT%H:%M:%S",
    )

    stream = logging.StreamHandler(sys.stdout)
    stream.setFormatter(fmt)
    root.addHandler(stream)

    file_handler = logging.FileHandler(log_path, encoding="utf-8")
    file_handler.setFormatter(fmt)
    root.addHandler(file_handler)


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(
        description="Sync Verama PL job requests into import/jobs JSON files"
    )
    parser.add_argument(
        "--output-dir",
        help="Override VERAMA_OUTPUT_DIR",
    )
    args = parser.parse_args(argv)

    try:
        config = Config.from_env()
    except ConfigError as exc:
        print(f"Config error: {exc}", file=sys.stderr)
        return 2

    if args.output_dir:
        object.__setattr__(
            config,
            "output_dir",
            Path(args.output_dir),
        )

    _setup_logging(config.log_path)
    logging.getLogger(__name__).info(
        "Starting Verama job sync → %s", config.output_dir
    )

    try:
        report = run_sync(config)
    except Exception:
        return 1

    if report.failed or report.errors:
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
