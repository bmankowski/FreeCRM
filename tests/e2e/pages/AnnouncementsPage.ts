/**
 * Announcements Page Object Model
 * 
 * Provides methods for interacting with the Announcements module.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { Page, Locator, expect } from '@playwright/test';

export class AnnouncementsPage {
  readonly page: Page;
  readonly searchInput: Locator;
  readonly announcementsTable: Locator;
  readonly addAnnouncementButton: Locator;

  constructor(page: Page) {
    this.page = page;
    
    // Common selectors for Announcements module
    this.searchInput = page.locator('input[name="search"], input.listSearchContributor, input[data-list-search]');
    this.announcementsTable = page.locator('table.listViewEntriesTable, .listViewEntries, [data-test="list-view-table"]');
    this.addAnnouncementButton = page.locator('button:has-text("Add"), .addButton, [data-test="add-announcement"]');
  }

  /**
   * Navigate to Announcements list view
   */
  async goto() {
    await this.page.goto('/index.php?module=Announcements&view=ListView&mid=108&parent=84');
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Get the number of announcements currently displayed in the list
   * @returns Number of visible announcement records
   */
  async getRecordCount(): Promise<number> {
    await this.announcementsTable.first().waitFor({ state: 'visible', timeout: 10000 });
    
    // Count rows in the table (excluding header and search row)
    const rows = await this.page.locator('table tbody tr').count();
    // Subtract 1 for the search/filter row if it exists
    return rows > 1 ? rows - 1 : rows;
  }

  /**
   * Check if an announcement with specific text exists in the current view
   * @param announcementText - Text to look for in the announcements list
   * @returns true if announcement is found
   */
  async hasAnnouncement(announcementText: string): Promise<boolean> {
    const announcementLocator = this.page.locator(`tr:has-text("${announcementText}")`);
    return await announcementLocator.count() > 0;
  }

  /**
   * Get all visible announcement titles from the list
   * @returns Array of announcement titles
   */
  async getVisibleAnnouncements(): Promise<string[]> {
    const titleLinks = this.page.locator('table tbody tr td a.textOverflowEllipsis, table tbody tr .fieldValue a');
    
    const count = await titleLinks.count();
    const titles: string[] = [];
    
    for (let i = 0; i < count; i++) {
      const text = await titleLinks.nth(i).textContent();
      if (text) {
        titles.push(text.trim());
      }
    }
    
    return titles;
  }

  /**
   * Wait for the announcements list to finish loading
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

