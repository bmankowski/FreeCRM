# FreeCRM Privilege System Overview

This document summarizes how access control works today, explains where privilege data lives, highlights the major weak spots, and sketches a pragmatic refactor plan that keeps the platform stable while modernising the internals.

---

## 1. Current Architecture At A Glance

- **Runtime entry point** – most permission checks call `\App\Privilege::isPermitted()` directly; legacy code still invokes `\App\Utils\UserInfoUtil::isPermitted()` which wraps the same logic but returns `'yes'/'no'`.
- **Wrapper model** – `\App\Modules\Users\Models\Privileges` exposes helpers such as `getInstanceById()` and proxies to the static privilege API.
- **Query helpers** – `\App\PrivilegeQuery` offers two flavours of list-view filtering (`getAccessConditions()` returns SQL strings, `getConditions()` mutates a query builder).
- **Supporting services** – `\App\PrivilegeFile`, `\App\Modules\Users\Services\PrivilegeFileManager`, `\App\PrivilegeUtil`, `\App\PrivilegeUpdater`, and `\App\PrivilegeAdvanced` manage file generation, cache invalidation, supporting calculations, and advanced rules.
- **Module overrides** – many modules still ship custom `getListViewSecurityParameter()` implementations, duplicating core logic.

```45:104:src/Privilege.php
	public static function isPermitted($moduleName, $actionName = null, $record = false, $userId = false)
	{
		\App\Log::trace("Entering \App\Utils\UserInfoUtil::isPermitted($moduleName,$actionName,$record,$userId) method ...");
		// ... complex sequence of module, action, record, sharing and hierarchy checks ...
		\App\Log::trace('Exiting isPermitted method ... - ' . static::$isPermittedLevel);
		return $recordCheck;
	}
```

---

## 2. Privilege Storage Model

### 2.1 Generated PHP Artifacts

Privilege state is cached in flat PHP files under `user_privileges/`:

- `user_privileges/user_privileges_{id}.php` – core user snapshot: admin flag, profile & role assignments, action matrix, group membership, subordinate role map.
- `user_privileges/sharing_privileges_{id}.php` – sharing defaults and per-module read/write matrices, plus related-module sharing definitions.
- Additional global caches (`users.php`, `tabdata.php`, etc.) provide lookups for names, module metadata, and defaults.

These files are regenerated whenever roles/profiles/users change. The primary writer is `PrivilegeFileManager`.

```32:123:src/Modules/Users/Services/PrivilegeFileManager.php
    public static function createUserPrivilegesFile($userId): bool
    {
        $handle = @fopen(ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'user_privileges/user_privileges_' . $userId . '.php', "w+");
        // ... collect role/profile/group data and dump PHP arrays ...
        fputs($handle, $newbuf);
        fclose($handle);
        PrivilegeFile::createUserPrivilegesFile($userId);
        Privileges::clearCache($userId);
        Record::clearCache($userId);
        return true;
    }
```

When user data is requested at runtime:

1. `\App\Modules\Users\Models\Privileges::getPrivilegesFile()` `require`s `user_privileges_{id}.php`.
2. That method repackages the arrays into a model, supplementing with freshly computed data (e.g. subordinate users).
3. `\App\Privilege::getSharingFile()` `require`s the matching sharing file and memoises it.

### 2.2 Temp Tables Loaded From Files

After sharing file generation, `populateSharingtmptables()` seeds `vtiger_tmp_*` tables for faster lookups. List-view filters (`PrivilegeQuery`) tap both the file arrays and the temp tables to decide record visibility.

---

## 3. Execution Flow

1. Caller invokes `isPermitted()` (directly or via the `Privileges` model).
2. Method resolves the effective user (`$userId` parameter or current session), then loads privilege and sharing data from cached files.
3. Checks are performed in layers:
   - module activation & admin short-circuit
   - profile tab/action rules
   - global permissions (view-all/edit-all)
   - record-level checks (ownership, private flag, shared owners, role hierarchy, related-record escalation)
   - organisation sharing matrix (default sharing + explicit rules)
4. A static marker `Privilege::$isPermittedLevel` captures the first matching reason; the boolean result is returned to the caller.

Parallel legacy paths (`UserInfoUtil::isPermitted()`, module-specific `getListViewSecurityParameter()`) run similar logic but with duplicated code and different return types.

---

## 4. Weaknesses & Inconsistencies

| Category | Symptoms | Impact |
|----------|----------|--------|
| **Data storage** | File-per-user cache (`user_privileges_*.php`, `sharing_privileges_*.php`) with ad-hoc serialization helpers, writable filesystem requirement | Difficult horizontal scaling, race conditions during regeneration, expensive I/O, brittle deployments |
| **API surface** | Three active `isPermitted()` flavors (`Privilege`, `UserInfoUtil`, wrapper in `Privileges`), and 20+ bespoke `getListViewSecurityParameter()` copies | Inconsistent return values (`bool` vs `'yes'/'no'`), unclear preferred call site, duplicated maintenance |
| **Static globals** | Almost every helper is static (Privilege, PrivilegeQuery, PrivilegeUtil, PrivilegeFileManager) | Hard to test/migrate, hidden dependencies, global mutable state leaks |
| **Mixed paradigms** | Blend of procedural code, Active Record, and OO wrappers without clear boundaries | Onboarding complexity, unpredictable side effects, unclear ownership of responsibilities |
| **Traceability** | `$isPermittedLevel` is a shared static property | Concurrent checks can overwrite each other; no structured audit trail |
| **Module overrides** | Module classes override list-view security logic with `require()` calls into privilege files | Security drift between modules, higher regression risk, impeded refactoring |
| **Advanced rules** | Sharing, related-record escalation, and advanced permissions interleave within the monolithic `isPermitted()` | Hard to reason about precedence, fragile to change, difficult to profile |

---

## 5. Design Flaws In Detail

1. **File-based privilege cache** – storing executable PHP for every user couples permission state to local disk, complicates deployments (e.g., multi-webhead setups or container orchestration) and introduces concurrency hazards.
2. **Monolithic permission evaluator** – the current `isPermitted()` method combines module/action, ownership, sharing, private flag, hierarchy, advanced rules, and logging in one place. Any change risks regressions elsewhere, and there is no fine-grained telemetry.
3. **SQL string vs query builder duality** – `PrivilegeQuery` ships both `getAccessConditions()` (string) and `getConditions()` (builder) with near-identical logic, yet different call sites use each variant. They can drift and complicate testing.
4. **Legacy return values** – returning `'yes'`/`'no'` from the legacy API forces callers to mix string comparisons with boolean logic, increasing the chance of subtle bugs during migrations.
5. **Privilege file writers** – both `PrivilegeFile` and `PrivilegeFileManager` generate user files, leading to unclear responsibility and duplicated serialization helpers.

---

## 6. Refactoring Roadmap (Phased)

The plan below keeps the system working while eliminating the worst technical debt. Each phase can be shipped independently; earlier phases lay the groundwork for later ones.

### Phase 0 – Baseline & Guardrails (1–2 sprints)

- Introduce central documentation (this file) and acceptance criteria for permission behaviour.
- Add smoke tests that exercise representative permission scenarios (admin, regular user, shared owner, hierarchy access).
- Instrument current `isPermitted()` to emit structured debug logs behind a feature flag for safe benchmarking.

### Phase 1 – API Unification & Deprecation (2–3 sprints)

- Wrap `\App\Privilege::isPermitted()` behind a new service interface (e.g., `PrivilegeServiceInterface`) that always returns a `PermissionResult` value object while still delegating to legacy code.
- Add deprecation notices to `\App\Utils\UserInfoUtil::isPermitted()` and module-level `getListViewSecurityParameter()`, and update high-traffic modules to the unified API.
- Consolidate privilege file writers (`PrivilegeFile` vs `PrivilegeFileManager`) into a single code path to remove duplicated serializers.

**Exit criteria:** all new code uses the service wrapper; static helper calls are limited to adapter implementations.

### Phase 2 – Storage Modernisation (3–4 sprints)

- Implement a repository abstraction for privilege persistence (initially backed by the existing files).
- Introduce database (or Redis) storage for privilege snapshots with migration tooling that can rebuild caches.
- Switch runtime reads to the repository; keep file generation as a fallback during the transition.
- Remove temp-table loaders’ tight coupling to PHP globals by reading from the repository payload.

**Exit criteria:** privilege reads no longer require direct file access; production can run in a read-only filesystem mode.

### Phase 3 – Permission Engine Refactor (4–6 sprints)

- Decompose `isPermitted()` into a chain of dedicated checkers (admin, module/action, global permission, record ownership, sharing, advanced rules) orchestrated by the privilege service.
- Replace `$isPermittedLevel` with structured `PermissionResult` metadata (reason code, checker name, supporting context) and expose it to callers/logging.
- Unify `PrivilegeQuery` by making `getConditions()` the single implementation; keep the string API as a thin adapter that calls into the builder.
- Provide a compatibility layer so legacy callers continue to receive boolean or SQL strings until they are migrated.

**Exit criteria:** checker components are unit-tested, and the legacy monolith is reduced to thin adapters.

### Phase 4 – Cleanup & Enhancements (ongoing)

- Remove deprecated entry points once call sites are migrated.
- Enforce dependency injection for privilege-related services; register them in the application container.
- Add real-time auditing hooks (e.g., PSR-14 events) that publish permission decisions for monitoring and security review.
- Explore pre-computation or caching improvements (e.g., per-module compiled ACLs) once the new architecture is stable.

**Exit criteria:** only the modern service-based API remains; auditing and observability are in place.

---

## 7. Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Behaviour regressions during refactor | Maintain extensive regression tests; roll out new checkers behind feature flags; use canary users/modules |
| Migration complexity for storage layer | Provide dual-read mode (new store first, fallback to files); ship admin tooling to rebuild caches on demand |
| Performance hits while introducing abstractions | Benchmark at each phase; cache repository outputs; maintain lightweight DTOs |
| Team adoption of new service API | Document migration steps, provide codemods for common patterns, and enforce through code review |

---

## 8. Next Steps

1. Implement Phase 0 tests/logging to get current baselines.
2. Draft the service wrapper interface and migrate a non-critical module to validate patterns.
3. Design the privilege repository schema (table, JSON payload, TTL strategy) and prepare migration scripts.
4. Schedule a final audit of module-level overrides to prioritise migration order.

Delivering the roadmap above will give FreeCRM a privilege system that is easier to reason about, scalable across deployment topologies, and ready for new features without risking regressions.

