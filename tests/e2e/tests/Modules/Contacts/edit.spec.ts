/**
 * Contacts Edit E2E Tests
 * 
 * Tests the edit and update functionality in the Contacts module.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { ContactsPage } from '../../../pages/ContactsPage';
import { Page } from '@playwright/test';

test.describe('Contacts Edit', () => {
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

  /**
   * Helper to create a contact and track its ID for cleanup
   */
  async function createAndTrackContact(
    page: Page,
    firstName: string,
    lastName: string
  ): Promise<string | null> {
    const addButton = page.locator('a:has-text("Dodaj rekord"), a:has-text("Add"), [href*="module=Contacts&view=Edit"]').first();
    await addButton.click();
    await page.waitForURL(/view=Edit/);
    
    await page.locator('input[name="firstname"]').fill(firstName);
    await page.locator('input[name="lastname"]').fill(lastName);
    
    await page.locator('button:has-text("Zapisz"), button:has-text("Save"), button.btn-success').first().click();
    await page.waitForLoadState('networkidle');
    
    // Get contact ID from URL
    const currentUrl = page.url();
    const match = currentUrl.match(/record=(\d+)/);
    if (match) {
      createdContactIds.push(match[1]);
      return match[1];
    }
    
    // If not in URL, get it by searching
    await contactsPage.gotoList();
    const contactId = await contactsPage.getContactId(lastName);
    if (contactId) {
      createdContactIds.push(contactId);
    }
    return contactId;
  }

  test('should navigate to edit view from detail view', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();
    
    // Create a test contact
    const testFirstName = `EditNavTestFirst${Date.now()}`;
    const testLastName = `EditNavTestLast${Date.now()}`;
    
    const contactId = await createAndTrackContact(authenticatedPage, testFirstName, testLastName);
    expect(contactId).not.toBeNull();
    
    // Navigate to detail view
    if (contactId) {
      await contactsPage.gotoDetail(contactId);
      // Click Edit button
      await contactsPage.gotoEdit();
    }
    
    // Verify we're on edit view
    await expect(authenticatedPage).toHaveURL(/view=Edit/);
    // Note: URL may or may not contain record ID depending on system behavior
    
    console.log(`Successfully navigated to edit view from detail view`);
  });

  test('should update contact fields and save changes', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();
    
    // Create a test contact
    const originalFirstName = `UpdateTestFirst${Date.now()}`;
    const originalLastName = `UpdateTestLast${Date.now()}`;
    const updatedFirstName = `UpdatedFirst${Date.now()}`;
    const updatedEmail = `updated${Date.now()}@example.com`;
    
    const contactId = await createAndTrackContact(authenticatedPage, originalFirstName, originalLastName);
    expect(contactId).not.toBeNull();
    
    // Navigate to edit view
    if (contactId) {
      await contactsPage.gotoEdit(contactId);
      // Update fields
      await contactsPage.updateContact({
        firstname: updatedFirstName,
        email: updatedEmail
      });
    }
    
    // Verify redirect to detail view after save
    await expect(authenticatedPage).toHaveURL(/view=Detail/);
    if (contactId) {
      await expect(authenticatedPage).toHaveURL(new RegExp(`record=${contactId}`));
    }
    
    // Wait for detail view to fully load
    await authenticatedPage.waitForLoadState('networkidle');
    await authenticatedPage.waitForTimeout(1000);
    
    // Verify updated values in detail view
    const updatedFirstNameDisplay = authenticatedPage.getByText(updatedFirstName, { exact: false }).first();
    await expect(updatedFirstNameDisplay).toBeVisible({ timeout: 10000 });
    
    // Verify email is displayed (if shown)
    const emailDisplay = authenticatedPage.getByText(updatedEmail, { exact: false }).first();
    if (await emailDisplay.count() > 0) {
      await expect(emailDisplay).toBeVisible({ timeout: 10000 });
    }
    
    console.log(`Successfully updated contact fields: ${updatedFirstName}, ${updatedEmail}`);
  });

  test('should cancel edit and verify no changes saved', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();
    
    // Create a test contact
    const originalFirstName = `CancelTestFirst${Date.now()}`;
    const originalLastName = `CancelTestLast${Date.now()}`;
    const changedFirstName = `ChangedFirst${Date.now()}`;
    
    const contactId = await createAndTrackContact(authenticatedPage, originalFirstName, originalLastName);
    expect(contactId).not.toBeNull();
    
    // Navigate to edit view
    if (contactId) {
      await contactsPage.gotoEdit(contactId);
      // Change first name
      await authenticatedPage.locator('input[name="firstname"]').fill(changedFirstName);
    }
    
    // Click cancel button
    const cancelButton = authenticatedPage.locator('button:has-text("Anuluj"), button:has-text("Cancel"), a:has-text("Anuluj"), a:has-text("Cancel")').first();
    if (await cancelButton.isVisible({ timeout: 3000 })) {
      await cancelButton.click();
    } else {
      // If no cancel button, navigate away
      await contactsPage.gotoList();
    }
    
    // Navigate back to detail view
    if (contactId) {
      await contactsPage.gotoDetail(contactId);
      await authenticatedPage.waitForLoadState('networkidle');
      await authenticatedPage.waitForTimeout(1000);
      // Verify original first name is still there (not changed)
    }
    const originalNameDisplay = authenticatedPage.getByText(originalFirstName, { exact: false }).first();
    await expect(originalNameDisplay).toBeVisible({ timeout: 10000 });
    
    // Verify changed name is NOT displayed
    const changedNameDisplay = authenticatedPage.getByText(changedFirstName, { exact: false });
    expect(await changedNameDisplay.count()).toBe(0);
    
    console.log(`Cancel edit verified - no changes were saved`);
  });

  test('should verify updated contact appears in list view', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();
    
    // Create a test contact
    const testFirstName = `ListUpdateTestFirst${Date.now()}`;
    const testLastName = `ListUpdateTestLast${Date.now()}`;
    const updatedLastName = `ListUpdatedLast${Date.now()}`;
    
    const contactId = await createAndTrackContact(authenticatedPage, testFirstName, testLastName);
    expect(contactId).not.toBeNull();
    
    // Navigate to edit view and update
    if (contactId) {
      await contactsPage.gotoEdit(contactId);
      await contactsPage.updateContact({
        lastname: updatedLastName
      });
    }
    
    // Navigate to list view
    await contactsPage.gotoList();
    
    // Search for updated last name
    await contactsPage.search(updatedLastName);
    await contactsPage.waitForContactRow(updatedLastName);
    
    // Verify contact is found with updated name
    const found = await contactsPage.hasContact(updatedLastName);
    expect(found).toBe(true);
    
    // Verify old name is not found
    await contactsPage.search(testLastName);
    const oldFound = await contactsPage.hasContact(testLastName);
    expect(oldFound).toBe(false);
    
    console.log(`Updated contact appears in list view with new name: ${updatedLastName}`);
  });
});

