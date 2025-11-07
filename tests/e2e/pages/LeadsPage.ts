/**
 * Leads Page Object Model
 * 
 * Provides methods for interacting with the Leads module.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { Page, Locator, expect } from '@playwright/test';

export class LeadsPage {
  readonly page: Page;
  readonly searchInput: Locator;
  readonly filterButton: Locator;
  readonly leadsTable: Locator;
  readonly addLeadButton: Locator;

  constructor(page: Page) {
    this.page = page;
    
    // Common selectors for Leads module
    this.searchInput = page.locator('input[name="search"], input.listSearchContributor, input[data-list-search]');
    this.filterButton = page.locator('.js-filter-btn, button:has-text("Filter"), [data-test="filter-button"]');
    this.leadsTable = page.locator('table.listViewEntriesTable, .listViewEntries, [data-test="list-view-table"]');
    this.addLeadButton = page.locator('button:has-text("Add"), .addButton, [data-test="add-lead"]');
  }

  /**
   * Navigate to Leads list view
   */
  async goto() {
    await this.page.goto('/index.php?module=Leads&view=List');
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Search for leads using the search box
   * @param searchTerm - Text to search for
   */
  async search(searchTerm: string) {
    await this.searchInput.first().fill(searchTerm);
    await this.searchInput.first().press('Enter');
    
    // Wait for results to load
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Apply a filter by name
   * @param filterName - Name of the filter to apply (e.g., "All", "My Leads", "Hot Leads")
   */
  async applyFilter(filterName: string) {
    // Look for filter in the filter dropdown or list
    const filterOption = this.page.locator(`
      .filterName:has-text("${filterName}"),
      a:has-text("${filterName}"),
      [data-filter-name="${filterName}"],
      .select2-results__option:has-text("${filterName}")
    `).first();
    
    await filterOption.click();
    
    // Wait for the filtered results to load
    await this.page.waitForLoadState('networkidle');
    await this.page.waitForTimeout(500); // Small wait for UI update
  }

  /**
   * Get the number of leads currently displayed in the list
   * @returns Number of visible lead records
   */
  async getLeadCount(): Promise<number> {
    // Wait for table to be visible
    await this.leadsTable.first().waitFor({ state: 'visible', timeout: 10000 });
    
    // Count rows in the table (excluding header)
    const rows = await this.page.locator('table tbody tr, .listViewEntries tbody tr').count();
    return rows;
  }

  /**
   * Check if a lead with specific name/text exists in the current view
   * @param leadText - Text to look for in the leads list
   * @returns true if lead is found
   */
  async hasLead(leadText: string): Promise<boolean> {
    const leadLocator = this.page.locator(`tr:has-text("${leadText}")`);
    return await leadLocator.count() > 0;
  }

  /**
   * Get all visible lead names/titles from the list
   * @returns Array of lead names
   */
  async getVisibleLeadNames(): Promise<string[]> {
    // Adjust selector based on actual table structure
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
   * Select a custom filter from advanced filter UI
   * @param filterName - Name of custom filter
   */
  async selectCustomFilter(filterName: string) {
    // Click filter button/dropdown to open filter options
    const filterDropdown = this.page.locator('.filterActionsDiv, .customFilterButton, select.customFilter').first();
    
    if (await filterDropdown.isVisible({ timeout: 2000 })) {
      await filterDropdown.click();
      
      // Select the filter option
      const option = this.page.locator(`option:has-text("${filterName}"), li:has-text("${filterName}")`).first();
      await option.click();
      
      await this.page.waitForLoadState('networkidle');
    } else {
      // Fallback: try direct filter link
      await this.applyFilter(filterName);
    }
  }

  /**
   * Get current active filter name
   * @returns Name of currently active filter
   */
  async getActiveFilterName(): Promise<string> {
    const activeFilter = this.page.locator('.filterName.selected, .customFilter option:checked, .active.filterName').first();
    const text = await activeFilter.textContent();
    return text?.trim() || '';
  }

  /**
   * Check if "no records" message is displayed
   * @returns true if no records found message is visible
   */
  async hasNoRecordsMessage(): Promise<boolean> {
    const noRecordsLocator = this.page.locator('text=No records found, .noDataMsg, .emptyRecordsDiv');
    return await noRecordsLocator.isVisible({ timeout: 5000 }).catch(() => false);
  }

  /**
   * Wait for the leads list to finish loading
   */
  async waitForListLoad() {
    // Wait for loading indicator to disappear
    await this.page.waitForSelector('.loading, .listViewLoadingImageBlock', { 
      state: 'hidden', 
      timeout: 10000 
    }).catch(() => {
      // If no loading indicator exists, that's fine
    });
    
    await this.page.waitForLoadState('networkidle');
  }
}

