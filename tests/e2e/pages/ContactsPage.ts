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
  readonly lastnameSearchInput: Locator;

  constructor(page: Page) {
    this.page = page;
    
    // Common selectors for Contacts module
    this.searchInput = page.locator('input[name="search"], input.listSearchContributor, input[data-list-search]');
    this.contactsTable = page.locator('table.listViewEntriesTable, .listViewEntries, [data-test="list-view-table"]');
    this.addContactButton = page.locator('button:has-text("Add"), .addButton, [data-test="add-contact"]');
    this.lastnameSearchInput = page.locator('input.listSearchContributor[name="lastname"]');
  }

  /**
   * Navigate to Contacts list view
   */
  async goto() {
    const currentUrl = this.page.url();
    if (currentUrl.includes('module=Contacts') && currentUrl.includes('view=ListView')) {
      await this.waitForListLoad();
      return;
    }

    await this.page.goto('/index.php?module=Contacts&view=ListView&mid=49&parent=47', { waitUntil: 'domcontentloaded' });
    await this.waitForListLoad();
  }

  /**
   * Search for contacts using the inline search
   * @param searchTerm - Text to search for
   */
  async search(searchTerm: string) {
    // Find the lastname search field specifically
    if (!(await this.lastnameSearchInput.count())) {
      return;
    }

    await this.lastnameSearchInput.first().waitFor({ state: 'visible', timeout: 4000 });
    await this.lastnameSearchInput.fill(searchTerm);

    await Promise.all([
      this.lastnameSearchInput.press('Enter'),
      this.page.waitForLoadState('networkidle')
    ]);

    await this.waitForListLoad();
  }

  /**
   * Get the number of contacts currently displayed in the list
   * @returns Number of visible contact records
   */
  async getRecordCount(): Promise<number> {
    await this.contactsTable.first().waitFor({ state: 'visible', timeout: 10000 });
    
    const dataRows = this.contactsTable.locator('tbody tr').filter({
      hasNot: this.page.locator('input.listSearchContributor')
    });

    return await dataRows.count();
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
   * Wait until a contact row containing given text is visible
   */
  async waitForContactRow(contactText: string, timeout = 7000) {
    const contactRow = this.contactsTable.locator('tr', { hasText: contactText }).first();
    await contactRow.waitFor({ state: 'visible', timeout });
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
    await Promise.allSettled([
      this.page.waitForSelector('.loading, .listViewLoadingImageBlock', {
        state: 'hidden',
        timeout: 7000
      }),
      this.page.waitForLoadState('networkidle')
    ]);
  }

  /**
   * Delete a contact by navigating to detail view and clicking delete button
   * @param contactText - Text to identify the contact (e.g., lastname)
   */
  async deleteContact(contactText: string) {
    // First, search for the contact to make sure it's visible
    await this.search(contactText);
    
    // Find the row containing the contact text and click on it to go to detail view
    await this.waitForContactRow(contactText, 10000);
    const contactRow = this.contactsTable.locator('tr', { hasText: contactText }).first();
    
    // Click on the contact name link to go to detail view
    const contactLink = contactRow.locator('a.moduleColor_Contacts, a:has-text("' + contactText + '")').first();
    await contactLink.click();
    
    // Wait for detail view to load
    await this.page.waitForURL(/view=Detail/, { timeout: 10000 });
    
    // Find and click the delete button in detail view
    // Delete button has ID like Contacts_detailView_action_LBL_DELETE_RECORD
    const deleteButton = this.page.locator('#Contacts_detailView_action_LBL_DELETE_RECORD, button[onclick*="deleteRecord"]').first();
    await deleteButton.waitFor({ state: 'visible', timeout: 5000 });
    
    // Wait for confirmation modal to appear after clicking delete
    // Use Promise.all to wait for both the click and the modal
    const modal = this.page.locator('.bootbox .modal-dialog, .modal.show .modal-dialog, .modal.in .modal-dialog').first();
    await deleteButton.click();
    await modal.waitFor({ state: 'visible', timeout: 8000 });
    
    // Find and click the confirm button in the modal
    // Bootbox.confirm creates a modal with OK and Cancel buttons
    // The confirm button is the primary button (OK/Tak)
    // Try to find all buttons in the modal and click the confirm one
    // First, wait for any button to appear in the modal
    await modal.locator('button').first().waitFor({ state: 'attached', timeout: 10000 });
    
    // Get all buttons in the modal (excluding close button)
    const allButtons = modal.locator('button:not(.close)');
    const buttonCount = await allButtons.count();
    
    // Find the confirm button - it's usually the primary button or the button with OK/Tak text
    // Exclude Cancel/Anuluj buttons and close buttons
    let confirmButton = modal.locator('.btn-primary:not(.close)').first();
    
    // If no primary button found, try to find by text
    if (await confirmButton.count() === 0) {
      confirmButton = modal.locator('button:has-text("OK"):not(.close), button:has-text("Tak"):not(.close)').first();
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
    await modal.waitFor({ state: 'hidden', timeout: 5000 }).catch(() => {});
    await this.page.waitForURL(/view=ListView/, { timeout: 10000 });
    await this.waitForListLoad();
  }

  /**
   * Navigate to Recycle Bin (Kosz) module via sidebar link
   */
  async gotoRecycleBin() {
    // Find the "Lista rekordów" button by text content
    await this.page.goto('/index.php?module=RecycleBin&view=ListView&sourceModule=Contacts', {
      waitUntil: 'domcontentloaded'
    });
    
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

