# Delayed email buffer — operator runbook

**See also:** [mvp-email-buffer.md](../../mvp-email-buffer.md), [freecrm-mail-system-architecture.md](../freecrm-mail-system-architecture.md#delayed-emails-buffer-and-audit)

## What it does

Workflow-triggered emails (e.g. recruitment status change) can be held in `s_yf_delayed_email_queue` for **120 minutes** (default). Rapid status changes supersede the pending row. When the delay expires, cron promotes the row into `s_yf_mail_queue`; the existing `MailerTask` sends it. Every SMTP attempt is logged in `s_yf_mail_sent_log` when audit is enabled.

## Admin UI

**Settings → Opóźnione e-maile** (`Settings:DelayedEmails`)

- Lists pending rows sorted by `send_after`.
- **Cancel** — deletes the row (no email will be sent).
- **Send now** — sets `send_after = NOW()`; `LBL_DELAYED_EMAIL_QUEUE` cron promotes within ~60 s.

## Config flags (`config/modules/Mail.php`)

| Key | Default | Meaning |
|-----|---------|---------|
| `MAIL_AUDIT_LOG_ENABLED` | `false` | Log each send/fail to `s_yf_mail_sent_log` |
| `AUDIT_LOG_RETENTION_DAYS` | `365` | Cleanup window for audit rows |
| `DELAYED_EMAIL_BUFFER_ENABLED` | `false` | Use buffer; `false` = immediate `addMail()` |
| `DELAYED_EMAIL_DEFAULT_MINUTES` | `120` | Default delay for new enqueues |

## Activation (production)

1. Deploy code + schema.
2. Enable audit (optional): `MAIL_AUDIT_LOG_ENABLED=true`, cron `LBL_CLEANUP_MAIL_AUDIT_LOG` → `status=1`.
3. Enable buffer: `DELAYED_EMAIL_BUFFER_ENABLED=true`, cron `LBL_DELAYED_EMAIL_QUEUE` → `status=1`.

## Rollback

1. Set `DELAYED_EMAIL_BUFFER_ENABLED=false` — new emails send immediately.
2. Optionally `UPDATE vtiger_cron_task SET status=0 WHERE name='LBL_DELAYED_EMAIL_QUEUE';`
3. Clear pending: Settings UI or `DELETE FROM s_yf_delayed_email_queue;`

## Diagnostics SQL

```sql
-- Pending buffer size
SELECT COUNT(*) FROM s_yf_delayed_email_queue;

-- Pending by type
SELECT type, COUNT(*) FROM s_yf_delayed_email_queue GROUP BY type;

-- Overdue (cron lag)
SELECT COUNT(*) FROM s_yf_delayed_email_queue WHERE send_after < NOW() - INTERVAL 5 MINUTE;

-- Sends in last hour
SELECT COUNT(*) FROM s_yf_mail_sent_log WHERE attempted_at > NOW() - INTERVAL 1 HOUR;

-- Sends from buffer in last hour
SELECT COUNT(*) FROM s_yf_mail_sent_log
 WHERE source_module='DelayedBuffer' AND attempted_at > NOW() - INTERVAL 1 HOUR;

-- Recent failures
SELECT id, subject, error, attempted_at FROM s_yf_mail_sent_log
 WHERE status=2 ORDER BY attempted_at DESC LIMIT 20;
```

## Cron tasks

| Name | Class | Frequency |
|------|-------|-----------|
| `LBL_DELAYED_EMAIL_QUEUE` | `DelayedEmailQueueTask` | 60 s |
| `LBL_CLEANUP_MAIL_AUDIT_LOG` | `CleanupMailAuditLogTask` | 86400 s |

Verify in **Settings → Scheduler** that `laststart`/`lastend` update when `status=1`.
