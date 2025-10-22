# Public Directory Migration Guide

## What Goes Where

### ✅ PUBLIC Directory (`/public/`)
**Static assets served directly by Apache:**
- `*.js` files (JavaScript)
- `*.css` files (Stylesheets)
- `*.less` files (Less stylesheets)
- `*.png`, `*.jpg`, `*.gif`, `*.svg` (Images)
- `*.woff`, `*.ttf`, `*.eot` (Fonts)
- `*.mp3`, `*.wav` (Sounds)
- `.htaccess` files (Apache config)

**Directories:**
- `public/layouts/` - JS, CSS, images, fonts, sounds ONLY
- `public/libraries/` - JS, CSS, fonts ONLY
- `public/modules/` - JS files ONLY

### ❌ ROOT Directory (NOT public)
**Server-side files that MUST NOT be publicly accessible:**
- `*.tpl` files (Smarty templates)
- `*.php` files (except entry points)
- `*.xml` files (configuration)
- Database files, logs, cache
- `storage/` - user uploads, sensitive data

**Directories:**
- `layouts/` - TPL templates ONLY (no JS/CSS)
- `src/` - PHP source code
- `config/` - configuration files
- `cache/` - temporary files
- `user_privileges/` - sensitive data

## Migration Steps

### 1. Sync Static Assets to Public
```bash
# Sync JS and CSS to public (preserves latest changes)
rsync -av --include='*.js' --include='*.css' --include='*.less' \
  --include='*/' --exclude='*' \
  layouts/ public/layouts/

rsync -av --include='*.js' --include='*.css' \
  --include='*/' --exclude='*' \
  libraries/ public/libraries/
```

### 2. Sync Images and Other Assets
```bash
# Sync images, fonts, sounds
rsync -av --include='*.png' --include='*.jpg' --include='*.gif' --include='*.svg' \
  --include='*.woff' --include='*.ttf' --include='*.eot' --include='*.mp3' --include='*.wav' \
  --include='*.ico' --include='*/' --exclude='*' \
  layouts/ public/layouts/
```

### 3. Delete JS/CSS from Root
```bash
# Remove from root (now in public only)
find layouts/ -name '*.js' -type f -delete
find libraries/ -name '*.js' -type f -delete
find layouts/ -type d -empty -delete
find libraries/ -type d -empty -delete
```

### 4. Verify - NEVER in Public
```bash
# These should return 0
find public/layouts -name '*.tpl' | wc -l  # Should be 0
find public/layouts -name '*.php' | wc -l  # Should be 0
find public/ -name 'storage' -type d       # Should be empty
```

## Code Changes Required

### Modified: `src/Vtiger_Loader.php`
Added check for static assets in `public/` directory first:
```php
// Lines 43-49: Check public/ for JS/CSS files
if (in_array($fileExtension, ['js', 'css', 'less'])) {
    $publicFile = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $file;
    if (file_exists($publicFile)) {
        return $publicFile;
    }
}
```

## Common Mistakes to Avoid

1. ❌ **Don't rsync ALL files** - only static assets
2. ❌ **Don't put TPL files in public** - security risk
3. ❌ **Don't put storage/ in public** - sensitive data
4. ❌ **Don't use symlinks** - creates confusion
5. ❌ **Don't delete before syncing** - you'll lose modifications

## Quick Reference

**Edit JS/CSS files:** Always edit in `public/layouts/` or `public/libraries/`
**Edit TPL files:** Always edit in root `layouts/`
**Edit PHP files:** Always edit in `src/` or `modules/`

**After editing Detail.js, minify:**
```bash
terser public/layouts/basic/modules/Vtiger/resources/Detail.js -c -m \
  -o public/layouts/basic/modules/Vtiger/resources/Detail.min.js
```

