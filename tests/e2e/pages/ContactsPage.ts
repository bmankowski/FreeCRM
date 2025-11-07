/**
 * Contacts Page Object Model
 * 
 * Provides methods for interacting with the Contacts module.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { Page, Locator, expect } from '@playwright/test';

export class ContactsPage {
  readonly page: Page;
  readonly searchInput: Locator;
  readonly contactsTable: Locator;
  readonly addContactButton: Locator;

  constructor(page: Page) {
    this.page = page;
    
    // Common selectors for Contacts module
    this.searchInput = page.locator('input[name="search"], input.listSearchContributor, input[data-list-search]');
    this.contactsTable = page.locator('table.listViewEntriesTable, .listViewEntries, [data-test="list-view-table"]');
    this.addContactButton = page.locator('button:has-text("Add"), .addButton, [data-test="add-contact"]');
  }

  /**
   * Navigate to Contacts list view
   */
  async goto() {
    await this.page.goto('/index.php?module=Contacts&view=ListView&mid=49&parent=47');
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Search for contacts using the inline search
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
   * Get the number of contacts currently displayed in the list
   * @returns Number of visible contact records
   */
  async getRecordCount(): Promise<number> {
    await this.contactsTable.first().waitFor({ state: 'visible', timeout: 10000 });
    
    // Count rows in the table (excluding header and search row)
    const rows = await this.page.locator('table tbody tr').count();
    // Subtract 1 for the search/filter row if it exists
    return rows > 1 ? rows - 1 : rows;
  }

  /**
   * Check if a contact with specific name/text exists in the current view
   * @param contactText - Text to look for in the contacts list
   * @returns true if contact is found
   */
  async hasContact(contactText: string): Promise<boolean> {
    const contactLocator = this.page.locator(`tr:has-text("${contactText}")`);
    return await contactLocator.count() > 0;
  }

  /**
   * Get all visible contact names from the list
   * @returns Array of contact names
   */
  async getVisibleContactNames(): Promise<string[]> {
    const nameLinks = this.page.locator('table tbody tr td a.textOverflowEllipsis, table tbody tr .fieldValue a');
    
    const count = await nameLinks.count();
    const names: string[] = [];
    
    for (let i = 0; i < count; i++) {
      const text = await nameLinks.nth(i).textContent();
      if (text) {
        names.push(text.trim());
      }
    }
    
    return names;
  }

  /**
   * Wait for the contacts list to finish loading
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

