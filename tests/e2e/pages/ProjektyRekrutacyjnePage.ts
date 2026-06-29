/**
 * Projekty Rekrutacyjne (Recruitment Projects) Page Object Model
 *
 * Provides methods for interacting with the ProjektyRekrutacyjne module.
 *
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { Page, Locator } from '@playwright/test';

export class ProjektyRekrutacyjnePage {
  readonly page: Page;
  readonly projectsTable: Locator;
  readonly customFilter: Locator;
  readonly salesStageSearch: Locator;
  readonly searchTrigger: Locator;

  constructor(page: Page) {
    this.page = page;

    this.projectsTable = page.locator('table.listViewEntriesTable, .listViewEntries, [data-test="list-view-table"]');
    this.customFilter = page.locator('#customFilter');
    this.salesStageSearch = page.locator('select.listSearchContributor[name="etap_sprzedazy"]');
    this.searchTrigger = page.locator('[data-trigger="listSearch"]').first();
  }

  /**
   * Navigate to the recruitment projects list view
   */
  async gotoList() {
    const currentUrl = this.page.url();
    if (currentUrl.includes('module=ProjektyRekrutacyjne') && currentUrl.includes('view=ListView')) {
      await this.waitForListLoad();
      return;
    }

    await this.page.goto('/index.php?module=ProjektyRekrutacyjne&view=ListView', { waitUntil: 'domcontentloaded' });
    await this.waitForListLoad();
  }

  /**
   * Wait for the list view to finish loading
   */
  async waitForListLoad() {
    await Promise.allSettled([
      this.page.waitForSelector('.loading, .listViewLoadingImageBlock', {
        state: 'hidden',
        timeout: 5000
      }),
      this.projectsTable.first().waitFor({ state: 'visible', timeout: 5000 })
    ]);
  }

  /**
   * Count the data rows currently shown in the list (excludes inline-search/header rows)
   */
  async getRecordCount(): Promise<number> {
    await this.projectsTable.first().waitFor({ state: 'visible', timeout: 10000 });

    const dataRows = this.projectsTable.locator('tbody tr').filter({
      hasNot: this.page.locator('input.listSearchContributor')
    });

    return await dataRows.count();
  }

  /**
   * Switch the active list filter (custom view) by its visible name, e.g. "Wszystkie".
   * The #customFilter <select> is wrapped by select2, so it is selected by label;
   * its native change handler reloads the list for the chosen view.
   */
  async selectView(viewName: string) {
    await this.customFilter.waitFor({ state: 'attached', timeout: 10000 });
    await this.customFilter.selectOption({ label: viewName }, { force: true });
    await this.waitForListReload();
  }

  /**
   * Filter the list by the "Etap sprzedaży" (sales stage) picklist and apply the search.
   * Auto-refresh on change is disabled in this instance, so the search trigger button
   * must be clicked to submit the inline list search.
   */
  async filterBySalesStage(stage: string) {
    await this.salesStageSearch.waitFor({ state: 'attached', timeout: 10000 });
    await this.salesStageSearch.selectOption({ label: stage }, { force: true });
    await this.searchTrigger.click();
    await this.waitForListReload();
  }

  /**
   * Resolve the column index of a list header by its visible label.
   */
  async getColumnIndex(headerLabel: string): Promise<number> {
    const headers = this.projectsTable.locator('thead th');
    const count = await headers.count();
    for (let i = 0; i < count; i++) {
      const text = (await headers.nth(i).textContent())?.trim();
      if (text === headerLabel) {
        return i;
      }
    }
    return -1;
  }

  /**
   * Read the "Etap sprzedaży" value of every visible data row.
   */
  async getVisibleSalesStages(): Promise<string[]> {
    const columnIndex = await this.getColumnIndex('Etap sprzedaży');
    if (columnIndex < 0) {
      throw new Error('Column "Etap sprzedaży" not found in the list view');
    }

    const rows = this.projectsTable.locator('tbody tr.listViewEntries');
    const rowCount = await rows.count();
    const stages: string[] = [];

    for (let i = 0; i < rowCount; i++) {
      const cellText = (await rows.nth(i).locator('td').nth(columnIndex).textContent())?.trim() ?? '';
      stages.push(cellText);
    }

    return stages;
  }

  /**
   * Wait for an AJAX-driven list reload (view switch or inline search) to settle.
   */
  async waitForListReload() {
    await this.page.waitForLoadState('networkidle');
    await Promise.allSettled([
      this.page.waitForSelector('.loading, .listViewLoadingImageBlock', {
        state: 'hidden',
        timeout: 5000
      }),
      this.projectsTable.first().waitFor({ state: 'visible', timeout: 5000 })
    ]);
  }
}
