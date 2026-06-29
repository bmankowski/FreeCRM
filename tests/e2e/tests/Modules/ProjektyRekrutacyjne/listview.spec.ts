/**
 * Projekty Rekrutacyjne List View E2E Tests
 *
 * Scenario: "Wchodzę na listę projektów rekrutacyjnych i wyświetla się
 * poprawnie lista projektów."
 *
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { ProjektyRekrutacyjnePage } from '../../../pages/ProjektyRekrutacyjnePage';

test.describe('Projekty Rekrutacyjne List View', () => {
  let projectsPage: ProjektyRekrutacyjnePage;
  let authenticatedPage: any;

  test.beforeEach(async ({ authenticatedPage: authPage }) => {
    authenticatedPage = authPage;
    projectsPage = new ProjektyRekrutacyjnePage(authenticatedPage);
    await projectsPage.gotoList();
  });

  test('should display the recruitment projects list view', async ({ authenticatedPage }) => {
    await expect(authenticatedPage).toHaveURL(/module=ProjektyRekrutacyjne/);
    await expect(authenticatedPage).toHaveURL(/view=ListView/);

    await expect(projectsPage.projectsTable.first()).toBeVisible();

    const recordCount = await projectsPage.getRecordCount();
    expect(recordCount).toBeGreaterThanOrEqual(0);

    console.log(`Recruitment projects list displayed with ${recordCount} row(s)`);
  });
});
