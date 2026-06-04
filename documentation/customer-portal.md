# Customer Portal (FreeCRM)

This document describes the **customer portal** in FreeCRM: a separate web application for contacts (and related accounts) to access CRM data self-service. It is **not** the internal **Portal** module (“Our sites” / bookmark list in `vtiger_portal`).

## Overview

| Layer | Role |
|--------|------|
| **Portal frontend** | Separate app (historically [YetiPortal](https://gitdeveloper.yetiforce.com/portal/)). Not in this repository and not in `docker-compose.yml`. |
| **FreeCRM** | Backend: portal user accounts, API, permissions, email templates, workflows, `$PORTAL_URL`. |

Typical flow: a contact logs into the portal UI → the portal calls FreeCRM’s webservice API → CRM enforces scope via portal user `type` and linked Contact/Account.

## Configuration

### `PORTAL_URL`

In `config/config.inc.php` (and `config/config.template.php`):

```php
$PORTAL_URL = 'https://portal.yetiforce.com';
```

Used for:

- Mail/text parser: `$(general : PortalUrl)$` → `TextParser::general('PortalUrl')`.
- Deep links to portal record views: `$(general : (__VtigerMeta__) portaldetailviewurl)` builds  
  `{PORTAL_URL}/index.php?module={Module}&action=index&{idField}={recordId}`  
  (HelpDesk uses `ticketid`, Faq `faqid`, Products `productid`, others `id`).

### Webservice service

In `config/api.php`, the modern API is enabled with:

```php
$enabledServices = [
    'webservice',
];
```

Entry point: `api/webservice.php` → `src/Api/webservice.php` → `App\Api\Webservice\Controller`.

Legacy vtiger `webservice.php` at repo root is a **different** stack (`operation=…`); portal integration uses the **YetiForce-style** API under `src/Api/Webservice/Portal/`.

### Database

Portal tables are created by `src/Modules/Install/install_schema/Webservice.php` and appear in the main schema as `w_yf_*` (prefix `w_#__` in code):

| Table | Purpose |
|--------|---------|
| `w_yf_servers` | Registered portal app instances (name, password, `api_key`, `type`, `acceptable_url`, `status`) |
| `w_yf_portal_user` | Portal login accounts |
| `w_yf_portal_session` | Session tokens after login |

`App\Db\Db::getInstance('webservice')` uses a dedicated config key if present; otherwise it **falls back to the main CRM database** (`src/Db/Db.php`). In the default Docker setup, portal tables live in the same MariaDB database as CRM.

## Admin setup (CRM UI)

Settings → **Integracja** / integration block (install data):

| Menu item | Module | Purpose |
|-----------|--------|---------|
| Web service - Applications | `Settings:WebserviceApps` | Create portal server row: name, URL, password, `api_key`, type `Portal` |
| Web service - users | `Settings:WebserviceUsers` | Manage `w_yf_portal_user` records |

Only type **`Portal`** is exposed in `WebserviceApps\Models\Module::getTypes()`.

### Portal user fields (`Settings:WebserviceUsers`)

Stored in `w_yf_portal_user`:

| Field | Meaning |
|--------|---------|
| `user_name` | Login (unique) |
| `password_t` | Password (plain text in DB; compared directly on login) |
| `password_h` | Reserved for hashed password (not used in current `Login` action) |
| `status` | Active/inactive |
| `crmid` | Linked **Contact** record |
| `user_id` | CRM **User** whose profile drives module/action permissions |
| `type` | Data visibility mode (see below) |
| `server_id` | Link to `w_yf_servers` |
| `language` | Portal session language |

### Permission `type` values

From `Settings\WebserviceUsers\Models\Record::getTypeValues()`:

| Value | Label key | Behaviour (API) |
|-------|-----------|------------------|
| 1 | `PLL_USER_PERMISSIONS` | Uses linked CRM user privileges; hierarchy API **not** available |
| 2 | `PLL_ACCOUNTS_RELATED_RECORDS` | Lists/details scoped to parent account/contact via related fields |
| 3 | `PLL_ACCOUNTS_RELATED_RECORDS_AND_LOWER_IN_HIERARCHY` | As 2, including lower accounts in hierarchy |
| 4 | `PLL_ACCOUNTS_RELATED_RECORDS_IN_HIERARCHY` | As 2, full account hierarchy |

Record list filtering: `Portal\BaseModule\RecordsList::getQueryByParentRecord()` uses `getParentCrmId()` (contact’s account parent chain). Hierarchy endpoint: `Portal\BaseModule\Hierarchy`.

## Contacts module

- Field **`portal`** on Contacts (`vtiger_customerdetails.portal`, uitype 56 checkbox) marks “portal user” intent.
- Block **`LBL_CUSTOMER_PORTAL_INFORMATION`** exists in install schema but is **hidden** on all views (`visible`, `create_view`, `edit_view`, `detail_view` = 0).
- JS (`public/layouts/basic/modules/Contacts/resources/Edit.js`, `Detail.js`) requires primary **email** when enabling `portal`.
- Default on create: `'portal' => 0` in `Contacts.php`.

Automatic portal account creation is **not** implemented in a dedicated Contacts save handler in `src/Modules/Contacts/`; admins typically create/sync users via **Settings → Web service - users**, or workflows.

### Workflow (install seed)

Workflow **“Send Customer Login Details”** (id 53) on Contacts: triggers when `portal` changes to enabled and email opt-out allows mail; uses template **“Customer Portal Login Details”** (id 44).

Email templates (also in install data):

- **Customer Portal Login Details** — login = contact email, password placeholder in template body.
- **Customer Portal - ForgotPassword** (`sys_name`: `YetiPortalForgotPassword`).
- **Notify Owner On new comment added to ticket from portal** — workflow task `HelpDeskNewCommentOwner` on ModComments.

## API architecture

### Two authentication layers

1. **Server (portal app → CRM)**  
   HTTP Basic Auth (`config/api.php` → `AUTH_METHOD` = `Basic`).  
   Validates against `w_yf_servers` (`name` + `pass`, `status` = 1).  
   Request must include header **`X-API-KEY`** matching `api_key` for that server (`Controller::preProcess()`).

2. **Portal end user**  
   After `Users/Login` (POST, no `X-TOKEN` required): returns **`token`**.  
   Subsequent calls send **`X-TOKEN`**; validated against `w_yf_portal_session` joined to `w_yf_portal_user`.  
   Session rows older than one day are purged on login.

Login sets CRM current user from portal row’s **`user_id`** for permission checks inside API actions.

### Routing

`Controller::getModuleClassName()` resolves:

- `App\Api\{ServerType}\{Module}\{Action}` — e.g. `App\Api\Portal\Users\Login`
- fallback `App\Api\{ServerType}\BaseModule\{Action}`
- fallback `App\Api\{ServerType}\BaseAction\{Action}`

`ServerType` comes from `w_yf_servers.type` (e.g. `Portal`). Source files live under `src/Api/Webservice/Portal/` with namespace `App\Api\Portal\…`.

### Implemented portal API actions

Discovered via `Portal\BaseAction\Methods` (GET lists available routes):

| Area | Class | Methods | Notes |
|------|--------|---------|--------|
| Users | `Users\Login` | POST | Returns token, labels, preferences from linked CRM user |
| Users | `Users\Logout` | PUT | Deletes session, updates `logout_time` |
| BaseAction | `BaseAction\Modules` | GET | Modules permitted for linked CRM user |
| BaseAction | `BaseAction\Methods` | GET | API method catalogue |
| BaseModule | `BaseModule\Record` | GET, POST, PUT, DELETE | CRUD with CRM record permissions |
| BaseModule | `RecordsList` | GET | List with parent scoping (unless type = 1) |
| BaseModule | `Fields` | GET | Field metadata |
| BaseModule | `Privileges` | GET | Standard actions for module |
| BaseModule | `Hierarchy` | GET | Account/related hierarchy (not for type 1) |

Optional headers (see `RecordsList`, `BaseAction`): `X-PARENT-ID`, `X-ROW-LIMIT`, `X-ROW-OFFSET`, `X-FIELDS`, `Accept-Language`.

Logs: `cache/logs/webservice.log`; debug: `WEBSERVICE_DEBUG` → `cache/logs/webserviceDebug.log`.

### Example request shape (conceptual)

```http
POST /api/webservice.php
Authorization: Basic {base64(server_name:server_pass)}
X-API-KEY: {api_key from w_yf_servers}
Content-Type: application/json

action=Login&module=Users
{ "userName": "...", "password": "...", "params": { "language": "en_us" } }
```

Then:

```http
GET /api/webservice.php?action=RecordsList&module=HelpDesk
X-API-KEY: ...
X-TOKEN: {token from login}
```

Exact query/body format follows `App\Api\Webservice\Core\Request` (JSON body for non-GET).

## HelpDesk integration

- Field **`from_portal`** on tickets (`vtiger_troubletickets`) marks tickets originating from the portal.
- Handler `HelpDesk_TicketRangeTime_Handler` clears `from_portal` on certain updates.
- Portal users can create/view tickets and comments subject to API permissions; owner notification uses workflow/email template above.

## Distinction from internal “Portal” module

| | Customer portal | Portal module (`module=Portal`) |
|--|-----------------|----------------------------------|
| Users | External contacts | Internal CRM users |
| Data | CRM records via API | `vtiger_portal` bookmarks (name + URL) |
| UI in repo | No | Yes (ListView, bookmarks) |

## FreeCRM / deployment status

- **Not bundled**: portal SPA must be deployed separately; set `$PORTAL_URL` to its public URL.
- **docker-compose**: no portal service; only `app`, `web`, `db`, etc.
- **WebserviceApps** lang string notes full functionality was expected in “v.3.1” (YetiForce-era); CRM-side API and user management are present, but the external YetiPortal app is outside this tree.
- **Security note**: `password_t` is stored and compared in plain text in `Users\Login`; treat webservice DB credentials and `X-API-KEY` as secrets.

## Related files (quick index)

```
config/config.inc.php          # $PORTAL_URL
config/api.php                 # webservice enabled, AUTH_METHOD
api/webservice.php             # API front controller
src/Api/webservice.php
src/Api/Webservice/Controller.php
src/Api/Webservice/Portal/     # Portal API actions
src/Modules/Settings/WebserviceApps/
src/Modules/Settings/WebserviceUsers/
src/Modules/Install/install_schema/Webservice.php
src/TextParser/TextParser.php  # PortalUrl, PortalDetailViewURL
public/layouts/basic/modules/Contacts/resources/Edit.js
documentation/README.md          # link to YetiPortal upstream
```

## References

- Upstream portal project: [YetiPortal](https://gitdeveloper.yetiforce.com/portal/) (linked from `documentation/README.md`).
- Legacy API index comment in `api.php` mentions `service=customerportal` (disabled/commented; not the active `api/webservice.php` stack).
