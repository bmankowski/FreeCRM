# Cursor Rules Guidelines

## Overview

As of 2024, the `.cursorrules` file format is **deprecated**. All rules should now be stored as individual `.mdc` files in the `.cursor/rules/` directory for better organization, easier updates, and more focused rule management.

## File Locations

### Project Rules (Recommended)
- **Location**: `.cursor/rules/` directory in project root
- **Format**: Individual `.mdc` (MDC - Metadata + Content) files
- **Version Control**: Should be committed to repository
- **Scope**: Project-specific rules that apply to the codebase
- **Subdirectories**: Each subdirectory can have its own `.cursor/rules/` for localized rules

### User Rules (Global)
- **Location**: Cursor Settings → Rules
- **Format**: Plain text
- **Scope**: Global preferences that apply across all projects
- **Use Cases**: Communication style, personal coding conventions

### Legacy Format (Deprecated)
- **Location**: `.cursorrules` file in project root
- **Status**: Still supported but will be deprecated
- **Recommendation**: Migrate to `.cursor/rules/` directory

## Rule Types

Rules can be configured with different application strategies:

### 1. Always
- **Behavior**: Always included in the model context
- **Use Case**: Core project guidelines, critical conventions
- **Property**: `alwaysApply: true`

### 2. Auto Attached
- **Behavior**: Included when files matching a glob pattern are referenced
- **Use Case**: Language-specific rules, module-specific guidelines
- **Property**: `globs: ["pattern"]`

### 3. Agent Requested
- **Behavior**: Available to AI, which decides whether to include it
- **Use Case**: Optional guidelines, context-dependent rules
- **Property**: Requires `description` field

### 4. Manual
- **Behavior**: Only included when explicitly mentioned using `@ruleName`
- **Use Case**: Specialized rules, temporary guidelines
- **Property**: No automatic inclusion

## MDC File Structure

Each `.mdc` rule file supports metadata and content:

```markdown
---
description: "When to apply this rule"
globs: ["src/**/*.php", "modules/**/*.php"]
alwaysApply: false
---

# Rule Title

Rule content goes here...
```

### Metadata Properties
- `description`: Explains when the rule should be applied (required for Agent Requested)
- `globs`: Array of glob patterns for Auto Attached rules
- `alwaysApply`: Boolean to make the rule always active

## Best Practices

### Rule Organization
1. **One Concern Per File**: Each rule should address a single aspect or concern
2. **Descriptive Names**: Use clear, descriptive filenames (e.g., `php-namespaces.mdc`, `api-conventions.mdc`)
3. **Logical Grouping**: Use subdirectories for related rules if needed

### Rule Content
1. **Length**: Keep rules under **500 lines**
   - Split larger rules into multiple composable rules
   - Focus on specific, actionable guidance

2. **Clarity**: 
   - Avoid vague guidance
   - Write as clear internal documentation
   - Provide concrete examples

3. **Examples**:
   - Include code examples showing correct and incorrect patterns
   - Reference actual files from the project when applicable
   - Use `<good-example>` and `<bad-example>` blocks

4. **Actionable**:
   - Focus on what to do, not just what to avoid
   - Provide clear steps or patterns to follow

5. **Scoped**:
   - Use glob patterns to limit rule application to relevant files
   - Don't apply backend rules to frontend code and vice versa

### Reusability
- Reuse rules when repeating prompts in chat
- Create rules for frequently repeated instructions
- Reference rules with `@ruleName` in chat when needed

## Migration from `.cursorrules`

To migrate from the legacy `.cursorrules` file:

1. **Create Directory**: Create `.cursor/rules/` in project root
2. **Split Content**: Break down `.cursorrules` into focused rule files
3. **Add Metadata**: Add appropriate metadata to each `.mdc` file
4. **Set Types**: Configure rule types (Always, Auto Attached, etc.)
5. **Test**: Verify rules are being applied correctly
6. **Remove Legacy**: Delete or archive the `.cursorrules` file

## Creating New Rules

### Via Command Palette
1. Press `Cmd + Shift + P` (Mac) or `Ctrl + Shift + P` (Windows/Linux)
2. Select "New Cursor Rule"
3. Creates a new `.mdc` file in `.cursor/rules/`

### Manual Creation
1. Create a new `.mdc` file in `.cursor/rules/`
2. Add metadata section with `---` delimiters
3. Write rule content below metadata
4. Save and test

## Rule Visibility

- View all rules and their status in Cursor Settings
- Rules are visible and manageable from the UI
- Better tracking compared to monolithic `.cursorrules` file

## Example Rule Structure

### Always Applied Rule

```markdown
---
alwaysApply: true
---

# Project Coding Standards

- Use 4 spaces for indentation
- Follow PSR-12 coding standards
- All classes must have proper docblocks
```

### Auto Attached Rule

```markdown
---
description: "PHP namespace conventions for this project"
globs: ["src/**/*.php", "modules/**/*.php"]
---

# PHP Namespace Guidelines

- All classes in `src/` use `FreeCRM\` namespace
- Always use fully qualified class names
- Add leading backslash for global namespace classes
```

### Manual Rule

```markdown
---
description: "Database migration guidelines"
---

# Database Migrations

Apply this rule when working with database schemas.

[Guidelines here...]
```

## Benefits of New System

1. **Better Organization**: Separate files for different concerns
2. **Easier Maintenance**: Update individual rules without affecting others
3. **Granular Control**: Apply rules to specific file patterns or contexts
4. **Version Control**: Easier to track changes in git diffs
5. **Scalability**: Supports large projects with many rules
6. **Visibility**: Clear overview of all active rules
7. **Flexibility**: Multiple application strategies (Always, Auto, Manual, Agent)

## Summary

| Aspect | `.cursorrules` (Old) | `.cursor/rules/` (New) |
|--------|---------------------|------------------------|
| Format | Single file | Multiple `.mdc` files |
| Organization | Monolithic | Modular |
| Status | Deprecated | Recommended |
| Metadata | None | Rich metadata support |
| Scoping | Global to project | Glob patterns, conditional |
| Visibility | Hidden in file | UI-visible |
| Maintenance | Difficult | Easy |

## Recommendations

1. **Migrate Now**: Convert existing `.cursorrules` to `.cursor/rules/` directory
2. **Stay Focused**: Keep each rule under 500 lines and focused on one aspect
3. **Use Glob Patterns**: Apply rules only where relevant
4. **Include Examples**: Always provide concrete code examples
5. **Version Control**: Commit `.cursor/rules/` to repository
6. **Document**: Add descriptions to help AI and team understand rule purpose



