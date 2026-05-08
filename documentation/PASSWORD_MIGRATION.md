# FreeCRM Password Hashing Migration (Argon2id + Pepper)

## Summary

FreeCRM has dropped the legacy Vtiger-era `crypt()`-based password hashing
(MD5/BLOWFISH/PHP5.3MD5 selected via the `crypt_type` column) and replaced it
with **Argon2id** layered on top of an **HMAC-SHA-256 pre-hash pepper**.

This is a one-way break:

- Every existing `vtiger_users.user_password` value (e.g. `$1$ad000000$...`)
  no longer authenticates. There is **no fallback** by design.
- Every user must have their password reset before they can log in.
- The `confirm_password` and `crypt_type` columns are dropped.

If you operate an existing FreeCRM install, read the **Upgrade procedure**
section before deploying.

## What changed

| Concern | Before | After |
| --- | --- | --- |
| Algorithm | `crypt()` MD5 / BLOWFISH / PHP5.3MD5 | **Argon2id** |
| Salt | First 2 chars of `user_name` | Per-hash random (built into Argon2id output) |
| Pepper | None | HMAC-SHA-256 with `$user_password_pepper` |
| Algorithm selector | `vtiger_users.crypt_type` column | None - Argon2id hashes are self-describing |
| Password mirror | `vtiger_users.confirm_password` column | None - confirm_password is now a UI-only retype check |
| Config | `PASSWORD_CRYPT_TYPE` in `config/modules/Users.php` | `PASSWORD_ARGON2_*` knobs (memory/time/threads) |
| Verify | string compare of recomputed hash | `password_verify()` (constant time) |
| Cost upgrades | Manual reset | `password_needs_rehash` on successful login |

The hash/verify primitives are centralized in
[`src/Security/PasswordCrypto.php`](../src/Security/PasswordCrypto.php). All
call sites (login, password change, installer, reset CLI) route through it.
No raw `password_hash()` / `password_verify()` calls live elsewhere.

## New configuration

### `$user_password_pepper` (in `config/config.inc.php`)

A per-install secret used as the HMAC-SHA-256 key applied to every plaintext
password before Argon2id hashing.

- The installer auto-generates a 256-bit value (64 hex chars) via
  `bin2hex(random_bytes(32))`.
- For existing installs that upgrade, FreeCRM will auto-generate one on the
  first request and write it into `config/config.inc.php` (atomic
  temp-file + rename).
- If `config/config.inc.php` is not writable, FreeCRM **refuses to serve any
  request that needs to hash or verify a password**, with a clear error
  pointing you here. The fix is to add the line manually:

  ```php
  $user_password_pepper = '<64 hex chars>';
  ```

### `config/modules/Users.php`

```php
'PASSWORD_ARGON2_MEMORY_COST' => 65536, // KiB; OWASP minimum 47104
'PASSWORD_ARGON2_TIME_COST'   => 4,     // iterations
'PASSWORD_ARGON2_THREADS'     => 1,     // parallelism
```

Tune downward only on memory-constrained hosts. Bumping any value
transparently rehashes hashes on the user's next successful login (via
`password_needs_rehash`).

## Operator-facing risks

> **Pepper rotation = total password reset.**
> Changing `$user_password_pepper` invalidates every existing login hash. The
> separation from `$application_unique_key` exists so you can rotate session,
> CSRF, and integration secrets without touching auth.

> **Pepper backup is mandatory.**
> Treat `config/config.inc.php` as part of the auth backup story alongside the
> `vtiger_users` table. Lose the pepper, lose every password.

> **Argon2id memory.**
> Default 64 MiB per concurrent verify can stress small hosts. OWASP's 2024
> minimum is `memory_cost=47104, time_cost=1, threads=1`; tune via
> `config/modules/Users.php` if necessary.

## Upgrade procedure (existing installs)

1. **Back up `config/config.inc.php`** and the `vtiger_users` table.
2. Pull this revision and deploy the code.
3. Run the schema migration that drops the legacy columns:
   ```bash
   ./vendor/bin/yii migrate --migrationPath=migrations/Users/
   ```
   This drops `vtiger_users.confirm_password`, `vtiger_users.crypt_type`,
   the `user_user_password_idx` index, and the `confirm_password` row from
   `vtiger_field`.
4. Visit any auth-protected page once. FreeCRM will detect that
   `$user_password_pepper` is missing/placeholder, generate one, and append
   it to `config/config.inc.php`. **Verify the line exists and back up the
   file before continuing.**
5. Reset the admin password:
   ```bash
   php tests/reset_user_password.php admin '<new-admin-password>'
   ```
6. Log in as `admin` and reset every other user's password from the UI, or
   use the CLI tool per user:
   ```bash
   php tests/reset_user_password.php <user_name> '<new-password>'
   ```

There is no shortcut. Legacy `$1$...` hashes cannot verify against
`password_verify()` and will not be accepted.

## Fresh installs

The web installer handles everything end-to-end:

- `config/config.template.php` ships a `$user_password_pepper` placeholder
  that `ConfigFileUtils` substitutes with a 256-bit CSPRNG value.
- `InitSchema::setDefaultUsersAccess` writes the admin `user_password` as an
  Argon2id hash directly.
- `scheme.sql` / `Base4.php` / `data.sql` no longer create `confirm_password`
  or `crypt_type`.

## Compatibility with YetiForce

Argon2id hashes (and bcrypt) are self-describing PHP password strings, so a
hash from FreeCRM is bit-for-bit `password_verify`-compatible with any other
PHP application *as long as both apply the same pepper, or neither applies a
pepper*. FreeCRM applies a pepper by default; YetiForce currently does not.
That means:

- Importing a YetiForce user row's `user_password` into a FreeCRM `vtiger_users`
  row will **not** authenticate, because YetiForce hashes are not peppered with
  FreeCRM's `$user_password_pepper`.
- Same in the reverse direction.

If you need cross-fork hash interoperability, run with no pepper (configure
`$user_password_pepper` identically across both, or hack the helper to skip
HMAC). The FreeCRM default is to keep the pepper because it is the safer
posture for a standalone deployment.

## Recovery

If you lose `$user_password_pepper`:

- All login hashes are dead. There is no decryption path.
- Restore `config/config.inc.php` from backup, OR
- Reset every user via `tests/reset_user_password.php` on a fresh pepper.

## Files changed in this migration

- New: [`src/Security/PasswordCrypto.php`](../src/Security/PasswordCrypto.php)
- New: [`migrations/Users/m260508_000001_drop_legacy_password_columns.php`](../migrations/Users/m260508_000001_drop_legacy_password_columns.php)
- New: [`tests/reset_user_password.php`](../tests/reset_user_password.php)
- Updated: [`src/Modules/Users/Models/Record.php`](../src/Modules/Users/Models/Record.php)
- Updated: [`src/Modules/Users/Users.php`](../src/Modules/Users/Users.php)
- Updated: [`src/Modules/Install/Models/InitSchema.php`](../src/Modules/Install/Models/InitSchema.php)
- Updated: [`src/Modules/Install/Models/ConfigFileUtils.php`](../src/Modules/Install/Models/ConfigFileUtils.php)
- Updated: [`src/Modules/Install/Views/Index.php`](../src/Modules/Install/Views/Index.php)
- Updated: [`src/Modules/Install/install_schema/scheme.sql`](../src/Modules/Install/install_schema/scheme.sql)
- Updated: [`src/Modules/Install/install_schema/Base4.php`](../src/Modules/Install/install_schema/Base4.php)
- Updated: [`src/Modules/Install/install_schema/Base2.php`](../src/Modules/Install/install_schema/Base2.php)
- Updated: [`src/Modules/Install/install_schema/data.sql`](../src/Modules/Install/install_schema/data.sql)
- Updated: [`config/config.template.php`](../config/config.template.php)
- Updated: [`config/modules/Users.php`](../config/modules/Users.php)
