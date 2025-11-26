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
  async gotoList() {
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

    // Press Enter and wait for list to reload
    await this.lastnameSearchInput.press('Enter');
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
    // Wait for loading indicator to disappear and table to be visible in parallel
    await Promise.allSettled([
      this.page.waitForSelector('.loading, .listViewLoadingImageBlock', {
        state: 'hidden',
        timeout: 5000
      }),
      this.contactsTable.first().waitFor({ state: 'visible', timeout: 5000 })
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

  /**
   * Navigate to contact detail view
   * @param contactId - Contact record ID
   */
  async gotoDetail(contactId: string) {
    await this.page.goto(`/index.php?module=Contacts&view=Detail&record=${contactId}`, {
      waitUntil: 'domcontentloaded'
    });
    await this.page.waitForURL(/view=Detail/, { timeout: 10000 });
    // Wait for detail view container to be visible
    await this.page.waitForSelector('.detailViewContainer, .detailViewContents, .detailView, h4.recordLabel', {
      state: 'visible',
      timeout: 5000
    }).catch(() => {});
  }

  /**
   * Navigate to contact edit view
   * @param contactId - Contact record ID (optional, if not provided navigates from current page)
   */
  async gotoEdit(contactId?: string) {
    if (contactId) {
      await this.page.goto(`/index.php?module=Contacts&view=Edit&record=${contactId}`, {
        waitUntil: 'domcontentloaded'
      });
    } else {
      // Click edit button from current page (detail view)
      // Use JavaScript to click if element is attached but considered hidden
      const editButton = this.page.locator('#Contacts_detailView_action_LBL_EDIT, a[href*="view=Edit"], button:has-text("Edytuj"), button:has-text("Edit")').first();
      await editButton.waitFor({ state: 'attached', timeout: 5000 });
      
      // Try regular click first, fall back to JavaScript click if needed
      try {
        await editButton.click({ timeout: 2000 });
      } catch {
        // If regular click fails, use JavaScript
        await this.page.evaluate(() => {
          const element = document.querySelector('#Contacts_detailView_action_LBL_EDIT') as HTMLElement;
          if (!element) {
            const link = document.querySelector('a[href*="view=Edit"]') as HTMLElement;
            if (link) {
              link.click();
            } else {
              throw new Error('Edit button not found');
            }
          } else {
            element.click();
          }
        });
      }
    }
    await this.page.waitForURL(/view=Edit/, { timeout: 10000 });
    // Wait for edit form to be visible instead of networkidle
    await this.page.waitForSelector('input[name="firstname"], input[name="lastname"]', {
      state: 'visible',
      timeout: 5000
    }).catch(() => {});
  }

  /**
   * Update contact fields
   * @param fields - Object with field names and values to update
   */
  async updateContact(fields: Record<string, string>) {
    for (const [fieldName, value] of Object.entries(fields)) {
      const fieldInput = this.page.locator(`input[name="${fieldName}"], textarea[name="${fieldName}"], select[name="${fieldName}"]`).first();
      await fieldInput.waitFor({ state: 'visible', timeout: 5000 });
      await fieldInput.fill(value);
    }
    
    // Save the contact
    const saveButton = this.page.locator('button:has-text("Zapisz"), button:has-text("Save"), button.btn-success[type="submit"]').first();
    await saveButton.waitFor({ state: 'visible', timeout: 5000 });
    await saveButton.click();
    
    // Wait for redirect - use networkidle (default timeout is usually fine)
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Get contact record ID from list view by searching for contact text
   * @param contactText - Text to identify the contact
   * @param skipSearch - If true, skip searching (assumes contact is already visible)
   * @returns Contact record ID or null
   */
  async getContactId(contactText: string, skipSearch: boolean = false): Promise<string | null> {
    if (!skipSearch) {
      await this.search(contactText);
    }
    // Always wait for contact row to be visible, even if we skip search
    await this.waitForContactRow(contactText);
    
    const contactRow = this.contactsTable.locator('tr', { hasText: contactText }).first();
    const contactLink = contactRow.locator('a.moduleColor_Contacts, a[href*="view=Detail"]').first();
    
    const href = await contactLink.getAttribute('href');
    if (href) {
      const match = href.match(/record=(\d+)/);
      if (match) {
        return match[1];
      }
    }
    return null;
  }

  /**
   * Sort list by column
   * @param columnName - Name of column to sort by
   * @param direction - Sort direction ('asc' or 'desc')
   */
  async sortBy(columnName: string, direction: 'asc' | 'desc' = 'asc') {
    // Find the column header
    const columnHeader = this.page.locator(`th:has-text("${columnName}"), th[data-columnname="${columnName}"]`).first();
    await columnHeader.waitFor({ state: 'visible', timeout: 5000 });
    
    // Click to sort - may need multiple clicks to get desired direction
    const currentSort = await columnHeader.getAttribute('data-sort');
    if (currentSort !== direction) {
      await columnHeader.click();
      // If we need opposite direction, click again
      if (direction === 'desc' && currentSort === 'asc') {
        await this.page.waitForTimeout(500);
        await columnHeader.click();
      }
    }
    
    await this.waitForListLoad();
  }

  /**
   * Filter list by field value
   * @param field - Field name to filter by
   * @param value - Value to filter with
   */
  async filterBy(field: string, value: string) {
    const filterInput = this.page.locator(`input.listSearchContributor[name="${field}"], input[name="${field}"].listSearchContributor`).first();
    await filterInput.waitFor({ state: 'visible', timeout: 5000 });
    await filterInput.fill(value);
    await filterInput.press('Enter');
    await this.waitForListLoad();
  }

  /**
   * Navigate to specific page in pagination
   * @param pageNumber - Page number to navigate to
   */
  async goToPage(pageNumber: number) {
    const pageLink = this.page.locator(`.pagination a:has-text("${pageNumber}"), .pagination .page-link:has-text("${pageNumber}")`).first();
    if (await pageLink.isVisible({ timeout: 2000 })) {
      await pageLink.click();
      await this.waitForListLoad();
    }
  }

  /**
   * Get current page number from pagination
   * @returns Current page number or 1 if not found
   */
  async getCurrentPage(): Promise<number> {
    const activePage = this.page.locator('.pagination .active, .pagination .page-item.active').first();
    if (await activePage.isVisible({ timeout: 2000 })) {
      const pageText = await activePage.textContent();
      const pageNum = parseInt(pageText || '1', 10);
      return isNaN(pageNum) ? 1 : pageNum;
    }
    return 1;
  }

  /**
   * Search by specific field
   * @param field - Field name to search in
   * @param value - Value to search for
   */
  async searchByField(field: string, value: string) {
    const searchInput = this.page.locator(`input.listSearchContributor[name="${field}"]`).first();
    if (await searchInput.isVisible({ timeout: 4000 })) {
      await searchInput.fill(value);
      await searchInput.press('Enter');
      await this.waitForListLoad();
    }
  }

  /**
   * Link contact to an account
   * @param accountName - Name of the account to link
   */
  async linkToAccount(accountName: string) {
    // Find the Member Of / parentid field
    const memberOfField = this.page.locator('input[name="parent_id"], input[name="parentid"], input[data-field-name="parent_id"]').first();
    await memberOfField.waitFor({ state: 'visible', timeout: 5000 });
    
    // Click to open lookup/popup
    const lookupButton = memberOfField.locator('..').locator('.input-group-addon, .lookup-icon, button[data-toggle="modal"]').first();
    if (await lookupButton.isVisible({ timeout: 2000 })) {
      await lookupButton.click();
    } else {
      // Try clicking the field itself to trigger lookup
      await memberOfField.click();
    }
    
    // Wait for popup/modal to appear
    const popup = this.page.locator('.modal.show, .modal.in, .popupContainer').first();
    await popup.waitFor({ state: 'visible', timeout: 5000 });
    
    // Search for account in popup
    const popupSearch = popup.locator('input[name="search_text"], input.searchBox').first();
    await popupSearch.fill(accountName);
    await popupSearch.press('Enter');
    await this.page.waitForTimeout(1000);
    
    // Click on the account in results
    const accountLink = popup.locator(`tr:has-text("${accountName}") a, a:has-text("${accountName}")`).first();
    await accountLink.waitFor({ state: 'visible', timeout: 5000 });
    await accountLink.click();
    
    // Wait for popup to close
    await popup.waitFor({ state: 'hidden', timeout: 5000 }).catch(() => {});
  }

  /**
   * Permanently delete a contact from the recycle bin
   * @param contactId - Contact record ID to permanently delete
   */
  async permanentlyDeleteFromRecycleBin(contactId: string) {
    try {
      // First try direct API call - this works even if contact is not visible in recycle bin list
      try {
        // Use request instead of goto since EmptyRecordBin returns JSON, not HTML
        const response = await this.page.request.get(`/index.php?module=RecycleBin&action=EmptyRecordBin&record=${contactId}&sourceModule=Contacts`, {
          timeout: 10000
        });
        
        if (response.ok()) {
          const responseText = await response.text();
          // Check if response indicates success (JSON with success: true or result)
          if (responseText.includes('"success"') || responseText.includes('"result"') || response.status() === 200) {
            console.log(`Successfully deleted contact ${contactId} via direct API`);
            return; // Successfully deleted via direct API
          }
        }
      } catch (directError) {
        // If direct API fails, try UI method
        console.log(`Direct API delete failed for ${contactId}, trying UI method: ${directError}`);
      }
      
      // Fallback: Navigate to recycle bin and use UI
      await this.gotoRecycleBin();
      
      // Search for the contact if needed
      await this.page.waitForTimeout(1000);
      
      // Find the row with this contact ID
      const contactRow = this.page.locator(`tr[data-id="${contactId}"], tr:has(input[value="${contactId}"])`).first();
      
      // If not found, try searching by ID in hidden inputs
      if (await contactRow.count() === 0) {
        // Try to find by checking all rows for the ID
        const allRows = this.page.locator('tbody tr');
        const rowCount = await allRows.count();
        let found = false;
        
        for (let i = 0; i < rowCount; i++) {
          const row = allRows.nth(i);
          const rowId = await row.getAttribute('data-id');
          if (rowId === contactId) {
            found = true;
            const checkbox = row.locator('input[type="checkbox"]').first();
            if (await checkbox.isVisible({ timeout: 1000 })) {
              await checkbox.check();
              
              // Click permanent delete button
              const permanentDeleteButton = this.page.locator('button:has-text("Usuń na stałe"), button:has-text("Permanently delete"), .btn:has-text("Empty"), #emptyRecordbinButton').first();
              if (await permanentDeleteButton.isVisible({ timeout: 3000 })) {
                await permanentDeleteButton.click();
                
                // Confirm deletion in modal
                const modal = this.page.locator('.bootbox .modal-dialog, .modal.show .modal-dialog').first();
                if (await modal.isVisible({ timeout: 3000 })) {
                  const confirmButton = modal.locator('.btn-primary, button:has-text("OK"), button:has-text("Tak")').first();
                  await confirmButton.click();
                  await modal.waitFor({ state: 'hidden', timeout: 5000 }).catch(() => {});
                }
              }
            }
            break;
          }
        }
        
        if (!found) {
          console.log(`Contact ${contactId} not found in recycle bin - may already be deleted`);
        }
        return;
      }
      
      // Check the checkbox for this record
      const checkbox = contactRow.locator('input[type="checkbox"]').first();
      await checkbox.check();
      
      // Click permanent delete button
      const permanentDeleteButton = this.page.locator('button:has-text("Usuń na stałe"), button:has-text("Permanently delete"), .btn:has-text("Empty"), #emptyRecordbinButton').first();
      if (await permanentDeleteButton.isVisible({ timeout: 3000 })) {
        await permanentDeleteButton.click();
        
        // Confirm deletion in modal
        const modal = this.page.locator('.bootbox .modal-dialog, .modal.show .modal-dialog').first();
        if (await modal.isVisible({ timeout: 3000 })) {
          const confirmButton = modal.locator('.btn-primary, button:has-text("OK"), button:has-text("Tak")').first();
          await confirmButton.click();
          await modal.waitFor({ state: 'hidden', timeout: 5000 }).catch(() => {});
        }
      }
    } catch (error) {
      console.log(`Failed to permanently delete contact ${contactId} from recycle bin: ${error}`);
    }
  }

  /**
   * Delete a contact by ID and permanently remove it from the system
   * @param contactId - Contact record ID
   */
  async deleteContactById(contactId: string) {
    try {
      // First, try to delete normally if the contact exists
      try {
        await this.page.goto(`/index.php?module=Contacts&view=Detail&record=${contactId}`, {
          waitUntil: 'domcontentloaded',
          timeout: 5000
        });
        
        // Delete the contact
        const deleteButton = this.page.locator('#Contacts_detailView_action_LBL_DELETE_RECORD, button[onclick*="deleteRecord"]').first();
        if (await deleteButton.isVisible({ timeout: 2000 })) {
          await deleteButton.click();

          // Confirm deletion (Bootbox modal sometimes animates, so always wait for it)
          const modal = this.page.locator('.bootbox .modal-dialog, .modal.show .modal-dialog').first();
          try {
            await modal.waitFor({ state: 'visible', timeout: 5000 });
            const confirmButton = modal
              .locator(
                [
                  'button[data-bb-handler="confirm"]',
                  '.modal-footer .btn-danger:not(.close)',
                  '.modal-footer .btn-primary:not(.close)',
                  'button:has-text("Usuń")',
                  'button:has-text("Delete")',
                  'button:has-text("Tak")',
                  'button:has-text("Yes")',
                  'button:has-text("OK")'
                ].join(', ')
              )
              .first();
            await confirmButton.waitFor({ state: 'visible', timeout: 5000 });
            await confirmButton.click();
            await modal.waitFor({ state: 'hidden', timeout: 7000 }).catch(() => {});
            await this.page.waitForTimeout(500); // Allow backend to flag record as deleted
          } catch (confirmError) {
            console.log(`Delete confirmation modal did not complete for ${contactId}: ${confirmError}`);
          }
        }
      } catch (deleteError) {
        // If normal delete fails, that's okay - we'll try to remove from recycle bin anyway
        console.log(`Regular delete failed for ${contactId}, will try recycle bin cleanup`);
      }
      
      // Now permanently delete from recycle bin using direct API call
      try {
        // Use request instead of goto since EmptyRecordBin returns JSON, not HTML
        const response = await this.page.request.get(`/index.php?module=RecycleBin&action=EmptyRecordBin&record=${contactId}&sourceModule=Contacts`, {
          timeout: 10000
        });
        
        if (response.ok()) {
          const responseText = await response.text();
          // Check if response indicates success
          if (responseText.includes('"success"') || responseText.includes('"result"') || response.status() === 200) {
            console.log(`Successfully deleted contact ${contactId} from recycle bin via direct API`);
          }
        }
      } catch (emptyError) {
        // If already deleted or not found, that's fine - will be handled by permanentlyDeleteFromRecycleBin
        console.log(`Direct recycle bin delete failed for ${contactId}, will try alternative method: ${emptyError}`);
      }
    } catch (error) {
      console.log(`Failed to delete contact ${contactId}: ${error}`);
    }
  }

  /**
   * Cleanup multiple contacts by their IDs
   * @param contactIds - Array of contact record IDs to delete
   */
  async cleanupContacts(contactIds: string[]) {
    for (const contactId of contactIds) {
      if (contactId) {
        try {
          console.log(`Cleaning up contact ID: ${contactId}`);
          
          // First try to delete normally if contact is still active
          await this.deleteContactById(contactId);
          console.log(`Completed deleteContactById for ${contactId}`);
          
          // Then ensure it's permanently removed from recycle bin
          // This handles cases where contact was already deleted or deleteContactById didn't fully work
          await this.permanentlyDeleteFromRecycleBin(contactId);
          console.log(`Completed permanentlyDeleteFromRecycleBin for ${contactId}`);
        } catch (error) {
          console.log(`Failed to cleanup contact ${contactId}: ${error}`);
        }
      }
    }
  }

  /**
   * Create a test contact and return its ID for cleanup
   * @param firstName - First name
   * @param lastName - Last name
   * @param additionalFields - Additional fields to fill
   * @returns Contact ID or null
   */
  async createTestContact(
    firstName: string,
    lastName: string,
    additionalFields?: Record<string, string>
  ): Promise<string | null> {
    try {
      // Navigate to list view first to ensure Add button is available
      await this.gotoList();
      
      // Wait for page to be fully loaded
      await this.page.waitForLoadState('networkidle');
      
      // Navigate directly to Edit view URL to avoid button clicking issues
      await this.page.goto('/index.php?module=Contacts&view=Edit', { 
        waitUntil: 'networkidle',
        timeout: 15000 
      });
      
      // Wait for form to be fully ready
      await this.page.waitForSelector('input[name="firstname"]', { state: 'visible', timeout: 10000 });
      await this.page.waitForSelector('input[name="lastname"]', { state: 'visible', timeout: 10000 });
      await this.page.waitForTimeout(1000); // Additional wait for JavaScript initialization
      
      // Fill in contact details
      await this.page.locator('input[name="firstname"]').fill(firstName);
      await this.page.locator('input[name="lastname"]').fill(lastName);
      
      // Fill additional fields if provided
      if (additionalFields) {
        for (const [fieldName, value] of Object.entries(additionalFields)) {
          const fieldInput = this.page.locator(`input[name="${fieldName}"], textarea[name="${fieldName}"]`).first();
          if (await fieldInput.isVisible({ timeout: 2000 })) {
            await fieldInput.fill(value);
          }
        }
      }
      
      // Save the contact
      await this.page.locator('button:has-text("Zapisz"), button:has-text("Save"), button.btn-success').first().click();
      await this.page.waitForLoadState('networkidle');
      
      // Get the contact ID from URL or by searching
      const currentUrl = this.page.url();
      const match = currentUrl.match(/record=(\d+)/);
      if (match) {
        return match[1];
      }
      
      // If not in URL, search for it
      await this.gotoList();
      return await this.getContactId(lastName);
    } catch (error) {
      console.log(`Failed to create test contact: ${error}`);
      return null;
    }
  }
}

