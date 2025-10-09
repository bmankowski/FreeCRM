---
title: FreeCRM Project Identity
apply: always
---

# FreeCRM Project Context

## Project Identity
This is **FreeCRM**, NOT YetiForce, NOT SugarCRM, NOT Vtiger.

While FreeCRM is based on YetiForce (which was originally forked from Vtiger CRM), this is a distinct project with its own identity and modifications.

## Key Information
- **Project Name**: FreeCRM
- **Primary Namespace**: `FreeCRM\` (PSR-4 autoloaded from `src/`)
- **Language**: PHP (minimum 5.6.0, but targeting modern versions)
- **Framework**: Yii2
- **Type**: Customer Relationship Management (CRM) system

## Architecture
- Uses PSR-4 autoloading with multiple namespaces:
  - `FreeCRM\` → `src/` (main application code)
  - `vtlib\` → `vtlib/Vtiger/` (legacy compatibility layer)
  - `includes\` → `include/`
  - `App\` → `vendor/yetiforce/` (legacy vendor namespace)
  - `Api\` → `api/webservice/`
  - `Exception\` → `include/exceptions/`

## Coding Standards
- When creating new files, use the `FreeCRM` namespace for new code in the `src/` directory
- Follow PSR-4 autoloading standards
- Use modern PHP practices where possible
- Maintain backward compatibility with existing modules where necessary

## Important Notes
- Any references to YetiForce in documentation or comments should be understood as historical context only
- When suggesting improvements or creating new features, brand them as FreeCRM features
- The project inherits some YetiForce/Vtiger structures but is evolving independently

