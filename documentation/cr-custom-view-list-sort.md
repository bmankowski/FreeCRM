# Change Request: Custom View Default List Sort

## Goal

Make **per-filter default sort** work reliably on list views: each `vtiger_customview` row can define `{column},{ASC|DESC}`, applied when a filter is first opened or switched, while user-initiated column clicks continue to override until the filter changes again.

Primary use case: **Candidates** list — default "All" filter sorted by date added (`createdtime`) descending; other filters configurable when **creating or editing** a filter (CustomView editor) or via **Settings → CustomView** list (edit filter).

---

## Stance

- **One format only:** `vtiger_customview.sort` = `{column},{ASC|DESC}` (e.g. `createdtime,DESC`). No JSON, no runtime compat shim.
- **Migrate** existing JSON rows in DB; centralized `parseSortValue()` replaces raw `explode`.
- **Platform fix** in CustomView/ListView — not Candidates-only.
- Module-level `default_order_by` in `src/Modules/Candidates/Candidates.php` stays empty.

---

## Impact

### Code modified

| Path | Change |
|------|--------|
| `migrations/Users/m260616_000003_customview_sort_comma_format.php` | JSON → comma format |
| `migrations/Users/m260616_000004_candidates_all_sort_createdtime.php` | Candidates All seed fix |
| `src/Modules/CustomView/Models/Record.php` | `parseSortValue()`, refactor `getSortOrderBy()` |
| `src/View/CustomView.php` | `resolveListSort()` |
| `src/Modules/Base/Views/ListView.php` | Use resolver; simplify AJAX `process()` |
| `src/Modules/Base/Models/MiniList.php` | Use `parseSortValue()` |
| `src/Modules/CustomView/Actions/Save.php` | Validate and persist sort on create/edit |
| `layouts/basic/modules/CustomView/EditView.tpl` | Sort field + direction UI |
| `public/layouts/basic/modules/CustomView/resources/CustomView.js` | Sort control handlers |
| `languages/en_us/CustomView.json`, `languages/pl_pl/CustomView.json` | Sort labels |
| `tests/customview_sort_smoke.php` | Automated smoke test (`parseSortValue`, `formatSortValue`) |

**Removed (Settings sort modal):** `Sorting.tpl`, `Views/Sorting.php`, `Sorting.js`, `getSortingFilterUrl()`, `updateOrderAndSort()`.

### Database

- `vtiger_customview.sort` — cvids 399, 353, 354 converted to comma format; Candidates All seeded `createdtime,DESC`.
- No schema `ALTER`.

---

## Functional requirements

| ID | Before → After |
|----|----------------|
| F1 | Candidates list first visit → **newest first by `createdtime`** |
| F2 | Filter switch → applies that filter's configured sort |
| F3 | Column header click → overrides until filter changes |
| F4 | Filter create/edit save → sort persisted and reflected on next load / filter select |
| F5 | MiniList widgets → respect filter sort |
| F6 | Full-page `viewname=` URL → target filter sort when view changed |

---

## Rollback

- **Code:** revert commit.
- **Data:** `UPDATE vtiger_customview SET sort = '...'` on cvids 399/353/354, or restore backup.

---

## Testing

```bash
docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
docker compose exec -T app php tests/customview_sort_smoke.php
rm -f cache/templates_c/*.php
```

Migrations: `m260616_000003` (comma format) + `m260616_000004` (Candidates All `createdtime,DESC` seed; fixes 000003 apply order on already-migrated DBs).

Manual: Candidates list (incognito), filter switch, column sort, create/edit filter with sort field.

Watch: `cache/logs/system.log` for `[ListView] Incorrect value of sorting`.
