/**
 * Accounts Page Object Model
 * 
 * Provides methods for interacting with the Accounts module.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { Page, Locator, expect } from '@playwright/test';

export class AccountsPage {
  readonly page: Page;
  readonly searchInput: Locator;
  readonly accountsTable: Locator;
  readonly addAccountButton: Locator;

  constructor(page: Page) {
    this.page = page;
    
    // Common selectors for Accounts module
    this.searchInput = page.locator('input[name="search"], input.listSearchContributor, input[data-list-search]');
    this.accountsTable = page.locator('table.listViewEntriesTable, .listViewEntries, [data-test="list-view-table"]');
    this.addAccountButton = page.locator('button:has-text("Add"), .addButton, [data-test="add-account"]');
  }

  /**
   * Navigate to Accounts list view
   */
  async goto() {
    await this.page.goto('/index.php?module=Accounts&view=ListView&mid=51&parent=47');
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Search for accounts using the search box
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
   * Get the number of accounts currently displayed in the list
   * @returns Number of visible account records
   */
  async getRecordCount(): Promise<number> {
    await this.accountsTable.first().waitFor({ state: 'visible', timeout: 10000 });
    
    // Count rows in the table (excluding header and search row)
    const rows = await this.page.locator('table tbody tr').count();
    // Subtract 1 for the search/filter row if it exists
    return rows > 1 ? rows - 1 : rows;
  }

  /**
   * Check if an account with specific name/text exists in the current view
   * @param accountText - Text to look for in the accounts list
   * @returns true if account is found
   */
  async hasAccount(accountText: string): Promise<boolean> {
    const accountLocator = this.page.locator(`tr:has-text("${accountText}")`);
    return await accountLocator.count() > 0;
  }

  /**
   * Wait for the accounts list to finish loading
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

