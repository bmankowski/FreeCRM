/**
 * Home Page E2E Tests
 * 
 * Tests the home page/dashboard functionality.
 * Verifies that the main page loads without errors or warnings.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../fixtures/auth.fixture';
import { expectNoWarningsAndErrors } from '../helpers/page-assertions';

test.describe('Home Page', () => {
  test('should display home page without errors or warnings', async ({ authenticatedPage }) => {
    // Navigate to home page (index.php)
    await authenticatedPage.goto('/index.php', { waitUntil: 'domcontentloaded' });
    
    // Wait for page to fully load
    await authenticatedPage.waitForLoadState('networkidle');
    
    // Verify page does not contain warning or error messages
    await expectNoWarningsAndErrors(authenticatedPage);
    // Check for footer
    const footer = authenticatedPage.locator('footer, .footer, [role="contentinfo"]').first();
    
    if (await footer.count() > 0) {
      await expect(footer).toBeVisible({ timeout: 5000 });
    } else {
      // If no footer found, at least verify page loaded successfully
      const body = authenticatedPage.locator('body');
      await expect(body).toBeVisible();
    }
  });
});

