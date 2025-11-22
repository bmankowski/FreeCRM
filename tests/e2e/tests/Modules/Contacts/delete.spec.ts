/**
 * Contacts Delete E2E Tests
 * 
 * Tests the contact deletion and recycle bin functionality in the Contacts module.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { ContactsPage } from '../../../pages/ContactsPage';

test.describe('Contacts Delete', () => {
  let contactsPage: ContactsPage;
  let createdContactIds: string[] = [];

  test.beforeEach(async ({ authenticatedPage }) => {
    contactsPage = new ContactsPage(authenticatedPage);
    createdContactIds = [];
    await contactsPage.gotoList();
  });

  test.afterEach(async () => {
    // Clean up all contacts created during this test
    // These contacts are already in recycle bin, so just permanently delete them
    if (createdContactIds.length > 0) {
      console.log(`Cleaning up ${createdContactIds.length} test contact(s) from recycle bin...`);
      for (const contactId of createdContactIds) {
        await contactsPage.permanentlyDeleteFromRecycleBin(contactId);
      }
      createdContactIds = [];
    }
  });

  test('should delete contact and verify it appears in recycle bin', async ({ authenticatedPage }) => {
    test.setTimeout(60000);
    await contactsPage.waitForListLoad();
    
    // Create a new contact for deletion
    const testFirstName = `DeleteTestFirst${Date.now()}`;
    const testLastName = `DeleteTestLast${Date.now()}`;
    
    // Click add contact button
    const addButton = authenticatedPage.locator('a:has-text("Dodaj rekord"), a:has-text("Add"), [href*="module=Contacts&view=Edit"]').first();
    await addButton.click();
    
    // Wait for edit form to load
    await authenticatedPage.waitForURL(/view=Edit/);
    
    // Fill in contact details
    await authenticatedPage.locator('input[name="firstname"]').fill(testFirstName);
    await authenticatedPage.locator('input[name="lastname"]').fill(testLastName);
    
    // Save the contact
    await authenticatedPage.locator('button:has-text("Zapisz"), button:has-text("Save"), button.btn-success').first().click();
    
    // Wait for save and redirect
    await authenticatedPage.waitForLoadState('networkidle');
    
    // Get contact ID from URL for cleanup
    const currentUrl = authenticatedPage.url();
    const match = currentUrl.match(/record=(\d+)/);
    let contactId: string | null = null;
    if (match) {
      contactId = match[1];
      createdContactIds.push(contactId);
    }
    
    // Go back to list view
    await contactsPage.gotoList();
    
    // If we didn't get ID from URL, get it by searching
    if (!contactId) {
      contactId = await contactsPage.getContactId(testLastName);
      if (contactId) {
        createdContactIds.push(contactId);
      }
    }
    
    console.log(`Successfully created contact for deletion: ${testFirstName} ${testLastName}`);
    
    // Search for the contact to make sure it's visible
    await contactsPage.search(testLastName);
    
    // Verify the contact exists before deletion
    await contactsPage.waitForContactRow(testLastName);
    const existsBeforeDelete = await contactsPage.hasContact(testLastName);
    expect(existsBeforeDelete).toBe(true);
    console.log(`Contact found before deletion: ${testLastName}`);
    
    // Delete the contact
    await contactsPage.deleteContact(testLastName);
    console.log(`Contact deleted: ${testLastName}`);
    
    // Navigate to Recycle Bin via sidebar link
    // The link automatically filters by current module (Contacts)
    await contactsPage.gotoRecycleBin();
    
    // Verify we're on Recycle Bin page with Contacts filter
    await expect(authenticatedPage).toHaveURL(/module=RecycleBin/);
    // The URL should contain sourceModule=Contacts if filtering works correctly
    // Note: The filter might already be set via URL parameter from the sidebar link
    
    // Search for the deleted contact in recycle bin
    await contactsPage.search(testLastName);
    
    // Verify the contact exists in recycle bin
    const foundInRecycleBin = await contactsPage.hasContactInRecycleBin(testLastName);
    expect(foundInRecycleBin).toBe(true);
    console.log(`Successfully found deleted contact in recycle bin: ${testLastName}`);
  });
});


