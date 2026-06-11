# Users & permissions refactor — simplified plan

This is the **pragmatic, low-ceremony** version of `users-new-architecture.plan-en.md`.

Context that shapes every decision in this document:

- FreeCRM is a single-developer fork of YetiForce/Vtiger.
- It runs on **one** Docker host (`test.itconnect.pl`), MariaDB on `127.0.0.1:3306`. No Redis, no multi-webhead, no Kubernetes.
- ~170 source files call `\App\Security\Privilege::isPermitted()` today.
- `Privilege::isPermitted()` is already broken into nine `checkXxx()` step methods returning reason codes via the static `$isPermittedLevel`.
- `\App\User\CurrentUser` (51 lines) and `$request->getUser()` / `$request->getUserId()` already exist as the "current user" accessors.
- `\App\Modules\Users\Services\PrivilegeFileManager` already exists as the single file writer.

The current state is documented in [users.md](users.md) and [privileges.md](privileges.md). This document is the **change roadmap**, not a re-description of the current state.

---

## 1. Goals (only the ones that pay rent)

1. One official boolean entry point for "can this user do X?": `\App\Security\Privilege::isPermitted()`.
2. One way to read "the current user" in any request path.
3. One owner of file/cache invalidation after Settings changes.
4. Remove the `'yes'/'no'` legacy API.
5. Remove module-by-module copies of list-view security SQL.
6. Stop relying on the global static `$isPermittedLevel` to know *why* a check failed.

Non-goals (explicitly out of scope to avoid scope creep):

- DDD / Hexagonal / Ports & Adapters layering.
- New `Domain/Application/Infrastructure/Checker` directories.
- New value objects (`UserId`, `RoleId`, `UserContext`, `PrivilegeSnapshot`, …) unless one specific change requires them.
- Replacing `user_privileges/*.php` files with a DB+Redis snapshot store.
- A `PermissionService` interface that just forwards to `Privilege::isPermitted()`.
- Audit logger ports, snapshot event tables, async rebuild queues.
- Renaming `App\` to `FreeCRM\`.

If a future requirement (multi-host deploy, audit compliance, etc.) actually materialises, we revisit. Until then: **YAGNI**.

---

## 2. Design principles

1. **Edit existing classes; do not introduce new layers.** The pain is inside `Privilege`, `Privileges`, `PrivilegeQuery`, `Record`, and `Users` — fix them in place.
2. **One concept, one place.** Pick the current best accessor for each concept and migrate everything else to it.
3. **No new global statics.** Replace `$isPermittedLevel` with a return value.
4. **Keep `bool` as the canonical permission type.** Reason codes are a side channel for debugging, not a new return type.
5. **No new files unless deleting an old one.** Net file count should go down or stay flat.
6. **Web-UI verified at every step** (per `testing-requirements.mdc`).

---

## 3. Concrete work items

Seven items, ordered so each one is independently shippable, reversible, and observable in the Web UI.

### Item 1 — Replace `Privilege::$isPermittedLevel` with a return value

**Problem.** `Privilege::isPermitted()` returns only `bool` and writes the reason into the *static class property* `Privilege::$isPermittedLevel`. Concurrent or nested checks overwrite each other, and there is no clean way to surface "why".

**Change.**

- Add `Privilege::lastResult(): ?array` returning `['allowed' => bool, 'reason' => string]` for the **current call frame** (thread-local style — use a return-by-reference helper or an internal stack, not a global).
- Internally, every `checkXxx()` step already returns the reason; just bubble it up.
- Keep `isPermitted()` signature unchanged so the 170 call sites do not move.
- Mark `Privilege::$isPermittedLevel` `@deprecated` but leave the property in place for one release; have `isPermitted()` keep writing it for backward compatibility.

**Done when.**

- New code reads the reason via `Privilege::lastResult()`.
- `Privilege::$isPermittedLevel` has zero non-legacy readers (`rg` confirms).

**Effort:** ~1 day. Zero call-site changes outside `Privilege.php`.

---

### Item 2 — Delete the `'yes'/'no'` API

**Problem.** `\App\Utils\UserInfoUtil::isPermitted()` returns `'yes'`/`'no'`; callers mix string comparisons with booleans. A migration list already exists.

**Change.**

- Replace every call to `UserInfoUtil::isPermitted()` with `\App\Security\Privilege::isPermitted()` (`bool`).
- Drive the migration from `documentation/yes-no-migration-list.md`.
- After the last call site is converted, **delete** `UserInfoUtil::isPermitted()` (do not leave a thin wrapper).

**Done when.**

- `rg "UserInfoUtil::isPermitted"` returns no hits in `src/`.

**Effort:** mechanical; group by module in 4–6 small PRs.

---

### Item 3 — Hide `baseUserId` behind named session methods

**Problem.** Code reads `Vtiger_Session` keys directly (`baseUserId`, `authenticated_user_id`); impersonation logic is scattered.

**Change.**

- Add to `\App\Http\Vtiger_Session`:
  - `getEffectiveUserId(): ?int` — what the app sees (today's `authenticated_user_id`).
  - `getRealUserId(): ?int` — the operator behind impersonation (today's `baseUserId ?? authenticated_user_id`).
  - `isImpersonated(): bool`.
- Migrate every direct `baseUserId` / `authenticated_user_id` read to those methods.
- `Record::getCurrentUserRealId()` becomes a one-liner that delegates.

**Done when.**

- `rg "baseUserId"` only matches the new accessors and the impersonation writer.

**Effort:** ~1 day.

---

### Item 4 — Standardise "get current user"

**Problem.** There are at least four ways: `$request->getUser()`, `\App\User\CurrentUser::get()`, `Record::getCurrentUserModel()`, `Privileges::getCurrentUserPrivilegesModel()`.

**Decision.** Keep two, in this order of preference:

1. **In controllers/actions/views** — `$request->getUser()` / `$request->getUserId()`.
2. **Outside the request lifecycle (cron, CLI, handlers)** — `\App\User\CurrentUser::get()`.

**Change.**

- Mark `Record::getCurrentUserModel()` `@deprecated`.
- Already covered by `getting-rid-of-current-user-from-controllers.md`; finish that work module by module.
- Do **not** introduce `UserContext`, `CurrentUserProvider`, or `SessionStore` — `CurrentUser::get()` plus the request accessor already cover both cases.

**Done when.**

- `rg "Record::getCurrentUserModel"` returns no hits in controllers/actions/views.

**Effort:** ongoing; one module per PR.

---

### Item 5 — Centralise list-view security

**Problem.** ~20 modules ship custom `getListViewSecurityParameter()` implementations that duplicate the core sharing/role/owner SQL with slow drift between them.

**Change.**

- Move the canonical logic into `\App\Security\PrivilegeQuery` (`getAccessConditions()` already exists — make it the single source of truth).
- Delete each module's `getListViewSecurityParameter()` override **unless** the override implements a genuinely module-specific rule (Calendar, Documents, OSSMailView are the likely real exceptions — verify per module before deletion).
- For genuine exceptions, the module method should call `PrivilegeQuery::getAccessConditions()` and then append its extra clause, instead of re-implementing everything.

**Done when.**

- Number of `getListViewSecurityParameter` implementations in `src/Modules/` is reduced to the 2–3 modules with real custom rules.

**Effort:** medium; one module per PR, Web-UI verified.

---

### Item 6 — Single owner for ACL cache invalidation

**Problem.** Cache clearing is scattered (`Record::clearCache`, `Privileges::clearCache`, `PrivilegeFileManager::createUserPrivilegesFile`, ad-hoc `App\Cache\Cache` keys, `vtiger_tmp_*` repopulate).

**Change.**

- Add `\App\Modules\Users\Services\PrivilegeFileManager::invalidateUser(int $userId, string $reason): void` as the **only** entry point. It performs in order:
  1. regenerate `user_privileges_{id}.php` and `sharing_privileges_{id}.php`,
  2. clear `Record::clearCache($userId)`,
  3. clear `Privileges::clearCache($userId)`,
  4. clear the relevant `App\Cache\Cache` namespaces (`UserGroups`, `getRoleUsers`, `getUsers`, `getAccessibleUsers`),
  5. repopulate `vtiger_tmp_*` if sharing changed.
- Add `invalidateAll(string $reason)` for module install / global permission changes.
- Settings save handlers (`Settings\Roles\Models\Record`, `Settings\Profiles\Models\Record`, `Settings\Groups\Models\Record`, `Settings\GlobalPermission\Models\Record`, `Users\Models\Module::saveRecord`, `Users\Actions\SaveAjax`) call **only** `invalidateUser` / `invalidateAll`.
- Reason is a free-form string for the log; no enum, no event table.

**Done when.**

- Every `clearCache` call outside `PrivilegeFileManager` is gone or marked legacy-only.

**Effort:** ~2 days.

---

### Item 7 — Decide once: keep files, or migrate snapshots to DB

**Recommendation:** **keep `user_privileges/*.php` files.** They are fast, proven, deployed on the only target host, and writable via the existing storage volume. Migrating to a DB-JSON snapshot store costs a schema, a writer, a cache layer, an invalidation path, and a fallback — for no observable user benefit on a single-host deployment.

**Trigger to revisit:** if and only if we add a second app container without a shared `storage/` volume. At that point, and only then, introduce a `PrivilegeSnapshotRepository` with two backends (file, DB) behind a config flag. Do **not** build it speculatively.

**Done when.**

- This document records the decision; no further action.

---

## 4. What we are deliberately *not* doing

For traceability against the long plan, here is the explicit "rejected" list:

| Long plan idea | Rejected because |
|----------------|------------------|
| Fallbacks | Never needed |
| `Domain/Application/Infrastructure` layer split | Cost of ~30 new classes > benefit for a single-dev codebase |
| `PermissionService` façade | `Privilege::isPermitted()` is already the façade; renaming it adds no value |
| `PermissionRequest` / `PermissionResult` / `UserContext` / `UserId` / `RoleId` value objects | Plain primitives + one optional return-array suffice; VOs would need adapters at all 170 call sites |
| 14-class `Checker` pipeline | `Privilege::isPermitted()` is already split into 9 step methods inside one class; class-per-step is ceremony |
| `PrivilegeSnapshotRepository` + DB JSON table + checksum + event table | No multi-host requirement; files already work |
| `CacheStore` / `SessionStore` / `PermissionAuditLogger` ports | Premature abstraction; no second implementation in sight |
| Async snapshot rebuild via queue/cron | Synchronous regeneration after Settings save is already fast enough |
| Redis | Not in the stack, not needed |
| Renaming `App\` → `FreeCRM\` namespace | Orthogonal to ACL refactor; tackle separately if ever |
| `class_alias()` adapters | Forbidden by `general-guidelines.mdc` anyway |

---

## 5. Order of execution and effort estimate

| # | Item | Effort | Risk | Prerequisite |
|---|------|--------|------|--------------|
| 1 | Reason-code return value | 1 day | low | none |
| 3 | `baseUserId` accessors | 1 day | low | none |
| 6 | Single invalidation owner | 2 days | medium | none |
| 2 | Delete `yes/no` API | 4–6 small PRs | low | none |
| 4 | Standardise current-user accessor | ongoing | low | continues existing plan |
| 5 | Centralise list-view security | 1 PR per module | medium | item 1 helps debug regressions |
| 7 | Decide on file-vs-DB snapshots | none (decision recorded) | n/a | n/a |

Total realistic timeline: **2–3 calendar weeks of part-time work**, vs the multi-month estimate implied by the long plan.

---

## 6. Acceptance per item

Each item must pass before the next is started:

1. Web UI smoke test (login → dashboard → open module list → open record → change profile in Settings → re-check access).
2. `cache/logs/system.log` shows no new errors after the change (per `error-checking.mdc`).
3. Regression on the canonical scenarios:
   - admin sees everything,
   - normal user sees own records,
   - shared owner sees shared record,
   - parent-role user sees subordinate records,
   - deactivated user cannot log in,
   - profile change is visible after the next request.

CLI checks are supplementary; the Web-UI run is mandatory (per `testing-requirements.mdc`).

---

## 7. Open questions actually worth asking

Only these, and they are answerable in one line each:

1. **Do we ever need to run a second app container without sharing `storage/`?** If no → item 7 stays "keep files" forever.
Answer: No, we don't need.
2. **Do we want `Privilege::lastResult()` to log reasons in production, or only behind a debug flag?**
Answer: No.
3. **Which modules in item 5 have a real custom rule vs duplicated boilerplate?** 
Answer: I don't know.
---
