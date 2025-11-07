# Module Test Template

Use this template when creating tests for a new FreeCRM module.

## Steps to Create Tests for a New Module

### 1. Create Module Directory

```bash
cd tests/e2e/tests/Modules
mkdir YourModuleName  # e.g., Contacts, Accounts, Calendar
```

### 2. Create Page Object (if needed)

```bash
cd tests/e2e/pages
# Create YourModuleNamePage.ts
```

**Example Page Object Template:**

```typescript
/**
 * YourModuleName Page Object Model
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { Page, Locator, expect } from '@playwright/test';

export class YourModuleNamePage {
  readonly page: Page;
  
  constructor(page: Page) {
    this.page = page;
  }

  /**
   * Navigate to module list view
   */
  async goto() {
    await this.page.goto('/index.php?module=YourModuleName&view=List');
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Add your module-specific methods here
   */
}
```

### 3. Create Test File

```bash
cd tests/e2e/tests/Modules/YourModuleName
# Create yourtest.spec.ts
```

**Example Test Template:**

```typescript
/**
 * YourModuleName E2E Tests
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { YourModuleNamePage } from '../../../pages/YourModuleNamePage';

test.describe('YourModuleName Tests', () => {
  let modulePage: YourModuleNamePage;

  test.beforeEach(async ({ authenticatedPage }) => {
    modulePage = new YourModuleNamePage(authenticatedPage);
    await modulePage.goto();
  });

  test('should display list view', async ({ authenticatedPage }) => {
    await expect(authenticatedPage).toHaveURL(/module=YourModuleName/);
    // Add your assertions
  });

  test('your test description', async ({ authenticatedPage }) => {
    // Your test logic
  });
});
```

### 4. Run Your Tests

```bash
# Run all tests for the module
npx playwright test tests/Modules/YourModuleName

# Run specific test file
npx playwright test tests/Modules/YourModuleName/yourtest.spec.ts

# Run in UI mode for development
npm run test:ui
```

## Examples

Current implemented modules:

- ✅ **Leads** (`tests/Modules/Leads/`) - Filtering tests

Planned modules:

- ⏳ **Contacts** (`tests/Modules/Contacts/`)
- ⏳ **Accounts** (`tests/Modules/Accounts/`)
- ⏳ **Calendar** (`tests/Modules/Calendar/`)
- ⏳ **Opportunities** (`tests/Modules/Opportunities/`)

## Tips

1. Use the authentication fixture - it automatically logs in
2. Create reusable Page Objects for common actions
3. Use descriptive test names
4. Test critical user workflows first
5. Run tests in UI mode (`npm run test:ui`) during development

