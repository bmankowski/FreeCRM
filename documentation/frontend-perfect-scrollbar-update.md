# MVP: Upgrade perfect-scrollbar 0.6.12 → 1.5.6

## Goal

Replace the vendored perfect-scrollbar jQuery plugin (v0.6.12, last updated ~2017) with the current
vanilla-JS version (v1.5.6, Oct 2024). The jQuery plugin API was dropped in v1.0; all call sites
must migrate to the constructor-based API.

---

## Scope

### In scope (MVP)

- Replace vendored library files in `public/libraries/jquery/perfect-scrollbar/`
- Migrate all jQuery-plugin call sites to the v1.x constructor API
- Update the CSS reference path (file name unchanged, but content changes)
- Update `BaseViewController.php` to load the correct JS file (no jQuery plugin variant in v1.x)
- Rebuild `app.min.js` and `Header.min.js`

### Out of scope

- Moving to npm/build-pipeline dependency management
- Replacing perfect-scrollbar with an alternative library
- Fixing pre-existing scroll behaviour bugs unrelated to the upgrade

---

## Current state

| Item | Detail |
|---|---|
| Installed version | 0.6.12 |
| Distribution format | Vendored files (no npm/package.json) |
| JS file loaded | `perfect-scrollbar.jquery.js` (jQuery plugin variant) |
| CSS file loaded | `perfect-scrollbar.css` |
| Loaded in | `BaseViewController.php` (global, all pages) |

### Call sites

| File | Line | Current call | Notes |
|---|---|---|---|
| `public/libraries/resources/app.js` | 621 | `modalBody.perfectScrollbar()` | Init on modal open |
| `public/libraries/resources/app.js` | 616 | `modalBody.perfectScrollbar('update')` | Update on window resize |
| `public/layouts/basic/modules/Base/resources/Header.js` | 923 | `$(".slimScrollMenu").perfectScrollbar({useBothWheelAxes: true})` | Sidebar menu scroll |
| `layouts/basic/modules/ProjektyRekrutacyjne/RelatedList.tpl` | 566 | `data-js="perfectScrollbar"` | Marker attribute — no matching JS handler found; likely unused/dead |

---

## Breaking changes v0.6.x → v1.x

| Area | v0.6.x (jQuery plugin) | v1.x (vanilla) |
|---|---|---|
| Init | `$(el).perfectScrollbar(opts)` | `new PerfectScrollbar(el, opts)` |
| Update | `$(el).perfectScrollbar('update')` | `ps.update()` on stored instance |
| Destroy | `$(el).perfectScrollbar('destroy')` | `ps.destroy()` |
| Option `useBothWheelAxes` | Supported | Removed (now default behaviour) |
| CSS classes on container | `ps-container` | `ps` |
| jQuery plugin file | `perfect-scrollbar.jquery.js` | Does not exist — single `perfect-scrollbar.esm.js` / `perfect-scrollbar.js` |

---

## Architecture

No new components. The change is purely a library swap + call-site migration:

```
BaseViewController.php
  └─ loads: perfect-scrollbar.js  (was: perfect-scrollbar.jquery.js)
  └─ loads: perfect-scrollbar.css (same name, updated content)

app.js / Header.js
  └─ use: new PerfectScrollbar(el)  (was: $(el).perfectScrollbar())
  └─ store instance reference for .update() / .destroy()
```

Instance storage strategy for `app.js`: store the `PerfectScrollbar` instance on the DOM element
using jQuery `.data()` so the existing resize handler can call `.update()` without a closure change:

```js
// init
var ps = new PerfectScrollbar(modalBody[0]);
modalBody.data('ps', ps);

// update (resize handler)
var ps = modalBody.data('ps');
if (ps) ps.update();
```

---

## Implementation phases

### Phase 1 — Download and vendor new files

1. Download the v1.5.6 distribution from npm or GitHub releases.
   Relevant dist files:
   - `dist/perfect-scrollbar.js`
   - `dist/perfect-scrollbar.min.js`
   - `css/perfect-scrollbar.css`
2. Replace contents of `public/libraries/jquery/perfect-scrollbar/js/` and `.../css/`.
3. Delete `perfect-scrollbar.jquery.js` and `perfect-scrollbar.jquery.min.js` (no longer exist in v1.x).

### Phase 2 — Update the asset loader

File: `src/Base/Controllers/BaseViewController.php`

```php
// Before (line ~357):
'~libraries/jquery/perfect-scrollbar/js/perfect-scrollbar.jquery.js',

// After:
'~libraries/jquery/perfect-scrollbar/js/perfect-scrollbar.js',
```

### Phase 3 — Migrate call sites in `app.js`

File: `public/libraries/resources/app.js`

```js
// Before (lines 618–621):
var height = app.getScreenHeight() - modalDialog.outerHeight(true);
modalBody.css('max-height', (modalBody.outerHeight() + height) + 'px');
modalBody.css('overflow', 'auto');
modalBody.perfectScrollbar();

// After:
var height = app.getScreenHeight() - modalDialog.outerHeight(true);
modalBody.css('max-height', (modalBody.outerHeight() + height) + 'px');
modalBody.css('overflow', 'auto');
var ps = new PerfectScrollbar(modalBody[0]);
modalBody.data('ps', ps);
```

```js
// Before (lines 613–616):
var height = app.getScreenHeight() - modalDialog.outerHeight(true);
modalBody.css('max-height', (modalBody.outerHeight() + height) + 'px');
modalBody.css('overflow', 'auto');
modalBody.perfectScrollbar('update');

// After:
var height = app.getScreenHeight() - modalDialog.outerHeight(true);
modalBody.css('max-height', (modalBody.outerHeight() + height) + 'px');
modalBody.css('overflow', 'auto');
var ps = modalBody.data('ps');
if (ps) ps.update();
```

### Phase 4 — Migrate call site in `Header.js`

File: `public/layouts/basic/modules/Base/resources/Header.js`

```js
// Before (line 923):
$(".slimScrollMenu").perfectScrollbar({
    useBothWheelAxes: true,
});

// After:
$(".slimScrollMenu").each(function () {
    new PerfectScrollbar(this);
});
```

`useBothWheelAxes` is dropped — it is the default in v1.x.

### Phase 5 — Rebuild minified files

The project uses `terser` via npm scripts (see `package.json`):

```bash
npm run minify-js -- public/libraries/resources/app.js
npm run minify-js -- public/layouts/basic/modules/Base/resources/Header.js
```

Both commands write the result to the corresponding `.min.js` file in-place.

### Phase 6 — Verify & clean up

- [ ] Open any modal → scrollbar appears, resize works
- [ ] Sidebar menu scrolls correctly on long menus
- [ ] No JS console errors (`perfectScrollbar is not a function`)
- [ ] Inspect container elements — CSS class is now `ps`, not `ps-container`
- [ ] Check `RelatedList.tpl` `data-js="perfectScrollbar"` attribute — confirm it has no JS handler
      (safe to leave as a data attribute or remove if confirmed dead code)

---

## Risks

| Risk | Likelihood | Mitigation |
|---|---|---|
| Other undiscovered call sites | Low | `rg "perfectScrollbar"` across the full repo confirms only 3 active call sites |
| `data-js="perfectScrollbar"` in RelatedList.tpl is actually wired | Low | No matching JS selector found in any `.js` file; grep confirms it is dead |
| CSS class rename (`ps-container` → `ps`) breaks custom CSS | Low | Grep for `.ps-container` in custom CSS before deploying |
| `app.min.js` / `Header.min.js` out of sync | Low | Run `npm run minify-js` for both files as the last step; commit source + minified together |

---

## Assumptions

- Minified assets are rebuilt with `npm run minify-js` / `npm run minify-css` (see `.cursor/rules/frontend-assets.mdc`).
- No other modules or plugins depend on `$.fn.perfectScrollbar` beyond the three call sites found.
- v1.5.6 is the latest stable release as of May 2026; there is no v2.x.

---

## Future improvements (out of scope)

- Move perfect-scrollbar to an npm-managed dependency (like bootstrap is already synced via `npm run bootstrap:sync`)
- Evaluate replacing it with native CSS `overflow: auto` + `scrollbar-width: thin` (modern browsers
  support this natively; the library may become redundant)
