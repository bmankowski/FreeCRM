# ImportManager Rollout Checklist

## Pre-deploy
- [ ] Verify DB migrations for `import_batches`, `import_mappings`, `import_logs` executed.
- [ ] Confirm `config/modules/ImportManager.php` thresholds set appropriately (chunkSize, queue thresholds).
- [ ] Ensure `vtiger_import_queue` table exists (legacy installs may need migration).

## Deployment
- [ ] Deploy code changes (controllers, services, front-end assets).
- [ ] Clear cache/templates if needed (`cache/templates_c`, `cache/runtime`).
- [ ] Configure cron:  
  - `php cron/modules/ImportManager/Import.php` (recommended interval 5–10 min).  
  - Keep existing `vtigercron.php` schedule.

## Post-deploy validation
- [ ] Run a small import (inline staging) and verify preview + mapping + staging success.  
- [ ] Run a large import to trigger queue thresholds; confirm job created in `vtiger_import_queue` and worker processes it.
- [ ] Open Retry view to confirm failed rows show inline edit/CSV export.
- [ ] Inspect `cache/logs/system.log` for warnings/errors.
- [ ] (Optional) Capture staging tables to ensure naming matches `import_stage_{module}_{batchId}`.

## Rollback plan
- Remove cron entry for ImportManager worker.
- Re-enable legacy Import if needed (ensure mapping/staging tables cleaned up).
- Restore previous code snapshot.

