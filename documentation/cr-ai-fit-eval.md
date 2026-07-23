# Change Request: AI fit evaluation (CV × recruitment project)

**Status:** Ready for implementation — decisions confirmed  
**Depends on:** [cr-ai-prompts.md](cr-ai-prompts.md), [cr-ai-prompts-mail-improve.md](cr-ai-prompts-mail-improve.md), [cr-ai-request-logging.md](cr-ai-request-logging.md)  
**Follow-up (out of scope):** richer kanban sort/panel, auto-reject, history of evals, `PPL_AI_ADDED` sourcing  
**Related rules:** `.cursor/rules/ai.mdc`, `recruitment-project-candidates.mdc`, `docker-commands.mdc` (CV import A/B)

---

## Goal

When a candidate is **linked** to a recruitment project via **CV apply** (`PPL_APPLIED`) or **manual add** (`PPL_MANUALLY_ADDED`), FreeCRM **asynchronously** asks OpenAI how well the CV matches the project requirements and stores a structured result on the pair (project, candidate).

After this CR:

1. New table **`u_yf_ai_fit_eval`** holds queue state + score + summary + competency breakdown.
2. Action **`recruitment.fit_eval`** exists in `ActionRegistry` + seeded system prompt in `s_yf_ai_prompts`.
3. Cron **phase C** processes `pending` rows via `PromptResolver` + `OpenAi\Client` (same choke point as mail improve; logged to `ai.log`).
4. Recruiters see fit on kanban chips in **entry** (`PPL_MANUALLY_ADDED`) and **Screening** (`PPL_APPLIED`) — **assist, never auto-reject**.

---

## Stance

- **No fallbacks** — missing prompt / API key / bad JSON → `eval_status=failed` (or `skipped` with explicit reason); never invent a fake score.
- Consumers use **`PromptResolver` + `Client`** only; no ad-hoc prompts or second HTTP client.
- Fit is **per relation pair**, not a global candidate score.
- Import phases A/B stay fast — **no OpenAI inside phase B**.
- `class_alias()` forbidden. No silent empty prompts.
- Do not conflate with **`PPL_AI_ADDED`** (future: AI suggests candidates *into* a project).

---

## Questions (confirmed)

| # | Decision |
|---|----------|
| Q1 | Backend + cron + **minimal kanban badge** + tooltip/summary + re-eval (not full sort/panel). |
| Q2 | Auto-queue **`PPL_APPLIED`** and **`PPL_MANUALLY_ADDED`**. |
| Q3 | **No** migrate backfill — new links only (+ optional CLI later). |
| Q4 | Score **0–100**. |
| Q5 | **Same** Provider model as mail. |
| Q6 | AI prose **Polish**. |
| Q7 | CV / project requirement changes → **eager** `pending`. |
| Q8 | Prompt / model changes → **lazy** (manual re-eval). |
| Q9 | Manual **„Oceń ponownie”** per chip — yes. |
| Q10 | Input includes **`application_message`**; **not** salary. |
| Q11 | Cron every **60 s**, batch **5**. |
| Q12 | Missing `cv_text` → **`skipped`**; re-queue when text appears. |
| Q13 | Response = **JSON schema v1** only. |
| Q14 | Visible to anyone with normal project/candidate access. |
| Q15 | **One CR** (badge scope). |
| Q16 | Badge on **both** entry (`PPL_MANUALLY_ADDED`) and Screening (`PPL_APPLIED`) chips. |

---

## Decisions (confirmed)

| # | Decision |
|---|----------|
| D1 | Scope = table + prompt + service + cron C + **minimal kanban badge/tooltip** on entry + Screening chips + manual re-eval (Q1/Q9/Q16). |
| D2 | Storage = **`u_yf_ai_fit_eval`** (not columns on relation). UNIQUE `(project_id, candidate_id)`. |
| D3 | Auto-queue on **`PPL_APPLIED`** and **`PPL_MANUALLY_ADDED`** after link + when `cv_text` present (else `skipped`). Not on `PPL_AI_ADDED` until that flow exists and is product-approved. |
| D4 | No migrate backfill; optional follow-up script. |
| D5 | `fit_score` **0–100**; `breakdown_json` schema versioned. |
| D6 | Action key **`recruitment.fit_eval`**; placeholders below. |
| D7 | Same OpenAI Provider key/model; omit `temperature`. |
| D8 | Phase C cron separate from A/B; claim `pending`→`running`; max **3** attempts then stay `failed`. |
| D9 | `input_hash` = sha256 of canonical job+CV payload; skip API if hash + `prompt_revision` + `model` match and `done`. |
| D10 | Eager invalidation on CV / project requirement field changes; lazy on prompt/model change. |
| D11 | **No auto status change** / no auto rejection mail from score. |
| D12 | Delete eval row when membership relation is deleted. |
| D13 | AI prose in **Polish**. |
| D14 | Badge + re-eval on chips in **both** kanban entry (`PPL_MANUALLY_ADDED`) and Screening (`PPL_APPLIED`) columns (Q16 A). |

---

## Assumptions

| # | Assumption |
|---|------------|
| A1 | CR-1/2 + AI logging are live (`Client`, `PromptResolver`, Provider, `ai.log`). |
| A2 | `cv_text` is already capped (~10k) at import; same text is the CV corpus for eval. |
| A3 | Project skill fields `needed_skills` / `nice_to_have_skills` / `our_requirements` / `nazwa_projektu` are enough; full `tresc` HTML is **not** sent (too large / noisy). |
| A4 | Deduped candidates re-applying to a **new** project get a **new** eval row; same pair UNIQUE → re-queue updates the same row. |
| A5 | `PPL_AI_ADDED` and boolean `CvSkillsSearch` unchanged. |
| A6 | Cost/latency: prefer `gpt-4.1-nano` in Provider for UX; seed docs mention nano latency (see `ai.mdc`). |

---

## Functional requirements

### Before → after

| Before | After |
|--------|--------|
| Apply / manual add creates membership only | Also enqueues `u_yf_ai_fit_eval` (`pending` or `skipped`) for `PPL_APPLIED` and `PPL_MANUALLY_ADDED` |
| No fit score | Cron fills `fit_score` + `summary` + `breakdown_json` |
| Screening = status only | Chip shows score when `done`; tooltip/panel = summary (+ gaps) |
| — | Manual re-eval forces `pending` |

### Business rules

1. One current eval per `(project_id, candidate_id)`.
2. Score only authoritative when `eval_status = done`.
3. `failed` / `skipped` / `pending` / `running` show distinct UI affordance (spinner / “—” / error), never a guessed number.
4. Changing `cv_text` or project requirement fields (listed in invalidation) → `pending` for affected rows.
5. Missing active prompt `recruitment.fit_eval` → jobs fail visibly (`failed`, message); admin fixes Settings › AI Prompts.
6. Missing API key → same (`failed` / do not spin forever as `pending` after first attempt).

### Validation

- `fit_score` integer 0–100 when `done`.
- `breakdown_json` must parse; `schema` must be `1`; unknown schema → `failed`.
- `eval_status` enum: `pending\|running\|done\|failed\|skipped`.

### In scope

- Schema + migration + seed prompt  
- `ActionRegistry` entry + placeholders  
- `FitEvalService` + enqueue helpers  
- Hook after CV-apply bind (phase B path)  
- Cron task + crontab registration  
- Invalidation hooks (candidate CV save / project field save)  
- Relation delete cleanup  
- Minimal kanban UI on entry + Screening chips (badge + tooltip/summary + re-eval)  
- i18n EN+PL  
- Smoke test without live OpenAI (queue + hash + JSON validate)

### Out of scope / future

| Item | Why deferred |
|------|----------------|
| Auto `PPL_REJECTED_AFTER_CV` from score | Trust + product decision |
| Eval history versions | v1 UNIQUE current row enough |
| Separate fit model in Settings | YAGNI until cost split needed |
| Sending full job HTML `tresc` | Size/latency; requirements fields suffice |
| `PPL_AI_ADDED` candidate sourcing | Different product |
| Auto-queue `PPL_AI_ADDED` | Separate product; not confirmed with Q2 |
| Sort Screening column by score | Optional if Q1 expands |
| Candidate-facing score | Never |
| DB audit of every OpenAI call | Already `ai.log` |

---

## Data model

### Table

```sql
CREATE TABLE `u_yf_ai_fit_eval` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `project_id` INT(11) NOT NULL COMMENT 'crmid = ProjektyRekrutacyjne',
  `candidate_id` INT(11) NOT NULL COMMENT 'relcrmid = Candidates',
  `eval_status` VARCHAR(16) NOT NULL DEFAULT 'pending'
    COMMENT 'pending|running|done|failed|skipped',
  `fit_score` TINYINT UNSIGNED NULL DEFAULT NULL COMMENT '0-100; NULL until done',
  `summary` VARCHAR(1000) NULL DEFAULT NULL,
  `breakdown_json` MEDIUMTEXT NULL DEFAULT NULL,
  `model` VARCHAR(64) NULL DEFAULT NULL,
  `action_key` VARCHAR(64) NOT NULL DEFAULT 'recruitment.fit_eval',
  `input_hash` CHAR(64) NULL DEFAULT NULL COMMENT 'sha256 hex',
  `prompt_revision` VARCHAR(64) NULL DEFAULT NULL
    COMMENT '{prompt_id}:{modifiedtime}',
  `attempt_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `error_message` VARCHAR(500) NULL DEFAULT NULL,
  `queued_at` DATETIME NOT NULL,
  `started_at` DATETIME NULL DEFAULT NULL,
  `evaluated_at` DATETIME NULL DEFAULT NULL,
  `modifiedtime` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ai_fit_eval_pair` (`project_id`, `candidate_id`),
  KEY `idx_ai_fit_eval_queue` (`eval_status`, `queued_at`),
  KEY `idx_ai_fit_eval_project_score` (`project_id`, `fit_score`),
  KEY `idx_ai_fit_eval_candidate` (`candidate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Pair = `u_yf_projekty_rekrutacyjne_relations_members_entity (crmid, relcrmid)`.

### `breakdown_json` schema v1

```json
{
  "schema": 1,
  "must_have": [
    { "skill": "PHP", "status": "met|partial|missing", "evidence": "…" }
  ],
  "nice_to_have": [
    { "skill": "Docker", "status": "met|partial|missing", "evidence": "…" }
  ],
  "gaps": ["…"],
  "risks": ["…"]
}
```

### Prompt placeholders (`recruitment.fit_eval`)

| Placeholder | Source |
|-------------|--------|
| `{{job_title}}` | `nazwa_projektu` |
| `{{needed_skills}}` | `needed_skills` |
| `{{nice_to_have_skills}}` | `nice_to_have_skills` |
| `{{our_requirements}}` | `our_requirements` |
| `{{cv_text}}` | candidate `cv_text` |
| `{{application_message}}` | application message if any, else `""` |

System instruction (code, not Settings body): reply with **JSON only** matching schema v1; Polish strings; no markdown fences.

### Canonical `input_hash`

```
payload = sorted-keys JSON of:
  action_key, project_id, candidate_id,
  job_title, needed_skills, nice_to_have_skills, our_requirements,
  cv_text, application_message
input_hash = sha256_hex(payload)
```

Skip OpenAI when row is `done` and stored `(input_hash, prompt_revision, model)` equal newly computed values.

### Invalidation matrix

| Event | Action |
|-------|--------|
| Link with `PPL_APPLIED` or `PPL_MANUALLY_ADDED` + non-empty `cv_text` | Upsert `pending`, clear score/summary/breakdown, `attempt_count=0` |
| Same link paths, empty `cv_text` | Upsert `skipped` / `missing_cv_text` |
| `cv_text` becomes non-empty | Those `skipped`/`done`/`failed` for candidate → recompute hash → `pending` if changed or was skipped |
| Project fields `nazwa_projektu`, `our_requirements`, `needed_skills`, `nice_to_have_skills` saved | All evals for `project_id` → `pending` if hash would change |
| Prompt body / active flag for `recruitment.fit_eval` | **Lazy** — no mass requeue |
| Provider `model` change | **Lazy** |
| Manual re-eval | Force `pending` |
| Relation `delete` | `DELETE` eval row |
| Cron success | `done` + fields + hash + revision + model |
| Cron failure | `failed`, `attempt_count++`; if `< 3` → back to `pending` after short delay (or immediate requeue with higher `queued_at`) |

---

## Impact

### Observable vs internal

| Change | Observable |
|--------|------------|
| Kanban chip score / tooltip / re-eval (entry + Screening) | Yes — recruiters |
| New cron + API usage / cost | Ops |
| Settings › AI Prompts new action in catalog | Admin |
| Relation table schema | **No** change |
| Phase A/B import timing | Unchanged (enqueue only) |

### Code being added (indicative)

| Path | Role |
|------|------|
| `migrations/Users/mYYMMDD_000001_ai_fit_eval.php` | Table + seed prompt + cron register |
| `src/Ai/Prompt/ActionRegistry.php` | `MAIL_IMPROVE` unchanged; add `FIT_EVAL` |
| `src/Ai/Recruitment/FitEvalService.php` | build payload, hash, call OpenAI, parse JSON |
| `src/Ai/Recruitment/FitEvalQueue.php` | enqueue / claim / invalidate / deleteForRelation |
| `src/Modules/ProjektyRekrutacyjne/Cron/FitEvalTask.php` | phase C runner |
| Hook after CV-apply bind **and** after successful manual membership (`PPL_MANUALLY_ADDED`) | enqueue |
| Candidate / project save handlers (narrow field watch) | invalidation |
| `GetRelatedMembers::delete` | delete eval |
| Kanban tpl/CSS/JS (+ minify) | badge + tooltip + re-eval Ajax |
| `…/Actions/FitEvalAjax.php` (or ProjektyRekrutacyjne action) | manual re-eval + fetch breakdown |
| `languages/*/ProjektyRekrutacyjne.json` (+ Candidates if needed) | labels |
| `tests/ai_fit_eval_smoke.php` | hash + JSON schema + queue transitions |
| `.cursor/rules/ai.mdc` | document action + table |

### Code being modified

| Path | Change |
|------|--------|
| `ActionRegistry` | new action + placeholders |
| `docker/cron/crontab` | FitEval cron line (`gosu www-data`, flock) |
| `RecruitmentProjectKanban.tpl` / kanban JS/CSS | badge |
| `GetRelatedMembers::delete` | cleanup |
| Phase B bind path + manual-add (`createMembership` / `AddManualCandidatesAjax` success) | enqueue after successful link |

### Code being deleted

**None** (greenfield feature).

### Call sites / search verification

After implementation, grep must show:

- `recruitment.fit_eval` only via registry + seed + service  
- No direct `s_yf_ai_prompts` reads outside resolver  
- No second OpenAI client  

### DB

Additive only: `u_yf_ai_fit_eval` + one `s_yf_ai_prompts` seed row. No changes to relation membership table.

### Cron

| Service label | Handler | Interval |
|---------------|---------|----------|
| `LBL_SCHEDULED_AI_FIT_EVAL` (name TBD in migration) | `FitEvalTask` | `* * * * *` (batch 5) |

After crontab change: `docker compose up -d cron` (supercronic).

### Module metadata

No new CRM module. Optional: no `vtiger_field` on relation (score is not a Vtiger relation field — read via service/JOIN).

### Language

`en_us` + `pl_pl` for Screening strings + cron label + Ajax errors.

### External

OpenAI Chat Completions; logged via existing `AiRequestLogger` with `action=recruitment.fit_eval`.

### Sync / prod

`scripts/sync-from-prod` / promote docs: add `u_yf_ai_fit_eval` when promoting recruitment data (follow-up note in sync README if needed). Empty table on fresh migrate is fine.

---

## Data migration

1. `CREATE TABLE` as above (idempotent: skip if exists).  
2. Seed `s_yf_ai_prompts` for `recruitment.fit_eval` / `userid IS NULL` if missing.  
3. Register cron in `vtiger_cron_task` (pattern: CV import split migration).  
4. **No** backfill of existing relations (D4).

**Rollback:** `DROP TABLE u_yf_ai_fit_eval`; delete seed prompt row; unregister cron; revert code. Additive — no restore-from-backup required for schema.

**Existing non-conforming rows:** N/A (new table).

---

## Implementation plan

1. **Migration** — table + seed prompt + cron task row.  
2. **Registry** — `ActionRegistry::FIT_EVAL` + placeholders.  
3. **Queue + service** — `FitEvalQueue`, `FitEvalService` (hash, claim, OpenAI, JSON validate, `session_write_close` N/A in CLI cron).  
4. **Enqueue** — after successful link with `PPL_APPLIED` or `PPL_MANUALLY_ADDED` when `cv_text` known (single helper from apply + manual-add paths).  
5. **Invalidation** — candidate `cv_text` save; project requirement fields save; relation delete.  
6. **Cron** — `FitEvalTask` + crontab; update `docker-commands.mdc` one-liner.  
7. **Ajax + minimal UI** — badge, tooltip/summary, re-eval.  
8. **i18n** EN+PL.  
9. **Smoke test** + update `ai.mdc`.  
10. Clear Smarty cache; no container restart except **cron** after crontab.

Each step leaves the app runnable (cron no-ops if queue empty).

---

## Testing

### Smoke

1. Configure Provider key + fast model (`gpt-4.1-nano`).  
2. Ensure prompt `recruitment.fit_eval` active.  
3. Import/apply **or** kanban manual-add candidate with CV → row `pending` in `u_yf_ai_fit_eval`.  
4. Run cron C manually → `done`, score 0–100, JSON schema 1, `ai.log` entry.  
5. Entry (`PPL_MANUALLY_ADDED`) and Screening (`PPL_APPLIED`) chips show score when `done`; tooltip shows summary.  
6. Empty `cv_text` path → `skipped`.  
7. Re-eval button → `pending` → `done` again.  
8. Delete relation → eval row gone.  
9. Change `needed_skills` on project → evals for that project `pending`.  
10. Duplicate cron tick while `running` does not double-charge (claim atomic).

### Regression

- CV import A/B still completes; phase B time not dominated by OpenAI.  
- Application received mail still sends on `PPL_APPLIED`.  
- Kanban drag / reject / accept unchanged.  
- Mail improve wand unchanged.  
- Boolean CV skill search unchanged.

### Logs

- `cache/logs/cron.log` — FitEval task  
- `cache/logs/ai.log` — `recruitment.fit_eval`  
- `cache/logs/system.log` — no API key; errors only  

### Data checks

```sql
SELECT eval_status, COUNT(*) FROM u_yf_ai_fit_eval GROUP BY eval_status;
SELECT * FROM u_yf_ai_fit_eval WHERE eval_status='done' AND (fit_score IS NULL OR fit_score > 100);
-- orphans:
SELECT e.* FROM u_yf_ai_fit_eval e
LEFT JOIN u_yf_projekty_rekrutacyjne_relations_members_entity r
  ON r.crmid=e.project_id AND r.relcrmid=e.candidate_id
WHERE r.crmid IS NULL;
```

### Automated

`tests/ai_fit_eval_smoke.php` — hash stability, JSON accept/reject, enqueue upsert, no live OpenAI.

---

## Rollback plan

1. Revert git commit / redeploy previous tree.  
2. `DROP TABLE u_yf_ai_fit_eval`; remove prompt seed; disable/delete cron task.  
3. Remove crontab line + `docker compose up -d cron`.  
4. Downtime: none required. Data loss: only AI eval rows (recomputable).

---

## Edge cases

| Case | Handling |
|------|----------|
| Candidate linked to many projects | Independent eval rows |
| Re-apply same project (relation already exists) | `createLink` false → no duplicate membership; optional: if CV updated later, invalidation handles |
| OpenAI timeout | `failed` / retry `< 3` |
| Model returns markdown fences | Strip once; if still invalid JSON → `failed` |
| Partial migration re-run | Idempotent CREATE / seed IF NOT EXISTS |
| Project without skills/requirements | Still evaluate on title + empty skills; model may return low score + gaps — OK |
| Privileges | Re-eval Ajax checks project/candidate access like other recruitment Ajax |
| Stuck `running` (killed worker) | Cron recovery: `running` older than e.g. 15 min → `pending` |

---

## Decision rationale & tradeoffs

| Choice | Why | Rejected |
|--------|-----|----------|
| Side table vs relation columns | Fat JSON + queue lifecycle off hot membership row | Columns on `…_relations_members_entity` |
| Async cron vs inline in B | B must stay fast; OpenAI 2–20 s | Call OpenAI in phase B |
| JSON schema vs prose | Sortable score + trustworthy breakdown | Free text only |
| Assist only | Screening reject reasons stay human | Auto-reject on threshold |
| Eager CV/project invalidation | Score must track real inputs | Stale scores after CV replace |
| Lazy prompt/model invalidation | Avoid surprise API cost spike | Mass requeue on Settings save |

---

## Risks

| Risk | Severity | Mitigation |
|------|----------|------------|
| API cost on high apply volume | Med | Batch 5/min; cheap model; lazy prompt requeue; no backfill |
| Hallucinated “met” skills | Med | Evidence strings + human reject path; never auto-reject |
| Stale `running` rows | Low | Timeout reclaim |
| Kanban query cost JOIN | Low | Index `(project_id, fit_score)`; LEFT JOIN only Screening |
| Prompt quality | Med | Iterate seed in Settings without code deploy |
| `gpt-5-nano` latency | Low | Document; recommend `gpt-4.1-nano` in Provider (`ai.mdc`) |

---

## Deliverables checklist

1. Impact (+ no deletions)  
2. Schema + seed + cron migration  
3. Implementation steps (above)  
4. Testing checklist  
5. Rollback plan  
6. Rationale  
7. Risks  
8. **Q1–Q16 confirmed** → Status **Ready for implementation**  

---

## Appendix — seed prompt intent (draft English; store PL or EN in DB as product prefers)

Admin-editable body should instruct: compare CV to must-have / nice-to-have skills and requirements; return JSON schema 1 only; be conservative on `met`; write `summary`/`evidence`/`gaps`/`risks` in Polish; do not invent employers or skills absent from CV.

Exact seed text finalized at implementation (EN canonical in migration + PL label in Settings UI).
