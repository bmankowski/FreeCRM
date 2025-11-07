/**
 * Authentication Fixture for FreeCRM E2E Tests
 * 
 * Automatically logs in before each test and provides an authenticated page.
 * This eliminates the need to manually log in for every test.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test as base, Page } from '@playwright/test';

type AuthFixture = {
  authenticatedPage: Page;
};

/**
 * Extended test with authentication fixture
 * 
 * Usage:
 * ```typescript
 * import { test } from '../fixtures/auth.fixture';
 * 
 * test('my test', async ({ authenticatedPage }) => {
 *   // Already logged in!
 *   await authenticatedPage.goto('/index.php?module=Leads&view=List');
 * });
 * ```
 */
export const test = base.extend<AuthFixture>({
  authenticatedPage: async ({ page }, use) => {
    // Perform login
    await page.goto('/index.php');
    
    // Check if we need to log in or if already logged in
    const needsLogin = await page.locator('input[name="username"]').isVisible({ timeout: 3000 }).catch(() => false);
    
    if (needsLogin) {
      // Fill credentials individually (as per login-credentials.mdc)
      await page.fill('input[name="username"]', 'admin');
      await page.fill('input[name="password"]', 'admin');
      
      // Submit the form
      await page.click('button[type="submit"]');
      
      // Wait for login to complete - page will navigate away from login
      await page.waitForLoadState('networkidle', { timeout: 15000 });
    }
    
    // Verify we're actually logged in by checking for common CRM elements
    // Check for navigation menu or user info that indicates we're logged in
    await page.waitForSelector('nav, .menubar, [role="menubar"], .userName', { 
      timeout: 10000,
      state: 'attached' // Just check if element exists in DOM, not visibility
    });
    
    // Additional wait for page to be fully interactive
    await page.waitForLoadState('domcontentloaded');
    
    // Pass the authenticated page to the test
    await use(page);
    
    // Cleanup: logout after test (optional)
    // This ensures clean state between tests
    try {
      // Try to logout if still on a CRM page
      const currentUrl = page.url();
      if (currentUrl.includes('index.php')) {
        // Look for logout button/link - adjust selector based on actual CRM UI
        const logoutButton = page.locator('text=Logout, text=Sign Out, a[href*="logout"]').first();
        if (await logoutButton.isVisible({ timeout: 2000 })) {
          await logoutButton.click();
        }
      }
    } catch (error) {
      // Ignore logout errors - test might have already logged out
      console.log('Cleanup: Could not logout, might already be logged out');
    }
  },
});

export { expect } from '@playwright/test';

