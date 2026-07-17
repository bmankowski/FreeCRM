# FreeCRM — Verama job sync image
# @project FreeCRM
# @author bmankowski@gmail.com

FROM python:3.12-slim

WORKDIR /opt

COPY scripts/verama_job_sync/requirements.txt /tmp/requirements.txt
RUN pip install --no-cache-dir -r /tmp/requirements.txt

COPY scripts/verama_job_sync /opt/verama_job_sync

ENV PYTHONUNBUFFERED=1 \
    PYTHONPATH=/opt \
    VERAMA_OUTPUT_DIR=/var/www/import/jobs \
    VERAMA_LOG_PATH=/var/www/html/cache/logs/verama-job-sync.log

WORKDIR /opt
CMD ["python", "-m", "verama_job_sync"]
