/**
 * Projekty Rekrutacyjne List View — date/datetime inline search E2E Tests
 *
 * Verifies that clearing a date-range filter and pressing Enter reloads the list
 * without the filter (createdtime / "Czas utworzenia").
 *
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { ProjektyRekrutacyjnePage } from '../../../pages/ProjektyRekrutacyjnePage';

test.describe('Projekty Rekrutacyjne List View - createdtime filter', () => {
  let projectsPage: ProjektyRekrutacyjnePage;

  test.beforeEach(async ({ authenticatedPage }) => {
    projectsPage = new ProjektyRekrutacyjnePage(authenticatedPage);
    await projectsPage.gotoList();
    await projectsPage.selectView('Wszystkie');
  });

  test('should remove createdtime filter after clearing the field and pressing Enter', async () => {
    const baselineCount = await projectsPage.getRecordCount();
    expect(baselineCount).toBeGreaterThan(0);

    await projectsPage.filterByCreatedTimeRange('2099-01-01', '2099-12-31');

    const filteredCount = await projectsPage.getRecordCount();
    expect(filteredCount).toBe(0);
    expect(projectsPage.urlHasCreatedTimeSearchParam()).toBe(true);

    await projectsPage.clearCreatedTimeFilter(true, baselineCount);

    const restoredCount = await projectsPage.getRecordCount();
    expect(restoredCount).toBe(baselineCount);
    expect(projectsPage.urlHasCreatedTimeSearchParam()).toBe(false);
  });

  test('should remove createdtime filter after clearing the field and clicking search', async () => {
    const baselineCount = await projectsPage.getRecordCount();
    expect(baselineCount).toBeGreaterThan(0);

    await projectsPage.filterByCreatedTimeRange('2099-01-01', '2099-12-31');

    expect(await projectsPage.getRecordCount()).toBe(0);

    await projectsPage.clearCreatedTimeFilter(false, baselineCount);

    expect(await projectsPage.getRecordCount()).toBe(baselineCount);
    expect(projectsPage.urlHasCreatedTimeSearchParam()).toBe(false);
  });
});
