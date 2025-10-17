# CSS Guidelines

## File Structure

- **Source files**: `public/layouts/basic/skins/style.css` (and variants)
- **Minified files**: `public/layouts/basic/skins/style.min.css` (automatically generated)
- The application loads `style.min.css` in production

## CSS Minification

### Tool
- **Minifier**: `clean-css-cli` (version ^5.6.3)
- **Installed via**: npm (see `package.json`)

### Setup
```bash
# Install dependencies (first time or after cloning)
npm install
```

### Usage

**Minify a specific CSS file:**
```bash
npm run minify-css -- path/to/your/file.css
```
This will create `file.min.css` in the same directory.

**Examples:**
```bash
npm run minify-css -- public/layouts/basic/skins/style.css
npm run minify-css -- public/layouts/basic/skins/twilight/style.css
npm run minify-css -- public/layouts/basic/skins/blue/style.css
```

**Minify all CSS files in the project:**
```bash
npm run minify-css-all
```

### Workflow

1. Edit the source CSS file (e.g., `style.css`)
2. Run the minification command
3. The minified version (e.g., `style.min.css`) is automatically created/updated
4. Commit both files to version control

### Important Notes

- Always edit the **source** `.css` file, never the `.min.css` file
- After editing CSS, **always run minification** before committing
- The application loads `.min.css` files, so minification is required for changes to take effect
- File size reduction: typically 15-20% smaller

## Animation Performance

### Current Settings
Menu animations are optimized for speed:
- Transition duration: `0.05s` (nearly instantaneous)
- Transition delay: `0s` or `0.05s` (minimal delay)

### Key Classes
- `.leftPanel` - Main left navigation panel
- `.bodyHeader` - Top header bar
- `.basePanel` - Main content area
- `.subMenu` - Dropdown submenus

If changing animation timings, keep values low (< 0.2s) for best UX.

## Browser Cache

When testing CSS changes, use hard refresh to clear browser cache:
- **Chrome/Edge**: `Ctrl + F5` or `Ctrl + Shift + R`
- **Firefox**: `Ctrl + Shift + R`
- **Safari**: `Cmd + Shift + R`

