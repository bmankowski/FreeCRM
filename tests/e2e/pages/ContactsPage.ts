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
    // Find the lastname search field specifically
    const lastnameSearchInput = this.page.locator('input.listSearchContributor[name="lastname"]');
    
    if (await lastnameSearchInput.isVisible({ timeout: 2000 })) {
      await lastnameSearchInput.fill(searchTerm);
      // Press Enter to trigger the search
      await lastnameSearchInput.press('Enter');
      await this.page.waitForLoadState('networkidle');
      // Wait a bit more for the search results to load
      await this.page.waitForTimeout(1000);
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

  /**
   * Delete a contact by navigating to detail view and clicking delete button
   * @param contactText - Text to identify the contact (e.g., lastname)
   */
  async deleteContact(contactText: string) {
    // First, search for the contact to make sure it's visible
    await this.search(contactText);
    await this.waitForListLoad();
    
    // Find the row containing the contact text and click on it to go to detail view
    const contactRow = this.page.locator(`tr:has-text("${contactText}")`).first();
    await contactRow.waitFor({ state: 'visible', timeout: 5000 });
    
    // Click on the contact name link to go to detail view
    const contactLink = contactRow.locator('a.moduleColor_Contacts, a:has-text("' + contactText + '")').first();
    await contactLink.click();
    
    // Wait for detail view to load
    await this.page.waitForURL(/view=Detail/, { timeout: 10000 });
    await this.page.waitForLoadState('networkidle');
    
    // Find and click the delete button in detail view
    // Delete button has ID like Contacts_detailView_action_LBL_DELETE_RECORD
    const deleteButton = this.page.locator('#Contacts_detailView_action_LBL_DELETE_RECORD, button[onclick*="deleteRecord"]').first();
    await deleteButton.waitFor({ state: 'visible', timeout: 5000 });
    
    // Wait for confirmation modal to appear after clicking delete
    // Use Promise.all to wait for both the click and the modal
    await Promise.all([
      deleteButton.click(),
      this.page.waitForSelector('.bootbox, .modal-dialog', { state: 'attached', timeout: 5000 })
    ]);
    
    // Wait a bit for the modal to be fully visible and rendered
    await this.page.waitForTimeout(2000);
    
    // Find and click the confirm button in the modal
    // Bootbox.confirm creates a modal with OK and Cancel buttons
    // The confirm button is the primary button (OK/Tak)
    // Try to find all buttons in the modal and click the confirm one
    // First, wait for any button to appear in the modal
    await this.page.waitForSelector('.bootbox button, .modal-dialog button', { state: 'attached', timeout: 10000 });
    
    // Get all buttons in the modal (excluding close button)
    const allButtons = this.page.locator('.bootbox button:not(.close), .modal-dialog button:not(.close)');
    const buttonCount = await allButtons.count();
    
    // Find the confirm button - it's usually the primary button or the button with OK/Tak text
    // Exclude Cancel/Anuluj buttons and close buttons
    let confirmButton = this.page.locator('.bootbox .btn-primary:not(.close), .modal-dialog .btn-primary:not(.close)').first();
    
    // If no primary button found, try to find by text
    if (await confirmButton.count() === 0) {
      confirmButton = this.page.locator('.bootbox button:has-text("OK"):not(.close), .bootbox button:has-text("Tak"):not(.close), .modal-dialog button:has-text("OK"):not(.close), .modal-dialog button:has-text("Tak"):not(.close)').first();
    }
    
    // If still no button found, use the first button that's not Cancel/Anuluj/Close
    if (await confirmButton.count() === 0 && buttonCount > 0) {
      for (let i = 0; i < buttonCount; i++) {
        const button = allButtons.nth(i);
        const text = await button.textContent();
        const className = await button.getAttribute('class') || '';
        if (text && !text.includes('Anuluj') && !text.includes('Cancel') && !className.includes('close')) {
          confirmButton = button;
          break;
        }
      }
    }
    
    // Wait for button to be visible and clickable
    await confirmButton.waitFor({ state: 'visible', timeout: 5000 });
    await confirmButton.click();
    
    // Wait for modal to disappear and deletion to complete
    // After deletion, we should be redirected to list view
    await this.page.waitForURL(/view=ListView/, { timeout: 10000 });
    await this.page.waitForLoadState('networkidle');
    await this.waitForListLoad();
  }

  /**
   * Navigate to Recycle Bin (Kosz) module via sidebar link
   */
  async gotoRecycleBin() {
    // Find the "Lista rekordów" button by text content
    const buttons = this.page.locator('button');
    const buttonCount = await buttons.count();
    let listViewButton: Locator | null = null;
    
    for (let i = 0; i < buttonCount; i++) {
      const button = buttons.nth(i);
      const text = await button.textContent();
      if (text && text.includes('Lista rekordów')) {
        listViewButton = button;
        break;
      }
    }
    
    if (!listViewButton) {
      throw new Error('Could not find "Lista rekordów" button');
    }
    
    await listViewButton.waitFor({ state: 'visible', timeout: 5000 });
    await listViewButton.click();
    
    // Wait for dropdown menu to appear and be visible
    await this.page.waitForSelector('.dropdown-menu:has(a[href*="RecycleBin"])', { state: 'visible', timeout: 3000 });
    await this.page.waitForTimeout(500);
    
    // Find the RecycleBin link - it should be in a dropdown menu
    // Look for link with text "RecycleBin" or "Kosz" or href containing RecycleBin
    const recycleBinLink = this.page.locator('a:has-text("RecycleBin"), a:has-text("Kosz"), a[href*="RecycleBin"]').first();
    // Wait for link to be attached
    await recycleBinLink.waitFor({ state: 'attached', timeout: 3000 });
    
    // Navigate directly using the href attribute if available, otherwise click
    const href = await recycleBinLink.getAttribute('href');
    if (href) {
      await this.page.goto(href.startsWith('http') ? href : '/' + href);
    } else {
      // Fallback to clicking
      await recycleBinLink.click({ force: true });
    }
    
    // Wait for Recycle Bin page to load
    await this.page.waitForURL(/module=RecycleBin/, { timeout: 10000 });
    await this.page.waitForLoadState('networkidle');
    await this.waitForListLoad();
  }

  /**
   * Check if a contact exists in the Recycle Bin
   * @param contactText - Text to look for in the recycle bin
   * @returns true if contact is found in recycle bin
   */
  async hasContactInRecycleBin(contactText: string): Promise<boolean> {
    const contactLocator = this.page.locator(`tr:has-text("${contactText}")`);
    return await contactLocator.count() > 0;
  }
}

