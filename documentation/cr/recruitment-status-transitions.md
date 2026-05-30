# Change Request: Recruitment Status Transition Rules

## Goal

Allow admins to define which recruitment candidate status transitions are legal via **Settings › Recruitment**. The kanban enforces rules client-side (illegal drop → revert + toast); manual status changes enforce the same rules server-side. Workflows and automation remain unaffected.

---

## Stance

FreeCRM is being actively modernized. **No fallbacks. No compat shims. No parallel code paths.**

- Whitelist model: only checked transitions are allowed after configuration is saved.
- Single source of truth: PHP service → adjacency map for JS.
- Validation lives in `changeStatus()` for manual callers only; workflows use `updateRelationData()` directly and stay exempt.
- Screening Accept/Reject buttons stay on the Screening tab only; governed by the same matrix.

---

## Decisions (locked)

| Topic | Decision |
|---|---|
| Rule model | Whitelist |
| Configuration | Settings › **Recruitment** (broader hub for future recruitment config) |
| Initial state | **Option B** — UI shows suggested defaults; **nothing enforced until first Save** |
| Workflows | Exempt — out of scope for this CR |
| Illegal kanban drop | Silent revert + error toast |
| Accept/Reject buttons | Screening tab only; same matrix (`PPL_APPLIED → …`) |
| Status list | Reuse `RelationTrigger::getRecruitmentStatusOptions()` |

---

## Impact

### Code being modified

| File | Change |
|---|---|
| `src/Modules/ProjektyRekrutacyjne/Relations/GetRelatedMembers.php` | Validate transition in `changeStatus()` when rules active |
| `src/Modules/ProjektyRekrutacyjne/Actions/ChangeCandidateStatusManuallyAjax.php` | Return error on illegal transition |
| `src/Modules/ProjektyRekrutacyjne/Actions/AcceptCandidateManuallyAjax.php` | Propagate validation failure |
| `src/Modules/ProjektyRekrutacyjne/Actions/RejectCandidateManuallyAjax.php` | Propagate validation failure |
| `src/Modules/ProjektyRekrutacyjne/Widgets/RecruitmentProjectKanban.php` | Pass adjacency map + configured flag to widget |
| `layouts/basic/modules/ProjektyRekrutacyjne/widgets/RecruitmentProjectKanban.tpl` | Embed transition config for JS |
| `public/modules/ProjektyRekrutacyjne/resources/Detail.js` | Drag UX: highlight valid targets, block illegal drops |
| `layouts/basic/modules/ProjektyRekrutacyjne/RelatedList.tpl` | Accept/Reject only when relation label is `Screening` |
| `languages/en_us/Settings/Recruitment.json` | New strings |
| `languages/pl_pl/Settings/Recruitment.json` | New strings |
| `languages/en_us/ProjektyRekrutacyjne.json` | `PLL_STATUS_TRANSITION_NOT_ALLOWED` |
| `languages/pl_pl/ProjektyRekrutacyjne.json` | Same |

### Code being created

| Path | Purpose |
|---|---|
| `src/Modules/Settings/Recruitment/` | Settings submodule (Views, Actions, Models) |
| `src/Modules/ProjektyRekrutacyjne/Services/RecruitmentStatusTransition.php` | Load, validate, adjacency map |
| `layouts/basic/modules/Settings/Recruitment/` | Matrix UI templates |
| `public/layouts/basic/modules/Settings/Recruitment/resources/` | Matrix save JS |
| `migrations/Users/m26XXXX_000001_recruitment_status_transitions.php` | Schema + settings menu entry |

### Code being deleted

None.

### DB

| Object | Change |
|---|---|
| `u_yf_recruitment_status_transitions` | **CREATE** — `(id, from_status, to_status)` + unique index |
| `u_yf_recruitment_settings` | **CREATE** — `(id=1, configured TINYINT)` flag for "first save done" |
| `vtiger_settings_field` | **INSERT** — menu item under appropriate Settings block |

### Observable vs internal

| Touchpoint | Observable |
|---|---|
| Settings › Recruitment matrix | Yes — new admin screen |
| Kanban drag behavior | Yes — illegal drops blocked (after configuration) |
| Screening Accept/Reject placement | Yes — removed from Kandydaci tab |
| Workflow status changes | No change |
| Existing relation data | No change |

### Call sites for `changeStatus()`

All three manual actions call it — all get validation when rules are active:

- `ChangeCandidateStatusManuallyAjax`
- `AcceptCandidateManuallyAjax`
- `RejectCandidateManuallyAjax`

Workflows use `updateRelationData()` directly or trigger on relation change — **not modified**.

---

## Functional requirements

### Before configuration (`configured = 0`, transitions table empty)

- Kanban: all transitions allowed (current behavior).
- Screening Accept/Reject: work as today.
- Settings UI: matrix pre-filled with **suggested defaults** (UI only, not persisted).

### After first Save (`configured = 1`)

- Only whitelisted `from_status → to_status` pairs allowed for manual changes.
- Kanban: illegal drop → chip reverts, error toast (`PLL_STATUS_TRANSITION_NOT_ALLOWED`).
- Server rejects illegal AJAX with same message (defense in depth).
- Screening Accept/Reject succeed only if respective transition is whitelisted:
  - Accept: `PPL_APPLIED → PPL_CANDIDATE_PASSED_SCREENING`
  - Reject: `PPL_APPLIED → PPL_REJECTED_AFTER_CV`
- Same-status (`from === to`): no-op, unchanged.
- Workflows / cron / imports: **exempt** — no validation in `updateRelationData()`.

### Settings › Recruitment UI

- Checkbox matrix: rows = source status, columns = target status (11×11).
- Row/column headers translated from `ProjektyRekrutacyjne` language strings.
- Helpers: "select all in row", "select all in column", "clear row".
- Save replaces all rows atomically (delete + insert in transaction).
- First visit: suggested defaults checked in UI; DB untouched until Save.

### Suggested UI defaults (pre-fill only, not enforced)

Mirror typical forward flow from kanban layout:

```
PPL_APPLIED → PPL_REJECTED_AFTER_CV, PPL_CANDIDATE_PASSED_SCREENING
PPL_REJECTED_AFTER_CV → PPL_CANDIDATE_PASSED_SCREENING
PPL_CANDIDATE_PASSED_SCREENING → PPL_WAITING_FOR_INTERVIEW, PPL_REJECTED_AFTER_VERIFICATION
PPL_WAITING_FOR_INTERVIEW → PPL_TO_BE_SENT_TO_CLIENT, PPL_REJECTED_AFTER_INTERVIEW
PPL_TO_BE_SENT_TO_CLIENT → PPL_SENT_TO_CLIENT
PPL_SENT_TO_CLIENT → PPL_ACCEPTED, PPL_REJECTED_BY_CLIENT, PPL_OFFER_REJECTED_BY_CANDIDATE
```

Admin adjusts before Save. **Not hardcoded as enforced rules.**

### Screening button visibility

Change condition in `RelatedList.tpl` from:

```smarty
{if $RELATED_MODULE_NAME eq "Kandydaci"}
```

to:

```smarty
{if $RELATED_MODULE_NAME eq "Kandydaci" && $RELATION_MODEL->get('label') eq 'Screening'}
```

`RelatedList.js`: Accept/Reject handlers remain registered globally but buttons are absent on Kandydaci tab (no-op).

### JS adjacency map format

Embedded on kanban widget:

```json
{
  "configured": false,
  "transitions": {}
}
```

After configuration:

```json
{
  "configured": true,
  "transitions": {
    "PPL_APPLIED": ["PPL_REJECTED_AFTER_CV", "PPL_CANDIDATE_PASSED_SCREENING"]
  }
}
```

When `configured === false`: JS allows all drops (current behavior).

### Out of scope

- Per-project or per-role transition rules
- Kanban column layout driven by config (columns stay hardcoded in tpl)
- Workflow transition rules (separate CR)
- Replacing hardcoded status list with dynamic picklist admin

---

## Data migration

```sql
CREATE TABLE u_yf_recruitment_status_transitions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  from_status VARCHAR(64) NOT NULL,
  to_status VARCHAR(64) NOT NULL,
  UNIQUE KEY uq_from_to (from_status, to_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE u_yf_recruitment_settings (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
  configured TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO u_yf_recruitment_settings (id, configured) VALUES (1, 0);
```

Settings menu entry via migration (`Module::addSettingsField`).

**Idempotent:** `CREATE TABLE IF NOT EXISTS`, check before menu insert.

**Rollback:** drop both tables, delete settings menu row. No data loss on relation records.

**Existing non-conforming rows:** not touched — rules apply only to future manual transitions.

---

## Implementation plan

1. **Migration** — tables + settings menu entry + seed `configured = 0`
2. **`RecruitmentStatusTransition` service** — `isConfigured()`, `isAllowed()`, `getAdjacencyMap()`, `saveMatrix()`, `getSuggestedDefaults()`
3. **Settings › Recruitment** — Index view with matrix, `SaveAjax` action
4. **`GetRelatedMembers::changeStatus()`** — if `RecruitmentStatusTransition::isConfigured()` && !allowed → return false
5. **Ajax actions** — map failure to `PLL_STATUS_TRANSITION_NOT_ALLOWED`
6. **Kanban widget** — pass config to tpl; embed JSON
7. **`Detail.js`** — dragstart highlights valid columns; drop checks map; revert + toast on illegal
8. **`RelatedList.tpl`** — Screening-only buttons
9. **Language files** — `en_us` + `pl_pl`
10. **Minify** — `Detail.js`, Settings JS per `frontend-assets.mdc`

---

## Testing

### Settings

- [ ] First visit: matrix shows suggested defaults, DB empty, `configured = 0`
- [ ] Save: rows persisted, `configured = 1`
- [ ] Reload: saved state matches
- [ ] Uncheck all + Save: all manual transitions blocked

### Kanban (before Save)

- [ ] All drag-drop transitions work as today

### Kanban (after Save)

- [ ] Whitelisted drag succeeds, DOM updates
- [ ] Illegal drag: chip reverts, error toast, no server call (or server rejects if bypassed)
- [ ] Valid targets highlighted during drag

### Screening

- [ ] Kandydaci tab: no Accept/Reject buttons
- [ ] Screening tab: buttons visible
- [ ] Accept with whitelisted transition: success
- [ ] Accept with transition unchecked: toast error

### Regression

- [ ] Workflow-triggered status change still works
- [ ] New candidate link → `PPL_APPLIED` unchanged
- [ ] Project counters / comments on status change unchanged
- [ ] `cache/logs/system.log` clean after smoke test

### Verification grep

```bash
rg "acceptCandidateManually" layouts/basic/modules/ProjektyRekrutacyjne/
rg "RecruitmentStatusTransition" src/
```

If templates changed: `rm -f cache/templates_c/*.php` and reload.

---

## Rollback plan

1. Revert code commit
2. Run migration down (drop tables, remove menu entry)
3. Clear Smarty cache: `rm -f cache/templates_c/*.php`
4. **No backup restore needed** — additive schema only

---

## Edge cases

| Case | Handling |
|---|---|
| Admin saves empty matrix | All manual transitions blocked; intentional |
| Concurrent drag (status changed server-side) | Server validates actual `sourceStatus`; fails with toast |
| NULL status (~67 rows in production) | Not in kanban columns; out of scope |
| New status added to code later | Appears in matrix UI; blocked until admin whitelists |
| Screening tab label renamed | Match on relation label `Screening`; document dependency |

---

## Decision rationale & tradeoffs

| Choice | Why |
|---|---|
| Whitelist | Safer when new statuses appear |
| Separate `configured` flag | Option B: no behavior change until explicit Save |
| PHP service + JS map | Single source; JS for UX, PHP for security |
| Workflows exempt | User requirement; avoids breaking automation |
| Screening-only buttons | Matches business process; source always `PPL_APPLIED` |
| Matrix UI vs list editor | Visual match to kanban; 11×11 is manageable |

**Alternative rejected:** PickListDependency — wrong abstraction (module field deps, not relation transitions).

---

## Risks

| Risk | Severity | Mitigation |
|---|---|---|
| Admin saves restrictive matrix, breaks daily workflow | Med | Suggested UI defaults; review before Save |
| Forgot to configure after deploy | Low | Pre-Save = current behavior continues |
| JS/server rule drift | Low | Same service generates both |
| Screening tab label renamed | Low | Match on relation label constant; document in CR |
