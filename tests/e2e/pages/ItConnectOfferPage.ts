/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

import { Page, Locator, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';

declare global {
	interface Window {
		JetFormBuilder?: Record<
			string,
			{
				dataInputs?: Record<
					string,
					{
						nodes?: HTMLInputElement[];
						reporting?: unknown;
					}
				>;
			}
		>;
	}
}

export type OfferApplyData = {
	fullName: string;
	email: string;
	phone: string;
	message: string;
	availableFrom: string;
	preferredContractType: string;
	expectedSalary: string;
	futureRecruitmentConsent?: boolean;
	cvPath: string;
};

/**
 * Public job-offer apply form on www.itconnect.pl (JetForm Builder popup).
 */
export class ItConnectOfferPage {
	readonly page: Page;
	readonly applyTrigger: Locator;
	readonly form: Locator;
	lastSubmittedProjectId = '';

	constructor(page: Page) {
		this.page = page;
		this.applyTrigger = page
			.locator('[data-jet-popup*="jet-popup-2807"], .aplikuj-btn')
			.or(page.getByRole('button', { name: /^APLIKUJ$/i }))
			.or(page.getByRole('link', { name: /^APLIKUJ$/i }))
			.first();
		this.form = page.locator('form.jet-form-builder').first();
	}

	async goto(offerUrl: string): Promise<void> {
		await this.page.goto(offerUrl, { waitUntil: 'domcontentloaded', timeout: 60000 });
		await this.passSpamFireWallIfPresent(offerUrl);
		await this.page
			.locator('.aplikuj-btn, form.jet-form-builder, h2:has-text("Tester"), text=APLIKUJ')
			.first()
			.waitFor({ state: 'visible', timeout: 30000 });
	}

	/**
	 * CleanTalk SpamFireWall interstitial (auto-redirect ~3s, or click through).
	 */
	async passSpamFireWallIfPresent(offerUrl: string): Promise<void> {
		const firewall = this.page.getByText(/SpamFireWall/i);
		if (!(await firewall.isVisible({ timeout: 2000 }).catch(() => false))) {
			return;
		}
		const continueLink = this.page.getByRole('link', { name: new RegExp(offerUrl.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i') })
			.or(this.page.locator(`a[href*="/oferta/"]`))
			.first();
		if (await continueLink.isVisible({ timeout: 2000 }).catch(() => false)) {
			await continueLink.click();
		}
		await this.page.waitForURL((url) => !/SpamFireWall/i.test(url.toString()), { timeout: 20000 }).catch(() => undefined);
		await firewall.waitFor({ state: 'hidden', timeout: 20000 }).catch(() => undefined);
		await this.page.waitForLoadState('domcontentloaded');
	}

	async acceptCookiesIfPresent(): Promise<void> {
		const accept = this.page.getByRole('button', { name: /Akceptuj wszystko/i });
		if (await accept.isVisible({ timeout: 4000 }).catch(() => false)) {
			await accept.click({ force: true });
			await this.page.waitForTimeout(500);
		}
		await this.page.evaluate(() => {
			document
				.querySelectorAll(
					'.cky-consent-container, .cky-overlay, #cky-consent-container, .cky-modal'
				)
				.forEach((el) => el.remove());
		});
	}

	async openApplyForm(): Promise<void> {
		await this.acceptCookiesIfPresent();
		const alreadyVisible = await this.form.locator('#name').isVisible({ timeout: 1000 }).catch(() => false);
		if (!alreadyVisible) {
			await this.applyTrigger.click({ force: true });
		}
		await this.form.locator('#name').waitFor({ state: 'visible', timeout: 15000 });
		await this.acceptCookiesIfPresent();
	}

	async fillAndSubmit(data: OfferApplyData): Promise<void> {
		await this.openApplyForm();
		// CleanTalk collects bot signals for a few seconds after the popup opens.
		await this.page.waitForTimeout(2000);

		const typeSlow = async (selector: string, value: string) => {
			const field = this.form.locator(selector);
			await field.click();
			await field.fill('');
			await field.pressSequentially(value, { delay: 35 });
		};

		await typeSlow('#name', data.fullName);
		await typeSlow('#email', data.email);
		await typeSlow('#phone_number', data.phone);
		await typeSlow('#message', data.message);
		await typeSlow('#available_from', data.availableFrom);
		await typeSlow('#preferred_contract_type', data.preferredContractType);
		await typeSlow('#expected_salary', data.expectedSalary);

		this.lastSubmittedProjectId = await this.form.locator('input[name="id_projektu"]').inputValue();

		const cvAbsolute = path.isAbsolute(data.cvPath) ? data.cvPath : path.resolve(data.cvPath);
		const fileName = path.basename(cvAbsolute);

		const fileInput = this.form.locator('input[type="file"]').first();
		await fileInput.setInputFiles({
			name: fileName,
			mimeType: fileName.endsWith('.pdf')
				? 'application/pdf'
				: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			buffer: fs.readFileSync(cvAbsolute),
		});

		await expect
			.poll(
				async () =>
					fileInput.evaluate((el: HTMLInputElement) => (el.files && el.files.length > 0) || false),
				{ timeout: 10000, intervals: [200, 500] }
			)
			.toBeTruthy();
		// JetForm media.field.restrictions (Intl) needs a tick after change.
		await this.page.waitForTimeout(1000);

		const consent = this.form.locator('input[name="future_recruitment_consent"]');
		if (data.futureRecruitmentConsent !== false && (await consent.count()) > 0) {
			await consent.check({ force: true });
		}

		await this.acceptCookiesIfPresent();

		const maxAttempts = 2;
		let responseBody = '';
		for (let attempt = 1; attempt <= maxAttempts; attempt++) {
			const responsePromise = this.page.waitForResponse(
				(res) =>
					res.request().method() === 'POST' &&
					res.url().includes('oferta') &&
					res.url().includes('method=ajax'),
				{ timeout: 60000 }
			);

			await this.form.locator('button.jet-form-builder__submit[type="submit"]').click({ force: true });
			const response = await responsePromise;
			responseBody = await response.text().catch(() => '');

			if (/forms too often|too often|Please wait/i.test(responseBody)) {
				if (attempt === maxAttempts) {
					throw new Error(`WWW apply rate-limited by CleanTalk: ${responseBody.slice(0, 300)}`);
				}
				// CleanTalk temporary throttle after repeated test submits.
				await this.page.waitForTimeout(600_000);
				continue;
			}

			if (/Forbidden|Anti-Spam|CleanTalk/i.test(responseBody)) {
				throw new Error(`WWW apply blocked by anti-spam: ${responseBody.slice(0, 300)}`);
			}
			break;
		}

		const success = this.page.locator(
			'.jet-form-builder-message--success, .jet-form-builder__message--success, .jet-form-builder-messages-wrap'
		);
		await expect
			.poll(
				async () => {
					const text = ((await success.allTextContents().catch(() => [])) || []).join(' ').toLowerCase();
					const errors = (
						(await this.form
							.locator('.jet-form-builder__error, .error, .jet-form-builder-message--error')
							.allTextContents()) || []
					).join(' ');
					if (errors.trim()) {
						throw new Error(`WWW apply form errors: ${errors}`);
					}
					const nameEmpty = (await this.form.locator('#name').inputValue().catch(() => 'x')) === '';
					const bodyOk = /"status"\s*:\s*"success"|pomyślnie|pomyslnie|przesłan|przeslan/i.test(
						responseBody
					);
					return (
						bodyOk ||
						/pomyślnie|pomyslnie|przesłan|przeslan|dziek|dzięk|sukces|success|otrzym|thank/i.test(text) ||
						nameEmpty
					);
				},
				{ timeout: 30000, intervals: [500, 1000, 2000] }
			)
			.toBeTruthy();
	}

	async getHiddenProjectId(): Promise<string> {
		await this.openApplyForm();
		return this.form.locator('input[name="id_projektu"]').inputValue();
	}
}
