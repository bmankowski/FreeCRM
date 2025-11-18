/**
 * Contacts List View E2E Tests
 * 
 * Tests the list view functionality in the Contacts module.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { ContactsPage } from '../../../pages/ContactsPage';

test.describe('Contacts List View', () => {
  let contactsPage: ContactsPage;

  test.beforeEach(async ({ authenticatedPage }) => {
    contactsPage = new ContactsPage(authenticatedPage);
    await contactsPage.goto();
  });

  test('should display Contacts list view', async ({ authenticatedPage }) => {
    // Verify we're on the Contacts page
    await expect(authenticatedPage).toHaveURL(/module=Contacts/);
    
    // Verify the contacts table is visible
    await expect(contactsPage.contactsTable.first()).toBeVisible();
    
    console.log('Contacts list view displayed successfully');
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
    
    // Go back to list view
    await contactsPage.goto();
    await contactsPage.waitForListLoad();
    
    console.log(`Successfully created contact: ${testFirstName} ${testLastName}`);
    
    // Search for the newly created contact using the search field
    // Note: The contact may not be on the first page, but should be findable via search
    await contactsPage.search(testLastName);
    await contactsPage.waitForListLoad();
    
    // Verify search found the contact
    const foundInSearch = await contactsPage.hasContact(testLastName);
    expect(foundInSearch).toBe(true);
    console.log(`Successfully found contact in search: ${testLastName}`);
  });

  test('should delete contact and verify it appears in recycle bin', async ({ authenticatedPage }) => {
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
    
    // Go back to list view
    await contactsPage.goto();
    await contactsPage.waitForListLoad();
    
    console.log(`Successfully created contact for deletion: ${testFirstName} ${testLastName}`);
    
    // Search for the contact to make sure it's visible
    await contactsPage.search(testLastName);
    await contactsPage.waitForListLoad();
    
    // Verify the contact exists before deletion
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
    await contactsPage.waitForListLoad();
    
    // Verify the contact exists in recycle bin
    const foundInRecycleBin = await contactsPage.hasContactInRecycleBin(testLastName);
    expect(foundInRecycleBin).toBe(true);
    console.log(`Successfully found deleted contact in recycle bin: ${testLastName}`);
  });
});

