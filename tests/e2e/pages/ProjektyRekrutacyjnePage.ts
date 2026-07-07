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
  readonly listViewRoot: Locator;
  readonly projectsTable: Locator;
  readonly customFilter: Locator;
  readonly salesStageSearch: Locator;
  readonly createdTimeSearch: Locator;
  readonly searchTrigger: Locator;

  constructor(page: Page) {
    this.page = page;

    this.listViewRoot = page.locator('.listViewPageDiv .listViewContentDiv').first();
    this.projectsTable = this.listViewRoot.locator('table.listViewEntriesTable');
    this.customFilter = page.locator('#customFilter');
    this.salesStageSearch = this.listViewRoot.locator('select.listSearchContributor[name="etap_sprzedazy"]');
    this.createdTimeSearch = this.listViewRoot.locator('input.listSearchContributor.dateField[name="createdtime"]');
    this.searchTrigger = this.listViewRoot.locator('[data-trigger="listSearch"]').first();
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
    await this.listViewRoot.waitFor({ state: 'visible', timeout: 10000 });
    const countValue = await this.listViewRoot.locator('#noOfEntries').inputValue();
    return parseInt(countValue || '0', 10);
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
   * Filter by "Czas utworzenia" (createdtime) date range and submit.
   * Uses the search button so the typed range is sent as-is (Enter syncs via datepicker).
   */
  async filterByCreatedTimeRange(startDate: string, endDate: string) {
    await this.createdTimeSearch.waitFor({ state: 'visible', timeout: 10000 });
    await this.createdTimeSearch.fill(`${startDate},${endDate}`);
    await this.searchTrigger.click();
    await this.waitForListReload('createdtime');
    await this.page.waitForFunction(
      () => {
        const input = document.querySelector('.listViewPageDiv .listViewContentDiv #noOfEntries') as HTMLInputElement | null;
        return input?.value === '0';
      },
      { timeout: 15000 }
    );
  }

  /**
   * Clear the createdtime inline search and submit (Enter or search button).
   */
  async clearCreatedTimeFilter(submitWithEnter = false, expectedCount?: number) {
    await this.createdTimeSearch.waitFor({ state: 'visible', timeout: 10000 });
    await this.createdTimeSearch.fill('');
    if (submitWithEnter) {
      await this.createdTimeSearch.press('Enter');
    } else {
      await this.searchTrigger.click();
    }
    if (typeof expectedCount === 'number') {
      await this.page.waitForFunction(
        (count) => {
          const input = document.querySelector('.listViewPageDiv .listViewContentDiv #noOfEntries') as HTMLInputElement | null;
          return input?.value === String(count);
        },
        expectedCount,
        { timeout: 15000 }
      );
    }
    await this.waitForListReload();
  }

  /**
   * Whether the current URL still carries a createdtime list-search param.
   */
  urlHasCreatedTimeSearchParam(): boolean {
    const url = new URL(this.page.url());
    const raw = url.searchParams.get('search_params');
    if (!raw) {
      return false;
    }
    try {
      const params = JSON.parse(decodeURIComponent(raw));
      if (!Array.isArray(params)) {
        return false;
      }
      for (const group of params) {
        if (!Array.isArray(group)) {
          continue;
        }
        for (const condition of group) {
          if (Array.isArray(condition) && condition[0] === 'createdtime') {
            return true;
          }
        }
      }
      return false;
    } catch {
      return false;
    }
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
  async waitForListReload(expectedUrlFragment?: string) {
    if (expectedUrlFragment) {
      await this.page.waitForURL(
        (url) => decodeURIComponent(url.href).includes(expectedUrlFragment),
        { timeout: 15000 }
      );
    }
    await this.page.waitForFunction(() => {
      const loader = document.querySelector('.mainContainer .contentsDiv #listViewContents #loadingListViewModal');
      return !loader || loader.classList.contains('hide');
    }, { timeout: 15000 });
    await this.page.waitForLoadState('networkidle');
    await this.projectsTable.first().waitFor({ state: 'visible', timeout: 10000 });
  }

}
