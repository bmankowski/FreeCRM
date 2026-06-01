/**
 * FreeCRM E2E — shared login helper
 *
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { Page } from '@playwright/test';

export interface LoginCredentials {
	username: string;
	password: string;
}

export function getLoginCredentials(): LoginCredentials {
	return {
		username: process.env.FREECRM_USER || 'admin',
		password: process.env.FREECRM_PASS || 'NewArgon2idAdmin#42',
	};
}

export async function login(page: Page, baseUrl: string, credentials?: LoginCredentials): Promise<void> {
	const { username, password } = credentials ?? getLoginCredentials();

	await page.goto(`${baseUrl}/index.php`);

	const needsLogin = await page
		.locator('input[name="username"]')
		.isVisible({ timeout: 3000 })
		.catch(() => false);

	if (needsLogin) {
		await page.fill('input[name="username"]', username);
		await page.fill('input[name="password"]', password);
		await page.click('button[type="submit"]');
		await page.waitForLoadState('networkidle', { timeout: 15000 });
	}

	await page.waitForSelector('nav, .menubar, [role="menubar"], .userName', {
		timeout: 10000,
		state: 'attached',
	});
	await page.waitForLoadState('domcontentloaded');
}
