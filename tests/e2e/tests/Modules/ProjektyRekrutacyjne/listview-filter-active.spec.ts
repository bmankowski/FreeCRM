/**
 * Projekty Rekrutacyjne List View Filtering E2E Tests
 *
 * Scenario: "Wchodzę na listę projektów, na widoku »Wszystkie« filtruję ją do
 * projektów aktywnych (Etap sprzedaży = Aktywna) i sprawdzam, czy wyświetlają
 * się tylko projekty aktywne."
 *
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { ProjektyRekrutacyjnePage } from '../../../pages/ProjektyRekrutacyjnePage';

test.describe('Projekty Rekrutacyjne List View - filter by sales stage', () => {
  let projectsPage: ProjektyRekrutacyjnePage;

  test.beforeEach(async ({ authenticatedPage }) => {
    projectsPage = new ProjektyRekrutacyjnePage(authenticatedPage);
    await projectsPage.gotoList();
  });

  test('should display only active projects when filtering Etap sprzedaży on the "Wszystkie" view', async () => {
    await projectsPage.selectView('Wszystkie');

    await projectsPage.filterBySalesStage('Aktywna');

    const stages = await projectsPage.getVisibleSalesStages();

    expect(stages.length).toBeGreaterThan(0);
    for (const stage of stages) {
      expect(stage).toBe('Aktywna');
    }

    console.log(`Filtered to active projects - ${stages.length} row(s), all with stage "Aktywna"`);
  });
});
