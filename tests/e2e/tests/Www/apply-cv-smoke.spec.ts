/**
 * WWW → FreeCRM CV apply smoke (Playwright).
 *
 * Submits an application on www.itconnect.pl and polls test CRM until
 * RecruitmentApplication (phase A) and Candidates + project relation (phase B) appear.
 *
 * CleanTalk validates mailbox existence — use a real @itconnect.pl address.
 * Uniqueness comes from candidate_name / message marker (timestamp).
 *
 * Run (from tests/e2e):
 *   CRM_BASE_URL=https://test.itconnect.pl npm run pw -- test tests/Www/apply-cv-smoke.spec.ts --reporter=list
 *
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import path from 'path';
import { test, expect } from '@playwright/test';
import { login } from '../../helpers/login';
import { ItConnectOfferPage } from '../../pages/ItConnectOfferPage';
import { CandidatesPage, RecruitmentApplicationPage } from '../../pages/CrmRecruitmentPages';

const CRM_BASE_URL = (process.env.CRM_BASE_URL || 'https://test.itconnect.pl').replace(/\/$/, '');
const WWW_OFFER_URL =
	process.env.WWW_OFFER_URL || 'https://www.itconnect.pl/oferta/tester-manualny/';
const APPLY_EMAIL = process.env.WWW_APPLY_EMAIL || 'bmankowski@itconnect.pl';
const CV_PATH = path.resolve(__dirname, '../../test-data/e2e-cv-min.pdf');

/** File sync (~2×60s) + phase A/B (~2×60s) — leave headroom for clock skew. */
const IMPORT_POLL_TIMEOUT_MS = Number(process.env.CV_IMPORT_POLL_MS || 480_000);

test.use({
	launchOptions: {
		args: ['--disable-blink-features=AutomationControlled'],
	},
});

test.describe('WWW CV apply → CRM import smoke', () => {
	test.describe.configure({ mode: 'serial', timeout: IMPORT_POLL_TIMEOUT_MS + 180_000 });

	test('submits offer form and finds application + candidate on test CRM', async ({
		browser,
	}) => {
		const ts = Date.now();
		const fullName = `Test E2E ${ts}`;
		const email = APPLY_EMAIL;
		const phone = '501606752';
		const message = `Playwright E2E apply ${ts}`;

		const wwwContext = await browser.newContext({
			locale: 'pl-PL',
			viewport: { width: 1440, height: 900 },
			userAgent:
				'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
			extraHTTPHeaders: { 'Accept-Language': 'pl-PL,pl;q=0.9' },
		});
		await wwwContext.addInitScript(() => {
			Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
		});
		const wwwPage = await wwwContext.newPage();
		const offer = new ItConnectOfferPage(wwwPage);

		await offer.goto(WWW_OFFER_URL);
		await offer.fillAndSubmit({
			fullName,
			email,
			phone,
			message,
			availableFrom: 'Od zaraz',
			preferredContractType: 'B2B',
			expectedSalary: '15000',
			futureRecruitmentConsent: true,
			cvPath: CV_PATH,
		});
		const projectId = await offer.lastSubmittedProjectId;
		expect(projectId).toMatch(/^\d+$/);
		await wwwContext.close();

		const crmContext = await browser.newContext({
			baseURL: CRM_BASE_URL,
			locale: 'pl-PL',
		});
		const crmPage = await crmContext.newPage();
		await login(crmPage, CRM_BASE_URL);

		const applications = new RecruitmentApplicationPage(crmPage);
		await applications.waitForCandidateName(fullName, IMPORT_POLL_TIMEOUT_MS);
		await applications.openRowContaining(fullName);

		await applications.expectDetailContains('candidate_email', email);
		await applications.expectDetailContains('candidate_name', fullName);
		await applications.expectDetailContains('job_title', /Tester manualny/i);

		const candidates = new CandidatesPage(crmPage);
		await candidates.waitForName(fullName, IMPORT_POLL_TIMEOUT_MS);
		await candidates.openRowContaining(fullName);
		await expect(crmPage.getByRole('heading', { name: fullName, exact: true })).toBeVisible();
		await expect(crmPage.getByRole('link', { name: email })).toBeVisible();

		await expect
			.poll(
				async () => {
					try {
						await candidates.expectRelatedProjectContaining(
							new RegExp(`Tester manualny|${projectId}`, 'i')
						);
						return true;
					} catch {
						await crmPage.reload({ waitUntil: 'domcontentloaded' });
						return false;
					}
				},
				{ timeout: IMPORT_POLL_TIMEOUT_MS, intervals: [10_000, 15_000] }
			)
			.toBeTruthy();

		await crmContext.close();
	});
});
