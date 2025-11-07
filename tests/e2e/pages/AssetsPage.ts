/**
 * Assets Page Object Model
 * 
 * Provides methods for interacting with the Assets module.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { Page, Locator, expect } from '@playwright/test';

export class AssetsPage {
  readonly page: Page;
  readonly searchInput: Locator;
  readonly assetsTable: Locator;
  readonly addAssetButton: Locator;

  constructor(page: Page) {
    this.page = page;
    
    // Common selectors for Assets module
    this.searchInput = page.locator('input[name="search"], input.listSearchContributor, input[data-list-search]');
    this.assetsTable = page.locator('table.listViewEntriesTable, .listViewEntries, [data-test="list-view-table"]');
    this.addAssetButton = page.locator('button:has-text("Add"), .addButton, [data-test="add-asset"]');
  }

  /**
   * Navigate to Assets list view
   */
  async goto() {
    await this.page.goto('/index.php?module=Assets&view=ListView&mid=88&parent=84');
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Search for assets using the inline search
   * @param searchTerm - Text to search for
   */
  async search(searchTerm: string) {
    const inlineSearchInput = this.page.locator('table tbody tr td input[type="text"]').first();
    
    if (await inlineSearchInput.isVisible({ timeout: 2000 })) {
      await inlineSearchInput.fill(searchTerm);
      await this.page.waitForLoadState('networkidle');
    }
  }

  /**
   * Get the number of assets currently displayed in the list
   * @returns Number of visible asset records
   */
  async getRecordCount(): Promise<number> {
    await this.assetsTable.first().waitFor({ state: 'visible', timeout: 10000 });
    
    // Count rows in the table (excluding header and search row)
    const rows = await this.page.locator('table tbody tr').count();
    // Subtract 1 for the search/filter row if it exists
    return rows > 1 ? rows - 1 : rows;
  }

  /**
   * Check if an asset with specific name/text exists in the current view
   * @param assetText - Text to look for in the assets list
   * @returns true if asset is found
   */
  async hasAsset(assetText: string): Promise<boolean> {
    const assetLocator = this.page.locator(`tr:has-text("${assetText}")`);
    return await assetLocator.count() > 0;
  }

  /**
   * Wait for the assets list to finish loading
   */
  async waitForListLoad() {
    await this.page.waitForSelector('.loading, .listViewLoadingImageBlock', { 
      state: 'hidden', 
      timeout: 10000 
    }).catch(() => {
      // If no loading indicator exists, that's fine
    });
    
    await this.page.waitForLoadState('networkidle');
  }
}

