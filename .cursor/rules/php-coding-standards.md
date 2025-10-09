---
title: PHP Coding Standards
apply: auto-attach
patterns: ["**/*.php"]
---

# PHP Coding Standards for FreeCRM

## Namespace Usage
- **New code in `src/`**: MUST use `FreeCRM\` namespace
- Follow PSR-4 autoloading conventions
- Organize classes by domain/feature in subdirectories

## Code Style
- Follow PSR-12 coding style standard
- Use type hints for parameters and return types (PHP 7.0+)
- Use strict types where possible: `declare(strict_types=1);`
- Use meaningful variable and method names

## Documentation
- Add PHPDoc blocks for all classes, methods, and properties
- Include `@param`, `@return`, and `@throws` tags
- Document any YetiForce/Vtiger legacy compatibility notes

## Modern PHP Practices
- Prefer dependency injection over global state
- Use try-catch for exception handling
- Avoid deprecated PHP features
- Use null coalescing operator (`??`) and spaceship operator (`<=>`) where appropriate

## Legacy Code
- When modifying legacy code (non-FreeCRM namespace), maintain existing style for consistency
- Add comments explaining any FreeCRM-specific modifications
- Plan gradual migration to FreeCRM namespace when feasible

