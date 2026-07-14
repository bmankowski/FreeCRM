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
	const root = baseUrl.replace(/\/$/, '');

	await page.goto(`${root}/index.php?module=Users&parent=Settings&action=Logout`, {
		waitUntil: 'domcontentloaded',
		timeout: 30000,
	});

	const loginForm = page.locator('input[name="username"]');
	const onLoginPage = await loginForm.isVisible({ timeout: 5000 }).catch(() => false);

	if (onLoginPage) {
		await loginForm.fill(username);
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
