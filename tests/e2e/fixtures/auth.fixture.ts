/**
 * Authentication Fixture for FreeCRM E2E Tests
 *
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test as base, Page } from '@playwright/test';
import { login } from '../helpers/login';

type AuthFixture = {
	authenticatedPage: Page;
};

export const test = base.extend<AuthFixture>({
	authenticatedPage: async ({ page, baseURL }, use) => {
		await login(page, baseURL ?? 'https://dev.itconnect.pl');
		await use(page);

		try {
			const currentUrl = page.url();
			if (currentUrl.includes('index.php')) {
				const logoutButton = page.locator('text=Logout, text=Sign Out, a[href*="logout"]').first();
				if (await logoutButton.isVisible({ timeout: 2000 })) {
					await logoutButton.click();
				}
			}
		} catch {
			// Ignore logout errors — test might have already logged out
		}
	},
});

export { expect } from '@playwright/test';
