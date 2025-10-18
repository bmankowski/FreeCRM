# Add Missing Properties Script

This script automatically adds missing property declarations to PHP classes based on PHPStan analysis.

## Features

- ✅ **Detects undefined properties** using PHPStan
- ✅ **Smart type guessing** based on property naming conventions
- ✅ **Dry-run mode** to preview changes before applying
- ✅ **Automatic property visibility** (public for value objects, protected for others)
- ✅ **Proper PHPDoc comments** with type hints

## Usage

### Preview Changes (Dry Run)

```bash
php refactor/add-missing-properties.php --dry-run <file-to-analyze>
```

Example:
```bash
php refactor/add-missing-properties.php --dry-run src/events/VTWSEntityType.php
```

### Apply Changes

```bash
php refactor/add-missing-properties.php <file-to-analyze>
```

Example:
```bash
php refactor/add-missing-properties.php src/events/VTWSEntityType.php
```

### Process Multiple Files

```bash
# Process all files in a directory
for file in src/events/*.php; do 
    php refactor/add-missing-properties.php "$file"
done

# Dry run for all files
for file in src/events/*.php; do 
    php refactor/add-missing-properties.php --dry-run "$file"
done
```

## How It Works

1. **Runs PHPStan** on the target file to find undefined properties
2. **Analyzes the errors** to extract class and property names
3. **Generates property declarations** with:
   - Appropriate PHPDoc type hints
   - Smart visibility (public/protected)
   - Alphabetically sorted properties
4. **Inserts properties** into the class at the right location
5. **Verifies** the changes with PHPStan

## Type Guessing

The script intelligently guesses property types based on naming conventions:

| Property Name Pattern | Guessed Type |
|-----------------------|--------------|
| `isActive`, `hasAccess` | `bool` |
| `tabId`, `pos` | `int\|null` |
| `userId`, `entityId` | `int\|string\|null` |
| `adb`, `db` | `\App\Database\PearDatabase\|null` |
| `data` | `array` |
| `description`, `fieldNames`, `fieldLabels` | `array\|null` |
| `focus` | `\CRMEntity\|null` |
| Contains "type" or "Type" | `string\|null` |
| Contains "format" | `string\|null` |
| Contains "Name" or "name" | `string\|null` |

## Property Visibility

- **Public**: For value object classes (VTWSFieldType, VTFieldType, SqlResultIteratorRow)
- **Protected**: For all other classes (default for encapsulation)

## Example Output

### Dry Run Mode
```
[DRY RUN MODE] Analyzing src/events/VTWSEntityType.php...

Found 8 undefined properties in class: VTWSEntityType
Properties to add:
      /** @var \App\Database\PearDatabase|null */
      protected $adb;
      /** @var array|null */
      protected $description;
      /** @var string */
      protected $entityTypeName;
      
[DRY RUN] Would add properties to class VTWSEntityType
```

### Apply Mode
```
Analyzing src/events/VTWSEntityType.php...

Found 8 undefined properties in class: VTWSEntityType
Properties to add:
      /** @var \App\Database\PearDatabase|null */
      protected $adb;
      
✅ Properties added to src/events/VTWSEntityType.php

=== COMPLETE ===
Properties have been added. Run PHPStan again to verify:
  vendor/bin/phpstan analyse src/events/VTWSEntityType.php --level=5
```

## Verification

After running the script, verify the changes with PHPStan:

```bash
vendor/bin/phpstan analyse src/events/VTWSEntityType.php --level=5
```

You should see: `[OK] No errors`

## Notes

- The script preserves existing code formatting
- Properties are inserted after the class opening brace or after existing properties
- Properties are alphabetically sorted for consistency
- The script handles multiple classes in the same file

