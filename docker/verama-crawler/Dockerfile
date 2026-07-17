# FreeCRM — Verama job crawler image
# @project FreeCRM
# @author bmankowski@gmail.com

FROM python:3.12-slim

WORKDIR /opt/crawlers

COPY scripts/job-crawlers/verama/requirements.txt /tmp/requirements.txt
RUN pip install --no-cache-dir -r /tmp/requirements.txt

COPY scripts/job-crawlers /opt/crawlers

ENV PYTHONUNBUFFERED=1 \
    PYTHONPATH=/opt/crawlers \
    VERAMA_OUTPUT_DIR=/var/www/import/jobs \
    VERAMA_LOG_PATH=/var/www/html/cache/logs/verama-crawler.log

WORKDIR /opt/crawlers
CMD ["python", "-m", "verama"]
