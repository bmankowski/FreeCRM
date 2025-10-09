---
title: File Headers and Templates
apply: always
---

# File Headers for New Files

## Required Header Block

Every new PHP file MUST include the following header block at the top:

```php
<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */
```

## Full Template for New PHP Class Files

When creating a new PHP class file in the `src/` directory:

```php
<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace FreeCRM\[SubNamespace];

/**
 * [Class Name] class.
 * 
 * [Brief description of the class purpose]
 */
class ClassName
{
    // Class implementation
}
```

## For Other File Types

### JavaScript/TypeScript Files
```javascript
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 */
```

### CSS/SCSS Files
```css
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 */
```

### SQL Files
```sql
-- FreeCRM - Customer Relationship Management System
-- @project FreeCRM
-- @author bmankowski@gmail.com
-- @copyright (c) FreeCRM
```

### Configuration Files (YAML, JSON, etc.)
Add a comment in the appropriate format for the file type mentioning:
- Project: FreeCRM
- Author: bmankowski@gmail.com

## Important Notes

- **Always add headers to new files** - This is mandatory for all new files created
- **Existing files** - When modifying existing files, do NOT change or remove existing headers
- **Consistency** - Maintain the same format across all files of the same type
- **Namespace** - Replace `[SubNamespace]` with the appropriate namespace based on file location

