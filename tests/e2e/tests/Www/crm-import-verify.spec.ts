/**
 * CRM-side check for a WWW-imported application (no CleanTalk / no www submit).
 *
 * Useful after a manual or smoke WWW apply, or to validate list/detail selectors.
 *
 *   E2E_KNOWN_NAME='Test E2E 1784647223624' npm run pw -- test tests/Www/crm-import-verify.spec.ts --reporter=list
 *
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '@playwright/test';
import { login } from '../../helpers/login';
import { CandidatesPage, RecruitmentApplicationPage } from '../../pages/CrmRecruitmentPages';

const CRM_BASE_URL = (process.env.CRM_BASE_URL || 'https://test.itconnect.pl').replace(/\/$/, '');
const fullName = process.env.E2E_KNOWN_NAME || 'Test E2E 1784647223624';
const email = process.env.WWW_APPLY_EMAIL || 'bmankowski@itconnect.pl';
const projectId = process.env.WWW_PROJECT_ID || '1449321';

test('CRM shows imported WWW application + candidate + project relation', async ({
	browser,
}) => {
	const ctx = await browser.newContext({ baseURL: CRM_BASE_URL, locale: 'pl-PL' });
	const page = await ctx.newPage();
	await login(page, CRM_BASE_URL);

	const apps = new RecruitmentApplicationPage(page);
	await apps.waitForCandidateName(fullName, 60_000);
	await apps.openRowContaining(fullName);
	await apps.expectDetailContains('candidate_email', email);
	await apps.expectDetailContains('candidate_name', fullName);
	await apps.expectDetailContains('job_title', /Tester manualny/i);

	const cands = new CandidatesPage(page);
	await cands.waitForName(fullName, 60_000);
	await cands.openRowContaining(fullName);
	await expect(page.getByRole('heading', { name: fullName, exact: true })).toBeVisible();
	await expect(page.getByRole('link', { name: email })).toBeVisible();
	await cands.expectRelatedProjectContaining(new RegExp(`Tester manualny|${projectId}`, 'i'));

	await ctx.close();
});
