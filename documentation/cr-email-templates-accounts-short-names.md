# Change Request: Recruitment email templates — short names, accounts, transition resolution

## Goal

Recruitment **transition mail** (kanban status change) and **manual compose** from Candidates (with project context) resolve `ProjektyRekrutacyjne` email templates using:

1. **Transition matrix** (`Settings › Rekrutacja › TransitionMail`) — which **short names** apply to each status transition (`from → to`).
2. **Template metadata** — optional **Accounts** links per template; empty = **global** fallback.
3. **Project context** — `kontrahent` selects the account-specific variant; if none exists for a short name, use the global variant.

Admins configure one short name per “mail moment” in the matrix, not one template row per account. Per-account wording lives in separate templates sharing the same short name (e.g. `require_to_sign_permission` for Orange vs Budimex).

---

## Stance

- Matrix stores **`short_name`** (maps to `u_yf_emailtemplates.sys_name`), **not** `email_template_id`. Drop `email_template_id` in the same CR.
- **No parallel paths** — remove template-ID matrix logic from PHP, JS, tpl, tests.
- Reuse existing column **`sys_name`**; expose on EmailTemplates **Edit + Detail** for `module = ProjektyRekrutacyjne`.
- **`class_alias()` forbidden.**

---

## Impact

### Observable vs internal

| Change | Observable |
|--------|------------|
| TransitionMail matrix picker shows **short names only** | Yes — Settings UI |
| EmailTemplates Edit/Detail: short name + Accounts panel | Yes — admin UI |
| Kanban mail modal template list filtered by account | Yes — recruiter UX |
| Manual compose from Candidates + project: filtered templates | Yes |
| DB: matrix column rename, junction table | Internal (migration) |

### Code being modified

| Path | Change |
|------|--------|
| `migrations/Users/m260617_000001_email_template_accounts_transition_short_names.php` | **New** — schema, backfill, field metadata |
| `src/Modules/EmailTemplates/Models/TemplateAccount.php` | **New** — junction CRUD, overlap/uniqueness checks |
| `src/Modules/EmailTemplates/Models/RecruitmentTemplate.php` | **New** — resolver helpers: `resolveByShortName`, `filterListForAccount`, `getDistinctShortNames`, `isShortNameUsedInMatrix`, account-tier filter |
| `src/Modules/ProjektyRekrutacyjne/Services/RecruitmentStatusTransitionMail.php` | Matrix CRUD on `short_name`; `getPrompt(from, to, accountId)` resolves template IDs |
| `src/Modules/ProjektyRekrutacyjne/Actions/ChangeCandidateStatusManuallyAjax.php` | Load project `kontrahent`; pass account ID to `getPrompt()` |
| `src/Modules/Settings/Recruitment/Views/TransitionMail.php` | Assign `SHORT_NAME_OPTIONS` instead of/in addition to `TEMPLATE_OPTIONS` |
| `layouts/basic/modules/Settings/Recruitment/TransitionMailContent.tpl` | Multi-select **short names**; option value = `sys_name`; label = short name only |
| `public/layouts/basic/modules/Settings/Recruitment/resources/TransitionMail.js` | `collectEntries()` → `shortNames`; rename `.js-mail-template-ids` → `.js-mail-short-names`; update save validation messages |
| `public/layouts/basic/modules/Settings/Recruitment/resources/TransitionMail.min.js` | Regenerate via `npm run minify-js` |
| `src/Modules/Settings/Recruitment/Actions/SaveAjax.php` | `saveTransitionMail()` normalizes `shortNames[]` instead of `templateIds[]` |
| `src/Modules/Base/Views/IndividualSendMailModal.php` | When `sourceModule=ProjektyRekrutacyjne` + `sourceRecord`: load `kontrahent`, apply account-tier filter to `TEMPLETE_LIST` |
| `src/Email/Mail.php` | **Critical:** `getTempleteList()` / `getTempleteListForModules()` — today `hideSystem=true` excludes `sys_name IS NOT NULL`; recruitment templates with short names would vanish from compose. Change filter to hide system templates **except** `module = 'ProjektyRekrutacyjne'`. Optionally include `sys_name` in select for compose/debug. Invalidate `MailTempleteList` cache keys after template save. |
| `src/Modules/EmailTemplates/Views/Edit.php` | Register Accounts panel JS/CSS (mirror `TemplateAttachment` wiring) |
| `src/Modules/EmailTemplates/Views/Detail.php` | **New** (if module has no Detail view yet) or extend Base Detail — show `sys_name` + linked account names for recruitment templates |
| `layouts/basic/modules/EmailTemplates/EditViewBlocks.tpl` | Include `partials/TemplateAccounts.tpl` when target module is ProjektyRekrutacyjne |
| `layouts/basic/modules/EmailTemplates/partials/TemplateAccounts.tpl` | **New** — multi-select / popup link Accounts (pattern: `TemplateAttachments.tpl`) |
| `public/layouts/basic/modules/EmailTemplates/resources/TemplateAccounts.js` (+ `.min.js`) | **New** — link/unlink accounts Ajax |
| `public/layouts/basic/modules/EmailTemplates/resources/Edit.js` (+ `.min.js`) | Init accounts panel; client-side hints for recruitment module |
| `src/Modules/EmailTemplates/Actions/TemplateAccount.php` | **New** — Ajax modes `list`, `link`, `unlink` |
| `src/Modules/EmailTemplates/Actions/Save.php` | **New** — extends `Base\Actions\Save`: validate `sys_name` required + unique overlap for ProjektyRekrutacyjne; block `sys_name` rename if old value in matrix |
| `src/Modules/EmailTemplates/Actions/DeleteAjax.php` | **New** — extends `Base\Actions\Delete`: block delete if template `sys_name` referenced in matrix (pattern: `TemplateElements/Actions/DeleteAjax.php`) |
| `src/Modules/EmailTemplates/EmailTemplates.php` | Optional: add `sys_name` to list/search fields for admin visibility |
| `languages/en_us/EmailTemplates.json` | Labels: short name, accounts, validation errors |
| `languages/pl_pl/EmailTemplates.json` | Same keys in Polish |
| `languages/en_us/Settings/Recruitment.json` | Update TransitionMail help + short-name validation strings |
| `languages/pl_pl/Settings/Recruitment.json` | Same |
| `tests/recruitment_status_transition_mail_smoke.php` | Rewrite for short-name matrix + account resolution |
| `.cursor/rules/recruitment-settings.mdc` | Document short-name matrix + account resolution |

### Code being deleted

| Item | Location |
|------|----------|
| Column `email_template_id` | `u_yf_recruitment_status_transition_mail` |
| Unique index `u_yf_recruitment_status_transition_mail_from_to_tpl` on `(from_status, to_status, email_template_id)` | DB |
| Matrix load/save/display using template IDs | `RecruitmentStatusTransitionMail`, `TransitionMailContent.tpl`, `TransitionMail.js`, `SaveAjax::saveTransitionMail` |
| `getValidTemplateIdList()` / `filterValidTemplateIds()` as gate for matrix IDs | `RecruitmentStatusTransitionMail.php` — replace with short-name resolution |
| `TEMPLATE_OPTIONS` from `Mail::getTempleteList('ProjektyRekrutacyjne')` in TransitionMail view | `TransitionMail.php` — use distinct short names instead |

### Database

**Current state (dev DB, MCP):**

- `u_yf_emailtemplates`: column `sys_name` VARCHAR(50), indexed; 3 ProjektyRekrutacyjne rows, all `sys_name IS NULL`.
- `u_yf_recruitment_status_transition_mail`: 1 row — `PPL_APPLIED → PPL_CANDIDATE_PASSED_SCREENING`, `email_template_id = 1442025`.
- `ProjektyRekrutacyjne.kontrahent`: uitype 10 → Accounts, **mandatory** (`typeofdata ~M`).

**Add:**

```sql
CREATE TABLE u_yf_accounts_emailtemplates (
  emailtemplatesid INT(11) NOT NULL,
  accountid INT(11) NOT NULL,
  PRIMARY KEY (emailtemplatesid, accountid),
  KEY idx_ete_account (accountid),
  CONSTRAINT fk_ete_template FOREIGN KEY (emailtemplatesid)
    REFERENCES vtiger_crmentity (crmid) ON DELETE CASCADE,
  CONSTRAINT fk_ete_account FOREIGN KEY (accountid)
    REFERENCES vtiger_crmentity (crmid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

**Alter matrix table:**

```sql
ALTER TABLE u_yf_recruitment_status_transition_mail
  ADD COLUMN short_name VARCHAR(50) NULL AFTER to_status;

-- Data migration (PHP, idempotent):
-- 1. Backfill sys_name on ProjektyRekrutacyjne templates where NULL (slug from name)
-- 2. UPDATE matrix SET short_name = (SELECT sys_name FROM u_yf_emailtemplates WHERE emailtemplatesid = ...)
-- 3. DROP INDEX u_yf_recruitment_status_transition_mail_from_to_tpl
-- 4. DROP COLUMN email_template_id
-- 5. ALTER short_name NOT NULL
-- 6. ADD UNIQUE INDEX (from_status, to_status, short_name)
```

**Module metadata (`vtiger_field`):**

- Register **`sys_name`** on EmailTemplates (Edit + Detail, block with name/subject); required when `module_name = ProjektyRekrutacyjne` (server validation).
- Accounts: custom panel (junction) — **no** standard uitype-10 field required if using partial + Ajax like attachments.

**Post-metadata:** `docker compose exec -T app php bin/regenerate_user_privileges.php`

### Call sites (contract changes)

| Contract | Callers to update |
|----------|-------------------|
| `RecruitmentStatusTransitionMail::getPrompt(from, to)` → `getPrompt(from, to, accountId)` | `ChangeCandidateStatusManuallyAjax::buildMailPrompt` |
| `saveMatrix(entries with templateIds)` → `shortNames` | `SaveAjax::saveTransitionMail`, `TransitionMail.js`, smoke test |
| `getMatrixForDisplay()` returns `list<int>` → `list<string>` short names | `TransitionMailContent.tpl`, `TransitionMail.php` |
| `Mail::getTempleteList` hideSystem semantics | `IndividualSendMailModal`, any recruitment template consumer |

**Out of scope (v1):** `Settings/Workflows`, `SendMailModal` mass mail, `Mail/Views/Compose.php`, webservices.

### Caches / cron

- Clear Smarty: `rm -f cache/templates_c/*.php` after tpl deploy.
- `MailTempleteList` cache bust on EmailTemplates save/delete (extend existing save path).

---

## Functional requirements

### Before → after

| ID | Before | After |
|----|--------|-------|
| F1 | Matrix cell stores template IDs; runtime returns those IDs | Matrix cell stores **short names**; runtime **resolves** to template ID by account |
| F2 | All matrix templates offered regardless of project account | Account-specific templates preferred; **globals hidden** when account-specific match |
| F3 | No account dimension on templates | Optional Accounts (0..many); empty = global |
| F4 | Manual compose: all ProjektyRekrutacyjne templates | Same **account-tier filter** when project context present |
| F5 | `sys_name` hidden; NULL on recruitment templates | **Required** short name on Edit/Detail for ProjektyRekrutacyjne templates |
| F6 | Delete/rename template freely | **Blocked** if `sys_name` still listed in transition matrix |

### Short name (`sys_name`)

- Reuse `u_yf_emailtemplates.sys_name` (max 50 chars).
- **Required** when `module = ProjektyRekrutacyjne`.
- Format: **loose** — any non-empty trimmed string ≤ 50 chars.
- Matrix picker (F1 A): only values that exist on ≥1 ProjektyRekrutacyjne template.
- Matrix display (R5 C): **short name only** in `<select>`.

### Accounts

- Junction `u_yf_accounts_emailtemplates`.
- **0 rows = global** template for that short name.
- **Many accounts on one template** allowed (same body, F6).
- **Uniqueness:** for each account ID, at most one ProjektyRekrutacyjne template per `sys_name`. No second template may overlap accounts or global slot for the same short name. Enforced on **EmailTemplates Save**.

### Transition matrix (Settings › TransitionMail)

- Per `from → to` (where `from ≠ to`): checkbox + multi-select short names.
- Checkbox checked ⇒ ≥1 short name required on save (same UX as today’s template requirement).
- Short names must exist in template catalog (server validates on save).

### Runtime — kanban transition mail

Input: `from_status`, `to_status`, project `kontrahent` (account ID).

1. Load short names for `(from, to)` from matrix. None ⇒ **no `mailPrompt`**.
2. For each short name **in matrix order**:
   - Find template: `module=ProjektyRekrutacyjne`, `sys_name` match, linked to `kontrahent`.
   - Else find **global** (same `sys_name`, no junction rows).
   - Else **skip** short name silently (F4 A).
3. Build `templateIds` from resolved templates.
4. **Tier rule:** if any resolved template was account-specific (step 2 first branch), **remove** globals from the result set.
5. Empty result ⇒ **no prompt, no mail**.
6. One ID ⇒ modal with that template (existing `templateIds` JSON contract to frontend unchanged).
7. Multiple IDs ⇒ modal picker, order preserved (R3 A).

`ChangeCandidateStatusManuallyAjax` continues returning `{ candidateId, projectId, templateIds }` — only resolution logic changes.

### Runtime — manual compose

When `sourceModule=ProjektyRekrutacyjne` and `sourceRecord` set:

- Load project `kontrahent`.
- Start from `Mail::getTempleteList('ProjektyRekrutacyjne')` (with fixed hideSystem — see `Mail.php`).
- Apply **account-tier filter** (same as transition: account-specific only if any exist for that account, else globals).
- **Do not** filter by matrix short names (R4 A).

Without project context: unchanged behavior.

### Validation / guards

| Rule | Where |
|------|-------|
| `sys_name` required for ProjektyRekrutacyjne | `EmailTemplates/Actions/Save.php` |
| No overlapping `(sys_name, account)` | `TemplateAccount` + Save |
| Block `sys_name` change if old name in matrix | Save |
| Block delete if `sys_name` in matrix | `DeleteAjax.php` |
| Matrix short names must exist | `RecruitmentStatusTransitionMail::saveMatrix()` |

### Out of scope

- Workflows, mass mail, non-recruitment modules using `sys_name` as short name.
- Auto-generating long `name` from short name.
- Logging unresolved short names at runtime (F4 A — silent skip only).

---

## Data migration

### Backfill `sys_name` (F3 A — idempotent)

For each ProjektyRekrutacyjne template with `sys_name IS NULL`:

- Generate slug from `name` (lowercase, non-alphanumeric → `_`, trim, max 50).
- On collision append `_<emailtemplatesid>`.

Example mapping (dev — adjust in migration):

| id | name | proposed sys_name |
|----|------|-------------------|
| 1441893 | Kandydaci – Potwierdzenie… | `kandydaci_potwierdzenie_otrzymania_aplikacji` |
| 1442025 | Kandydaci - Zaproszenie… | `kandydaci_zaproszenie_na_rozmowe` |
| 1442031 | Kandydaci - Zgoda… | `kandydaci_zgoda_na_przetwarzanie_danych` |

### Matrix row migration

Existing row `PPL_APPLIED → PPL_CANDIDATE_PASSED_SCREENING`, template `1442025`:

- Set `short_name` = that template’s backfilled `sys_name` (e.g. `kandydaci_zaproszenie_na_rozmowe`).
- Drop `email_template_id`.

### Non-conforming rows

- Matrix rows pointing at deleted/invalid template IDs: **delete row** (log count in migration output).
- Re-run safe: `INSERT … ON DUPLICATE KEY` / `UPDATE WHERE short_name IS NULL` patterns only.

### Rollback

- **Code:** revert commit / redeploy previous tag.
- **Data:** after `email_template_id` dropped → **restore from backup**. Cheap rollback only before step 4 of matrix migration.

---

## Implementation plan

Ordered steps; each should leave the app runnable.

### Step 1 — Schema migration

**File:** `migrations/Users/m260617_000001_email_template_accounts_transition_short_names.php`

- Create `u_yf_accounts_emailtemplates`.
- Backfill `sys_name` on recruitment templates.
- Add `short_name` to matrix; migrate existing row(s); drop `email_template_id`; add unique index.
- Insert `vtiger_field` for `sys_name` (Edit + Detail blocks).
- Run: `docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0`
- Run: `docker compose exec -T app php bin/regenerate_user_privileges.php`

### Step 2 — Backend models

**New files:**

- `src/Modules/EmailTemplates/Models/TemplateAccount.php`
- `src/Modules/EmailTemplates/Models/RecruitmentTemplate.php`

**`TemplateAccount` methods:**

- `listForTemplate(int $templateId): list<array{id, name}>`
- `getAccountIdsForTemplate(int $templateId): list<int>`
- `link(int $templateId, int $accountId): void`
- `unlink(int $templateId, int $accountId): void`
- `assertNoSysNameOverlap(int $templateId, string $sysName, string $module, list<int> $accountIds): void`

**`RecruitmentTemplate` methods:**

- `getDistinctShortNames(): list<string>`
- `findForShortNameAndAccount(string $sysName, int $accountId): ?int` (template ID)
- `findGlobalForShortName(string $sysName): ?int`
- `resolveShortNamesForAccount(list<string> $shortNames, int $accountId): list<int>` (tier rule applied)
- `filterTemplateListForAccount(array $templateList, int $accountId): array`
- `isShortNameUsedInMatrix(string $sysName): bool`

### Step 3 — Transition mail service

**File:** `src/Modules/ProjektyRekrutacyjne/Services/RecruitmentStatusTransitionMail.php`

- `getMatrixForDisplay(): array<string, array<string, list<string>>>` — short names per cell.
- `getPrompt(string $from, string $to, int $accountId): ?array{templateIds: list<int>}` — delegate to `RecruitmentTemplate::resolveShortNamesForAccount`.
- `saveMatrix(array $entries)` — each entry: `{ from, to, shortNames: list<string> }`; validate short names exist; unique per cell.
- Remove `filterValidTemplateIds`, `getValidTemplateIdList`.

### Step 4 — Kanban call site

**File:** `src/Modules/ProjektyRekrutacyjne/Actions/ChangeCandidateStatusManuallyAjax.php`

- In `buildMailPrompt()`: load project via `Record::getInstanceById($projectId, 'ProjektyRekrutacyjne')`, read `(int) $project->get('kontrahent')`.
- Call `getPrompt($source, $destination, $accountId)`.

### Step 5 — Mail.php hideSystem fix

**File:** `src/Email/Mail.php`

- In `getTempleteList` / `getTempleteListForModules`, when `$hideSystem === true`:

  ```php
  // Replace: ->andWhere(['u_yf_emailtemplates.sys_name' => null])
  // With: exclude system templates but keep recruitment short-name templates
  ->andWhere(['or',
      ['u_yf_emailtemplates.sys_name' => null],
      ['u_yf_emailtemplates.module' => 'ProjektyRekrutacyjne'],
  ])
  ```

- Consider adding `sys_name` to SELECT for admin/debug (optional).

### Step 6 — Manual compose filter

**File:** `src/Modules/Base/Views/IndividualSendMailModal.php`

- After `getTempleteList($templateModule)`, if `$templateModule === 'ProjektyRekrutacyjne'` and `$sourceRecord`:
  - Load `kontrahent` from project.
  - `$templateList = RecruitmentTemplate::filterTemplateListForAccount($templateList, $accountId)`.
- Existing `filterTemplateList($list, $allowedIds)` for kanban `templateIds` param stays — applied **after** account filter when both present.

### Step 7 — EmailTemplates UI + actions

**New:**

- `src/Modules/EmailTemplates/Actions/TemplateAccount.php`
- `src/Modules/EmailTemplates/Actions/Save.php`
- `src/Modules/EmailTemplates/Actions/DeleteAjax.php`
- `layouts/basic/modules/EmailTemplates/partials/TemplateAccounts.tpl`
- `public/layouts/basic/modules/EmailTemplates/resources/TemplateAccounts.js` (+ minify)

**Modify:**

- `src/Modules/EmailTemplates/Views/Edit.php` — register TemplateAccounts assets.
- `layouts/basic/modules/EmailTemplates/EditViewBlocks.tpl` — include accounts partial after content block (when module ProjektyRekrutacyjne); show/hide via JS on `module_name` change.
- `public/layouts/basic/modules/EmailTemplates/resources/Edit.js` — init panel; toggle visibility.
- Detail: ensure `sys_name` visible — via `vtiger_field` on Detail block (may work with Base Detail without new view file); add read-only accounts list via small Detail hook or widget if field metadata insufficient.

### Step 8 — TransitionMail Settings UI

**Files:**

- `src/Modules/Settings/Recruitment/Views/TransitionMail.php` — `SHORT_NAME_OPTIONS` from `RecruitmentTemplate::getDistinctShortNames()`; remove `TEMPLATE_OPTIONS` / `getTempleteList`.
- `layouts/basic/modules/Settings/Recruitment/TransitionMailContent.tpl` — `<option value="{$SHORT}">{$SHORT}</option>`; rename CSS classes for clarity.
- `public/layouts/basic/modules/Settings/Recruitment/resources/TransitionMail.js` — `shortNames` in entries; update validation i18n key if renamed.
- `src/Modules/Settings/Recruitment/Actions/SaveAjax.php` — normalize `shortNames`.
- Minify TransitionMail.js.

### Step 9 — Languages

- `languages/en_us/EmailTemplates.json`, `languages/pl_pl/EmailTemplates.json`
- `languages/en_us/Settings/Recruitment.json`, `languages/pl_pl/Settings/Recruitment.json`

Keys (examples): `FL_SHORT_NAME`, `LBL_TEMPLATE_ACCOUNTS`, `LBL_TEMPLATE_ACCOUNTS_GLOBAL_HINT`, `LBL_ERR_SYS_NAME_REQUIRED`, `LBL_ERR_SYS_NAME_MATRIX_IN_USE`, `LBL_ERR_SYS_NAME_ACCOUNT_OVERLAP`, `LBL_SAVE_TRANSITION_MAIL_SHORT_NAMES_REQUIRED`.

### Step 10 — Tests + docs + legacy cleanup

- Rewrite `tests/recruitment_status_transition_mail_smoke.php`.
- Update `.cursor/rules/recruitment-settings.mdc`.
- Grep verification:

  ```bash
  rg 'email_template_id' src/ layouts/ public/ tests/
  rg 'templateIds' src/Modules/Settings/Recruitment public/layouts/basic/modules/Settings/Recruitment
  ```

---

## Testing

### Automated

```bash
docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
docker compose exec -T app php tests/recruitment_status_transition_mail_smoke.php
rm -f cache/templates_c/*.php
```

**Smoke test scenarios:**

- Matrix save/load with short names.
- Resolve account-specific vs global.
- Tier rule: account-specific hides global.
- Unresolved short name for account → empty prompt.
- Multiple short names on one transition → multiple template IDs.

### Manual — EmailTemplates admin

- [ ] Edit recruitment template: set `sys_name`, link Orange account, save.
- [ ] Duplicate `sys_name` + same account on second template → save **blocked**.
- [ ] One template linked to Orange + Budimex → saves OK.
- [ ] Detail view shows `sys_name` and accounts.
- [ ] Delete template whose `sys_name` is in matrix → **blocked**.
- [ ] Rename `sys_name` while in matrix → **blocked**.

### Manual — TransitionMail

- [ ] Open Settings › Rekrutacja › TransitionMail — picker lists short names only.
- [ ] Configure `PPL_APPLIED → PPL_CANDIDATE_PASSED_SCREENING` with short name; save; reload persists.
- [ ] Checkbox without short name → validation error.

### Manual — Kanban

- [ ] Project Orange + account-specific template → modal shows Orange template only.
- [ ] Project without account-specific, global exists → global shown.
- [ ] Project with no matching template and no global → **no mail modal** after status change.
- [ ] Two short names configured, both resolve → picker with two templates.

### Manual — Manual compose

- [ ] Candidates related list / email with `sourceModule=ProjektyRekrutacyjne` — template dropdown filtered by account tier.
- [ ] Compose without project context — unchanged.

### Regression

- [ ] Existing mail send / preview / attachments still work.
- [ ] `cache/logs/system.log` — no new errors on save/transition.

### Data integrity queries (MCP)

```sql
SELECT emailtemplatesid, name, sys_name, module FROM u_yf_emailtemplates WHERE module = 'ProjektyRekrutacyjne';
SELECT from_status, to_status, short_name FROM u_yf_recruitment_status_transition_mail;
SELECT * FROM u_yf_accounts_emailtemplates;
```

---

## Rollback plan

1. Revert git commit; redeploy previous tag on `test.itconnect.pl`.
2. If migration dropped `email_template_id`: **restore MariaDB backup** (matrix + template metadata changes lost since deploy).
3. If rollback before column drop: run migration `safeDown()` (drop junction, restore `email_template_id` from backup table if copied in migration).
4. Clear Smarty cache.

**Acceptable loss:** transition matrix edits and account links since deploy if restoring backup.

---

## Edge cases

| Case | Handling |
|------|----------|
| Project `kontrahent` empty despite mandatory metadata | Defensive: no prompt / empty compose list; log once (should not happen in normal use) |
| Matrix short name with no template | Save rejected (short name must exist) |
| Matrix short name, template exists, wrong account, no global | Silent skip; no prompt if nothing else resolves |
| Template global + account-specific both in matrix for same short name | Resolver picks account-specific for matching projects; global for others |
| Multiple short names resolve | Modal picker (R3 A) |
| Migration interrupted | Idempotent backfill + `short_name IS NULL` update; safe to re-run migrate |
| HelpDesk templates with `sys_name` | Still hidden by hideSystem (module ≠ ProjektyRekrutacyjne) |
| Cache stale template list | Bust `MailTempleteList` on EmailTemplates save/delete |

---

## Decision rationale & tradeoffs

| Decision | Rationale |
|----------|-----------|
| Short names **in matrix**, not template IDs | Adding a client = new template only; matrix stays stable across accounts. |
| Reuse **`sys_name`** | Column exists; recruitment rows currently NULL; avoids second field. |
| Matrix still **gates** which moments fire | Admin controls which transitions prompt mail; templates control wording + account. |
| **`hideSystem` fix in Mail.php** | Without it, recruitment templates with `sys_name` set would disappear from all compose pickers. |
| Junction for accounts (not JSON column) | Matches `u_yf_documents_emailtemplates` pattern; queryable for uniqueness. |
| Block delete/rename when in matrix | Prevents silent misconfiguration (F5 B). |
| Manual compose uses account filter, not matrix | Ad-hoc mail can use any recruitment template for that client (R4 A). |

**Rejected:** listing every account-specific template ID in each matrix cell — operational burden, error-prone.

---

## Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| `hideSystem` regression hides/shows wrong templates | **High** | Explicit Mail.php change + compose smoke tests |
| Uniqueness / overlap validation gaps | Med | Save tests + manual duplicate attempts |
| Migration slug collisions / wrong sys_name | Med | Idempotent migration; admin review in EmailTemplates list |
| Missed call site still using template IDs | Med | Grep gate in testing section |
| Detail view doesn’t show accounts without custom tpl | Low | Field metadata + partial or Detail hook |

---

## Assumptions

- `sys_name` **required** for all ProjektyRekrutacyjne templates after migration.
- `kontrahent` mandatory on projects (already in DB).
- Frontend kanban contract stays `{ mailPrompt: { templateIds, candidateId, projectId } }`.
- v1 scope: kanban transition mail + `IndividualSendMailModal` with project context only.
- Silent skip when short name does not resolve (no `system.log` entry).

---

## Questions resolved (reference)

| # | Decision |
|---|----------|
| Account source | `kontrahent` |
| Resolution | Account-specific first; hide globals when any account-specific match |
| Matrix role | Required gate for **short names** per transition |
| Accounts field | ProjektyRekrutacyjne only; 0..many |
| Manual compose | All module templates + account tier filter |
| Short name field | Reuse `sys_name`; Edit + Detail |
| Matrix picker | Existing short names only; display short name only |
| Uniqueness | No duplicate `(sys_name, account)` including global slot |
| No match | No prompt / no mail |
| Delete/rename | Block if `sys_name` in matrix |
| Migration | Auto-slug from `name` for existing 3 templates |
