% ImportManager – User Guide

## Overview
ImportManager replaces the legacy Import module with a three-step wizard: upload → mapping → validation/staging. The goal is to validate data before writing anything to the target module, provide clear feedback, and allow inline corrections.

## Prerequisites
- Permission `ImportData` on the target module.
- CSV, XML, or ZIP (single CSV/XML inside); size limits read from `config/modules/ImportManager.php`.
- Correct field mappings and knowledge of which fields are mandatory in the module.

## Workflow
1. **Upload & Preview**  
   - Select module, format, encoding, and optional CSV delimiter/XML XPath.  
   - Upload file and generate preview (first N rows).  
   - The wizard persists uploaded file under `storage/imports/{batchId}/`.

2. **Map Fields & Defaults**  
   - Each module field is listed once; pick the source column and optionally declare default values.  
   - Mandatory fields and required duplicate keys are highlighted.  
   - Save mapping to store configuration in `import_mappings`.

3. **Prepare Data (Staging)**  
   - After saving the mapping choose one of the buttons: **Run immediately** or **Add to queue** for staging.  
   - Immediate mode executes staging in the current HTTP request; queue mode always stores a job in `vtiger_import_queue` for `cron/modules/ImportManager/Import.php`.  
   - Staging results show total vs failed rows and unlock the import section (only rows with status `ok` will be processed).  
   - If failures exist, an optional Step 4 is shown with a link to “Popraw błędne rekordy”.

4. **Import**  
   - When staging finishes (and at least one row is valid) the wizard displays a second set of buttons: **Import now** or **Queue import**.  
   - Inline import is recommended for tiny batches; larger jobs should be queued so cron can process them.  
   - The summary lists how many records were created, updated, skipped or failed during the final write.

5. **Fix Failed Rows (Optional)**  
   - The retry grid (`view=Retry`) lists rows with `validation_status = failed` from `import_stage_{module}_{batchId}`.  
   - Edit offending values inline or export them to CSV.  
   - Saving marks the rows as `pending` with a new `retry_token`, ready for another staging/import attempt.

## Handling Errors
- **Mandatory fields missing** → fill in the column or default value.  
- **Duplicate key missing** → ensure all fields defined as required duplicates have values.  
- **Handler queued** → wait for cron to process job; monitor `cache/logs/system.log`.

## Log & Monitoring Checklist
- After staging/import, tail `cache/logs/system.log` for PHP or DB errors.  
- For queued tasks, verify `vtiger_import_queue.temp_status` transitions `1 → 2 → 4`.  
- Check `import_batches` for updated `processed_rows`, `error_rows`, and timestamps.  
- Inspect `storage/imports/{batchId}/log.json` (future Etap 3) once implemented.

## Cron Configuration
- Add an entry for `php cron/modules/ImportManager/Import.php` to process queued jobs.  
- Ensure existing legacy `vtigercron.php` schedule does not conflict; ImportManager cron only handles new queue entries.

## Troubleshooting
| Symptom | Resolution |
| --- | --- |
| “Handler nie został odnaleziony” | Ensure `Stage.php` action file is present and autoloadable, and that cron processed queued job. |
| CSV shows only headers | Verify staging table still exists; rerun staging if cleanup removed it. |
| Retry grid empty | No failed rows or staging table removed. Re-run staging after correcting source file. |

