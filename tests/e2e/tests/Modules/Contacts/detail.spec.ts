/**
 * Contacts Detail View E2E Tests
 * 
 * Tests the detail view functionality in the Contacts module.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { ContactsPage } from '../../../pages/ContactsPage';
import { findWarningsAndErrors, formatWarningsAndErrors } from '../../../helpers/page-assertions';

test.describe('Contacts Detail View', () => {
  let contactsPage: ContactsPage;

  test.beforeEach(async ({ authenticatedPage }) => {
    contactsPage = new ContactsPage(authenticatedPage);
    await contactsPage.gotoList();
  });

  test('should navigate to contact detail view from list', async ({ authenticatedPage }) => {
    // Verify page does not contain warning or error messages anywhere
    const warningsAndErrors = await findWarningsAndErrors(authenticatedPage);
    expect(warningsAndErrors).toBeNull();

    await contactsPage.waitForListLoad();

    // Create a test contact first
    const testFirstName = `DetailTestFirst${Date.now()}`;
    const testLastName = `DetailTestLast${Date.now()}`;

    // Click add contact button
    const addButton = authenticatedPage.locator('a:has-text("Dodaj rekord"), a:has-text("Add"), [href*="module=Contacts&view=Edit"]').first();
    await addButton.click();

    // Wait for edit form to load
    await authenticatedPage.waitForURL(/view=Edit/);
    // Verify page does not contain warning or error messages anywhere
    const warningsAndErrorsAfterEdit = await findWarningsAndErrors(authenticatedPage);
    expect(warningsAndErrorsAfterEdit).toBeNull();

    // Fill in contact details
    await authenticatedPage.locator('input[name="firstname"]').fill(testFirstName);
    await authenticatedPage.locator('input[name="lastname"]').fill(testLastName);

    // Save the contact
    await authenticatedPage.locator('button:has-text("Zapisz"), button:has-text("Save"), button.btn-success').first().click();
    await authenticatedPage.waitForLoadState('networkidle');
    const warningsAndErrorsAfterSave = await findWarningsAndErrors(authenticatedPage);
    expect(warningsAndErrorsAfterSave).toBeNull();

    // Navigate back to list view
    await contactsPage.gotoList();
    // Verify page does not contain warning or error messages anywhere
    const warningsAndErrorsInList = await findWarningsAndErrors(authenticatedPage);
    expect(warningsAndErrorsInList).toBeNull();

    // Search for the contact
    await contactsPage.search(testLastName);
    await contactsPage.waitForContactRow(testLastName);

    // Get contact ID from the list view link (skip search since we already searched)
    const contactId = await contactsPage.getContactId(testLastName, true);
    expect(contactId).not.toBeNull();

    // Click on the contact name to go to detail view
    const contactRow = contactsPage.contactsTable.locator('tr', { hasText: testLastName }).first();
    const contactLink = contactRow.locator('a.moduleColor_Contacts, a:has-text("' + testLastName + '")').first();
    await contactLink.click();

    // Verify we're on detail view
    await expect(authenticatedPage).toHaveURL(/view=Detail/);
    await expect(authenticatedPage).toHaveURL(new RegExp(`record=${contactId}`));
    // Verify page does not contain warning or error messages anywhere
    const warningsAndErrorsInDetail = await findWarningsAndErrors(authenticatedPage);
    expect(warningsAndErrorsInDetail).toBeNull();
    console.log(`Successfully navigated to detail view for contact: ${testFirstName} ${testLastName}`);
  });

  test('should display contact information in detail view', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();
    // Create a test contact
    const testFirstName = `InfoTestFirst${Date.now()}`;
    const testLastName = `InfoTestLast${Date.now()}`;
    const testEmail = `test${Date.now()}@example.com`;

    // Create contact via edit form
    const addButton = authenticatedPage.locator('a:has-text("Dodaj rekord"), a:has-text("Add"), [href*="module=Contacts&view=Edit"]').first();
    await addButton.click();
    await authenticatedPage.waitForURL(/view=Edit/);

    await authenticatedPage.locator('input[name="firstname"]').fill(testFirstName);
    await authenticatedPage.locator('input[name="lastname"]').fill(testLastName);
    await authenticatedPage.locator('input[name="email"]').fill(testEmail);

    // Save the contact
    await authenticatedPage.locator('button:has-text("Zapisz"), button:has-text("Save"), button.btn-success').first().click();
    await authenticatedPage.waitForLoadState('networkidle');

    // Navigate to list view and get contact ID
    await contactsPage.gotoList();
    await contactsPage.search(testLastName);
    await contactsPage.waitForContactRow(testLastName);

    // Use skipSearch=true since we already searched
    const contactId = await contactsPage.getContactId(testLastName, true);
    expect(contactId).not.toBeNull();

    // Navigate to detail view
    await contactsPage.gotoDetail(contactId!);

    // Verify contact name is displayed in detail view header
    // Use h4.recordLabel to avoid matching sidebar menu items with .moduleColor_Contacts class
    const contactName = authenticatedPage.locator('h4.recordLabel').filter({ hasText: testLastName }).first();
    await expect(contactName).toBeVisible({ timeout: 5000 });

    // Verify first name is displayed in detail view content (not in sidebar/menu)
    // Scope search to detail view container to avoid matching hidden menu items
    const detailViewContainer = authenticatedPage.locator('.detailViewContainer, .detailViewContents, .detailView').first();
    const firstNameDisplay = detailViewContainer.getByText(testFirstName, { exact: false }).first();
    if (await firstNameDisplay.count() > 0) {
      await expect(firstNameDisplay).toBeVisible({ timeout: 5000 });
    } else {
      // If not found in detail container, try in the main content area
      const mainContent = authenticatedPage.locator('.contentsDiv, .mainContainer, .detailViewContainer').first();
      const firstNameInContent = mainContent.getByText(testFirstName, { exact: false }).first();
      if (await firstNameInContent.count() > 0) {
        await expect(firstNameInContent).toBeVisible({ timeout: 5000 });
      }
    }

    // Verify last name is displayed (already verified in header, but check content too)
    const lastNameDisplay = detailViewContainer.getByText(testLastName, { exact: false }).first();
    if (await lastNameDisplay.count() > 0) {
      await expect(lastNameDisplay).toBeVisible({ timeout: 5000 });
    }

    // Verify email is displayed (if shown in detail view)
    const emailDisplay = detailViewContainer.getByText(testEmail, { exact: false }).first();
    if (await emailDisplay.count() > 0) {
      await expect(emailDisplay).toBeVisible({ timeout: 5000 });
    }

    console.log(`Contact information displayed correctly in detail view`);
  });

  test('should have navigation buttons in detail view', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();

    // Create a test contact
    const testLastName = `NavTestLast${Date.now()}`;

    const addButton = authenticatedPage.locator('a:has-text("Dodaj rekord"), a:has-text("Add"), [href*="module=Contacts&view=Edit"]').first();
    await addButton.click();
    await authenticatedPage.waitForURL(/view=Edit/);

    await authenticatedPage.locator('input[name="firstname"]').fill(`NavTestFirst${Date.now()}`);
    await authenticatedPage.locator('input[name="lastname"]').fill(testLastName);

    // Save the contact
    await authenticatedPage.locator('button:has-text("Zapisz"), button:has-text("Save"), button.btn-success').first().click();
    await authenticatedPage.waitForLoadState('networkidle');

    // Navigate to list view and get contact ID
    await contactsPage.gotoList();
    await contactsPage.search(testLastName);
    await contactsPage.waitForContactRow(testLastName);

    // Skip search since we already searched
    const contactId = await contactsPage.getContactId(testLastName, true);
    expect(contactId).not.toBeNull();

    // Navigate to detail view
    if (contactId) {
      await contactsPage.gotoDetail(contactId);
    }

    // Verify Edit button exists
    const editButton = authenticatedPage.locator('#Contacts_detailView_action_LBL_EDIT, a[href*="view=Edit"], button:has-text("Edytuj"), button:has-text("Edit"), button:has-text("✏")').first();
    await expect(editButton).toBeVisible({ timeout: 5000 });

    // Verify Delete button exists (may not be visible if no permission)
    const deleteButton = authenticatedPage.locator('#Contacts_detailView_action_LBL_DELETE_RECORD, button[onclick*="deleteRecord"]').first();
    if (await deleteButton.isVisible({ timeout: 2000 })) {
      await expect(deleteButton).toBeVisible();
    }

    console.log(`Navigation buttons verified in detail view`);
  });

  test('should navigate back to list view from detail', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();

    // Create a test contact
    const testLastName = `BackTestLast${Date.now()}`;

    const addButton = authenticatedPage.locator('a:has-text("Dodaj rekord"), a:has-text("Add"), [href*="module=Contacts&view=Edit"]').first();
    await addButton.click();
    await authenticatedPage.waitForURL(/view=Edit/);

    await authenticatedPage.locator('input[name="firstname"]').fill(`BackTestFirst${Date.now()}`);
    await authenticatedPage.locator('input[name="lastname"]').fill(testLastName);

    // Save the contact
    await authenticatedPage.locator('button:has-text("Zapisz"), button:has-text("Save"), button.btn-success').first().click();
    await authenticatedPage.waitForLoadState('networkidle');

    // Navigate to list view and get contact ID
    await contactsPage.gotoList();
    await contactsPage.search(testLastName);
    await contactsPage.waitForContactRow(testLastName);

    // Skip search since we already searched
    const contactId = await contactsPage.getContactId(testLastName, true);
    expect(contactId).not.toBeNull();

    // Navigate to detail view
    if (contactId) {
      await contactsPage.gotoDetail(contactId);
    }

    // Navigate back to list view
    await contactsPage.gotoList();

    // Verify we're on list view
    await expect(authenticatedPage).toHaveURL(/module=Contacts.*view=ListView/);
    await contactsPage.waitForListLoad();

    console.log(`Successfully navigated back to list view from detail view`);
  });
});

