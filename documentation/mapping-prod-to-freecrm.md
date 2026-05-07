# Mapping prod (10.0.0.220) DB to local FreeCRM (localhost)

## Can I copy the whole production database “as-is” to localhost?

**Yes, but only if you also align schema + runtime expectations.** “As-is” means you restore the production dump (schema + data) into localhost. That is safe for *data fidelity*, but it can break the FreeCRM code if **the code expects columns/tables that do not exist in production schema** (or vice‑versa).

In practice, there are two viable paths:

- **Option A (recommended)**: keep **localhost schema** (whatever your FreeCRM codebase expects) and import **production data** using `INSERT (...) VALUES (...)` with explicit column lists (like we already do with `--complete-insert`). This tolerates “prod has fewer/more columns” much better.
- **Option B (“as-is” DB)**: restore production **schema+data**, then run whatever **FreeCRM/YetiForce updater/migrations** are required to bring the schema up to the codebase version. If such migrations are missing/incomplete, you’ll hit runtime SQL errors.

## What we already know from this repo’s recent sync work

### Database engine/version mismatch
- **Production**: MariaDB **10.3.39**, `collation_server=utf8mb4_general_ci`
- **Local Docker**: MariaDB **11.6.2**, `collation_server=utf8mb4_uca1400_ai_ci`

**Impact**: this can affect SQL behavior, defaults, collations/sorting, and (rarely) query plans. It’s usually workable, but don’t assume perfect parity.

### Concrete schema drift found (example tables)
We observed **both directions** of drift:

- `vtiger_crmentity`
  - **Local has extra column**: `closedtime`
  - **Prod does NOT have it**
  - **Meaning**: if you restore prod “as-is” onto localhost, any local code/query that references `vtiger_crmentity.closedtime` can break until you add/migrate it.

- `vtiger_modcomments`
  - **Prod has extra column**: `parents`
  - **Local did NOT have it initially**
  - **Meaning**: if you import prod rows into local schema without that column, imports can fail (we fixed this by adding `parents` locally).

**Rule of thumb**:
- **Extra columns in DB** are usually harmless (application ignores them).
- **Missing columns required by application code or imports** cause hard failures.

## “As-is” migration risk checklist (very concise)

### High risk (likely to break something)
- **Schema version mismatch** between prod DB and your FreeCRM codebase (missing columns/tables/indexes).
- **Different DB major versions** (10.3 → 11.6) changing SQL modes/collation defaults.
- **Triggers/procedures/events** (if any) not included in dump/restore.
- **File storage** not migrated together with DB (`storage/` must match DB references).

### Medium risk (usually fixable)
- **Collation differences** causing slightly different ordering/search.
- **Users/roles/session** differences if you rely on local accounts.

### Low risk
- **Additional custom fields** (extra columns, extra `u_yf_*cf` columns): typically safe.

## Recommendation for FreeCRM dev/test localhost

If your goal is “local UI works and data is accurate”, prefer:

**Import prod data into the local schema (Option A)**, because it is robust to “prod/local have different columns”, and lets your local DB stay compatible with your current FreeCRM codebase.

If you truly need **prod schema** replicated (Option B), do it only if you also have a reliable **schema migration path** to reconcile prod → your current FreeCRM version.

