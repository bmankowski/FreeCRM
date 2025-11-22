/**
 * Contacts Create E2E Tests
 * 
 * Tests the contact creation and search functionality in the Contacts module.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { ContactsPage } from '../../../pages/ContactsPage';

test.describe('Contacts Create', () => {
  let contactsPage: ContactsPage;
  let createdContactIds: string[] = [];

  test.beforeEach(async ({ authenticatedPage }) => {
    contactsPage = new ContactsPage(authenticatedPage);
    createdContactIds = [];
    await contactsPage.gotoList();
  });

  test.afterEach(async () => {
    // Clean up all contacts created during this test
    if (createdContactIds.length > 0) {
      console.log(`Cleaning up ${createdContactIds.length} test contact(s)...`);
      await contactsPage.cleanupContacts(createdContactIds);
      createdContactIds = [];
    }
  });

  test('should create and search for new contact', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();
    
    // Get initial record count
    const initialCount = await contactsPage.getRecordCount();
    console.log(`Initial contacts count: ${initialCount}`);
    
    // Create a new contact
    const testFirstName = `TestFirst${Date.now()}`;
    const testLastName = `TestLast${Date.now()}`;
    
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
    if (match) {
      createdContactIds.push(match[1]);
    }
    
    // Go back to list view
    await contactsPage.gotoList();
    
    // If we didn't get ID from URL, get it by searching
    if (createdContactIds.length === 0) {
      const contactId = await contactsPage.getContactId(testLastName);
      if (contactId) {
        createdContactIds.push(contactId);
      }
    }
    
    console.log(`Successfully created contact: ${testFirstName} ${testLastName}`);
    
    // Search for the newly created contact using the search field
    // Note: The contact may not be on the first page, but should be findable via search
    await contactsPage.search(testLastName);
    
    // Verify search found the contact
    await contactsPage.waitForContactRow(testLastName);
    const foundInSearch = await contactsPage.hasContact(testLastName);
    expect(foundInSearch).toBe(true);
    console.log(`Successfully found contact in search: ${testLastName}`);
  });
});


