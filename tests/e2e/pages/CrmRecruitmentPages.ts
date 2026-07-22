/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

import { Page, Locator, expect } from '@playwright/test';

/**
 * Shared list/detail helpers for CRM modules used by WWW apply smoke.
 */
export class CrmListPage {
	readonly page: Page;
	readonly module: string;
	readonly listViewRoot: Locator;
	readonly table: Locator;
	readonly searchTrigger: Locator;

	constructor(page: Page, module: string) {
		this.page = page;
		this.module = module;
		this.listViewRoot = page.locator('.listViewPageDiv .listViewContentDiv').first();
		this.table = this.listViewRoot.locator('table.listViewEntriesTable');
		this.searchTrigger = this.listViewRoot.locator('[data-trigger="listSearch"]').first();
	}

	async gotoList(): Promise<void> {
		await this.page.goto(`/index.php?module=${this.module}&view=ListView`, {
			waitUntil: 'domcontentloaded',
		});
		await this.waitForListLoad();
	}

	async waitForListLoad(): Promise<void> {
		await Promise.allSettled([
			this.page.waitForSelector('.loading, .listViewLoadingImageBlock, .blockUI.blockOverlay', {
				state: 'hidden',
				timeout: 10000,
			}),
			this.table.first().waitFor({ state: 'visible', timeout: 15000 }),
		]);
	}

	async waitForListReload(): Promise<void> {
		await this.page.waitForLoadState('networkidle', { timeout: 20000 }).catch(() => undefined);
		await this.waitForListLoad();
	}

	async selectView(viewName: string): Promise<void> {
		const customFilter = this.page.locator('#customFilter');
		if ((await customFilter.count()) === 0) {
			return;
		}
		await customFilter.waitFor({ state: 'attached', timeout: 10000 });
		try {
			await customFilter.selectOption({ label: viewName }, { force: true });
		} catch {
			try {
				await customFilter.selectOption({ label: 'All' }, { force: true });
			} catch {
				await customFilter.selectOption({ label: 'Wszystkie' }, { force: true });
			}
		}
		await this.waitForListReload();
	}

	async searchByColumn(columnName: string, value: string): Promise<void> {
		const input = this.listViewRoot.locator(`.listSearchContributor[name="${columnName}"]`).first();
		await input.waitFor({ state: 'visible', timeout: 15000 });
		await input.fill('');
		await input.fill(value);
		// Prefer explicit search button — Enter alone is unreliable while overlays settle.
		if (await this.searchTrigger.isVisible().catch(() => false)) {
			await this.searchTrigger.click();
		} else {
			await input.press('Enter');
		}
		await this.page
			.locator('.blockUI.blockOverlay')
			.first()
			.waitFor({ state: 'hidden', timeout: 20000 })
			.catch(() => undefined);
		await this.waitForListReload();
	}

	dataRows(): Locator {
		return this.table.locator('tbody tr.listViewEntries');
	}

	/**
	 * Poll until a list row containing `needle` is visible.
	 */
	async waitUntilRowContains(
		needle: string,
		options: {
			timeoutMs?: number;
			intervalMs?: number;
			viewName?: string;
			searchColumn?: string;
		} = {}
	): Promise<void> {
		const timeoutMs = options.timeoutMs ?? 420_000;
		const intervalMs = options.intervalMs ?? 10_000;
		const deadline = Date.now() + timeoutMs;

		await this.gotoList();
		if (options.viewName) {
			await this.selectView(options.viewName);
		}

		while (Date.now() < deadline) {
			if (options.searchColumn) {
				await this.searchByColumn(options.searchColumn, needle);
			}
			const match = this.dataRows().filter({ hasText: needle }).first();
			if (await match.isVisible({ timeout: 2000 }).catch(() => false)) {
				return;
			}
			await this.page.waitForTimeout(intervalMs);
			await this.gotoList();
			if (options.viewName) {
				await this.selectView(options.viewName);
			}
		}

		throw new Error(
			`Timed out after ${timeoutMs}ms waiting for ${this.module} row containing ${JSON.stringify(needle)}`
		);
	}

	async openRowContaining(needle: string): Promise<void> {
		await this.page
			.locator('.blockUI.blockOverlay, .listViewLoadingImageBlock')
			.first()
			.waitFor({ state: 'hidden', timeout: 20000 })
			.catch(() => undefined);

		const row = this.dataRows().filter({ hasText: needle }).first();
		await expect(row).toBeVisible({ timeout: 15000 });

		const recordUrl =
			(await row.getAttribute('data-recordurl')) ||
			(await row.locator('a[href*="view=Detail"]').first().getAttribute('href'));
		if (!recordUrl) {
			throw new Error(`No detail URL on ${this.module} row containing ${JSON.stringify(needle)}`);
		}
		const href = recordUrl.startsWith('http') ? recordUrl : `/${recordUrl.replace(/^\//, '')}`;
		await this.page.goto(href, { waitUntil: 'domcontentloaded' });
		await this.page.waitForURL(/view=Detail/, { timeout: 30000 });
	}

	detailFieldValue(fieldName: string): Locator {
		return this.page
			.locator(
				`.detailViewInfo [data-name="${fieldName}"] .value, ` +
					`.fieldValue[data-name="${fieldName}"] .value, ` +
					`#${this.module}_detailView_fieldValue_${fieldName} .value, ` +
					`td.fieldValue[data-name="${fieldName}"]`
			)
			.first();
	}

	async expectDetailContains(fieldName: string, expected: string | RegExp): Promise<void> {
		const field = this.detailFieldValue(fieldName);
		await expect(field).toBeVisible({ timeout: 20000 });
		await expect(field).toContainText(expected, { timeout: 10000 });
	}
}

export class RecruitmentApplicationPage extends CrmListPage {
	constructor(page: Page) {
		super(page, 'RecruitmentApplication');
	}

	async waitForCandidateName(name: string, timeoutMs = 420_000): Promise<void> {
		await this.waitUntilRowContains(name, {
			timeoutMs,
			searchColumn: 'candidate_name',
		});
	}
}

export class CandidatesPage extends CrmListPage {
	constructor(page: Page) {
		super(page, 'Candidates');
	}

	async waitForName(name: string, timeoutMs = 420_000): Promise<void> {
		await this.waitUntilRowContains(name, {
			timeoutMs,
			searchColumn: 'name',
		});
	}

	async expectRelatedProjectContaining(text: string | RegExp): Promise<void> {
		const relatedTab = this.page
			.locator(
				'.related li[data-reference="ProjektyRekrutacyjne"], ' +
					'.related a:has-text("Projekty rekrutacyjne"), ' +
					'.related a:has-text("Projekty Rekrutacyjne")'
			)
			.first();
		if (await relatedTab.isVisible({ timeout: 5000 }).catch(() => false)) {
			await relatedTab.click();
			await this.page
				.waitForSelector(
					'.relatedContents table.listViewEntriesTable, .relatedContents .listViewEntries, a[href*="module=ProjektyRekrutacyjne"]',
					{ timeout: 30000 }
				)
				.catch(() => undefined);
		}
		await expect(this.page.locator('.detailViewContainer')).toContainText(text, { timeout: 20000 });
	}
}
