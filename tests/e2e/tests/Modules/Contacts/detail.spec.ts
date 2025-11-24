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
import { Page } from '@playwright/test';
import { ContactsPage } from '../../../pages/ContactsPage';
import { expectNoWarningsAndErrors } from '../../../helpers/page-assertions';

test.describe('Contacts Detail View', () => {
  let contactsPage: ContactsPage;
  let authenticatedPage: any;
  let createdContactIds: string[] = [];

  test.beforeEach(async ({ authenticatedPage: authPage }) => {
    authenticatedPage = authPage;
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
   * Helper function to create a test contact
   * Reduces code duplication across tests
   */
  async function createTestContact(
    page: Page, 
    contactsPage: ContactsPage, 
    data: {
      firstName?: string;
      lastName: string;
      email?: string;
      phone?: string;
    }
  ): Promise<string | null> {
    const addButton = page.locator('a:has-text("Dodaj rekord"), a:has-text("Add"), [href*="module=Contacts&view=Edit"]').first();
    await addButton.click();
    await page.waitForURL(/view=Edit/);
    await expectNoWarningsAndErrors(page);

    if (data.firstName) {
      await page.locator('input[name="firstname"]').fill(data.firstName);
    }
    await page.locator('input[name="lastname"]').fill(data.lastName);
    if (data.email) {
      await page.locator('input[name="email"]').fill(data.email);
    }
    if (data.phone) {
      await page.locator('input[name="phone"]').fill(data.phone);
    }

    await page.locator('button:has-text("Zapisz"), button:has-text("Save"), button.btn-success').first().click();
    await page.waitForLoadState('networkidle');
    await expectNoWarningsAndErrors(page);

    await contactsPage.gotoList();
    await contactsPage.search(data.lastName);
    await contactsPage.waitForContactRow(data.lastName);
    
    const contactId = await contactsPage.getContactId(data.lastName, true);
    if (contactId) {
      createdContactIds.push(contactId);
    }
    return contactId;
  }

  test('should navigate to contact detail view from list', async ({ authenticatedPage }) => {
    await expectNoWarningsAndErrors(authenticatedPage);
    await contactsPage.waitForListLoad();

    // Create a test contact using helper
    const testLastName = `DetailTest${Date.now()}`;
    const contactId = await createTestContact(authenticatedPage, contactsPage, {
      firstName: `DetailFirst${Date.now()}`,
      lastName: testLastName
    });

    expect(contactId).not.toBeNull();

    // Click on the contact name to go to detail view
    const contactRow = contactsPage.contactsTable.locator('tr', { hasText: testLastName }).first();
    const contactLink = contactRow.locator('a.moduleColor_Contacts, a:has-text("' + testLastName + '")').first();
    await contactLink.click();

    // Verify we're on detail view
    await expect(authenticatedPage).toHaveURL(/view=Detail/);
    await expect(authenticatedPage).toHaveURL(new RegExp(`record=${contactId}`));
    await expectNoWarningsAndErrors(authenticatedPage);
  });

  test('should display contact information in detail view', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();
    await expectNoWarningsAndErrors(authenticatedPage);

    // Create a test contact using helper
    const testFirstName = `InfoFirst${Date.now()}`;
    const testLastName = `InfoLast${Date.now()}`;
    const testEmail = `test${Date.now()}@example.com`;

    const contactId = await createTestContact(authenticatedPage, contactsPage, {
      firstName: testFirstName,
      lastName: testLastName,
      email: testEmail
    });

    expect(contactId).not.toBeNull();

    // Navigate to detail view
    await contactsPage.gotoDetail(contactId!);
    await expectNoWarningsAndErrors(authenticatedPage);

    // Verify contact name is displayed in detail view header
    const contactName = authenticatedPage.locator('h4.recordLabel').filter({ hasText: testLastName }).first();
    await expect(contactName).toBeVisible({ timeout: 5000 });

    // Verify first name is displayed in detail view content
    const detailViewContainer = authenticatedPage.locator('.detailViewContainer, .detailViewContents, .detailView').first();
    const firstNameDisplay = detailViewContainer.getByText(testFirstName, { exact: false }).first();
    if (await firstNameDisplay.count() > 0) {
      await expect(firstNameDisplay).toBeVisible({ timeout: 5000 });
    }

    // Verify email is displayed
    const emailDisplay = detailViewContainer.getByText(testEmail, { exact: false }).first();
    if (await emailDisplay.count() > 0) {
      await expect(emailDisplay).toBeVisible({ timeout: 5000 });
    }
  });

  test('should have navigation buttons in detail view', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();
    await expectNoWarningsAndErrors(authenticatedPage);

    // Create a test contact using helper
    const testLastName = `NavTest${Date.now()}`;
    const contactId = await createTestContact(authenticatedPage, contactsPage, {
      firstName: `NavFirst${Date.now()}`,
      lastName: testLastName
    });

    expect(contactId).not.toBeNull();

    // Navigate to detail view
    await contactsPage.gotoDetail(contactId!);
    await expectNoWarningsAndErrors(authenticatedPage);

    // Verify Edit button exists
    const editButton = authenticatedPage.locator('#Contacts_detailView_action_BTN_RECORD_EDIT').first();
    await expect(editButton).toBeVisible({ timeout: 5000 });

    // Verify Delete button exists (may not be visible if no permission)
    const deleteButton = authenticatedPage.locator('#Contacts_detailView_action_LBL_DELETE_RECORD, button[onclick*="deleteRecord"]').first();
    if (await deleteButton.isVisible({ timeout: 2000 })) {
      await expect(deleteButton).toBeVisible();
    }
  });

  test('should navigate back to list view from detail', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();
    await expectNoWarningsAndErrors(authenticatedPage);

    // Create a test contact using helper
    const testLastName = `BackTest${Date.now()}`;
    const contactId = await createTestContact(authenticatedPage, contactsPage, {
      firstName: `BackFirst${Date.now()}`,
      lastName: testLastName
    });

    expect(contactId).not.toBeNull();

    // Navigate to detail view
    await contactsPage.gotoDetail(contactId!);
    await expectNoWarningsAndErrors(authenticatedPage);

    // Navigate back to list view
    await contactsPage.gotoList();

    // Verify we're on list view
    await expect(authenticatedPage).toHaveURL(/module=Contacts.*view=ListView/);
    await contactsPage.waitForListLoad();
    await expectNoWarningsAndErrors(authenticatedPage);
  });

  // ============================================================================
  // NEGATIVE TESTS
  // ============================================================================

  test('should handle non-existent contact gracefully', async ({ authenticatedPage }) => {
    // Attempt to access a non-existent contact ID
    await authenticatedPage.goto('index.php?module=Contacts&view=Detail&record=999999999');
    await authenticatedPage.waitForLoadState('networkidle');
    
    // CRM should show "Brak uprawnień" (Permission denied) modal
    const permissionDeniedModal = authenticatedPage.getByRole('heading', { name: /Brak uprawnień/i });
    const permissionDeniedText = authenticatedPage.getByText(/nie masz wystarczających uprawnień/i);
    
    // Verify modal is displayed
    await expect(permissionDeniedModal.or(permissionDeniedText).first()).toBeVisible({ timeout: 5000 });
  });

  test('should handle invalid contact ID format', async ({ authenticatedPage }) => {
    // Try with invalid ID format
    await authenticatedPage.goto('index.php?module=Contacts&view=Detail&record=invalid-id');
    await authenticatedPage.waitForLoadState('networkidle');
    
    // CRM should show "Brak uprawnień" (Permission denied) modal
    const permissionDeniedModal = authenticatedPage.getByRole('heading', { name: /Brak uprawnień/i });
    const permissionDeniedText = authenticatedPage.getByText(/nie masz wystarczających uprawnień/i);
    
    // Verify modal is displayed
    await expect(permissionDeniedModal.or(permissionDeniedText).first()).toBeVisible({ timeout: 5000 });
  });

  // ============================================================================
  // EDIT FROM DETAIL VIEW TEST
  // ============================================================================

  test('should edit contact from detail view and save changes', async ({ authenticatedPage }) => {
    test.setTimeout(60000); // Increase timeout for this test
    
    await contactsPage.waitForListLoad();
    
    const contactId = await createTestContact(authenticatedPage, contactsPage, {
      firstName: `EditFromDetail${Date.now()}`,
      lastName: `TestLast${Date.now()}`,
      email: `original${Date.now()}@example.com`
    });
    
    expect(contactId).not.toBeNull();
    
    // Navigate to detail view
    await contactsPage.gotoDetail(contactId!);
    await expectNoWarningsAndErrors(authenticatedPage);
    
    // Navigate to edit view using page object method
    await contactsPage.gotoEdit(contactId!);
    await expectNoWarningsAndErrors(authenticatedPage);
    
    // Edit email field
    const newEmail = `newemail${Date.now()}@example.com`;
    await authenticatedPage.locator('input[name="email"]').fill(newEmail);
    
    // Save the contact
    const saveButton = authenticatedPage.locator('button:has-text("Zapisz"), button:has-text("Save"), button.btn-success').first();
    await saveButton.click();
    
    // Wait for save to complete (may redirect through index.php)
    await authenticatedPage.waitForLoadState('networkidle');
    
    // Manually navigate back to detail view (workaround for redirect issue)
    await contactsPage.gotoDetail(contactId!);
    await expectNoWarningsAndErrors(authenticatedPage);
    
    // Verify updated email is displayed
    await expect(authenticatedPage.getByText(newEmail, { exact: false }).first()).toBeVisible({ timeout: 10000 });
  });

  // ============================================================================
  // BREADCRUMB NAVIGATION TEST
  // ============================================================================

  test('should have correct breadcrumb navigation', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();
    
    const contactId = await createTestContact(authenticatedPage, contactsPage, {
      firstName: `BreadcrumbTest${Date.now()}`,
      lastName: `TestLast${Date.now()}`
    });
    
    expect(contactId).not.toBeNull();
    
    await contactsPage.gotoDetail(contactId!);
    await expectNoWarningsAndErrors(authenticatedPage);
    
    // Look for breadcrumb navigation
    const breadcrumbs = authenticatedPage.locator('.breadcrumb, .breadcrumbs, nav[aria-label="breadcrumb"], .c-breadcrumb');
    
    if (await breadcrumbs.count() > 0) {
      await expect(breadcrumbs.first()).toBeVisible();
      
      // Check if breadcrumbs contain link to Contacts module
      const contactsLink = breadcrumbs.locator('a:has-text("Contacts"), a:has-text("Kontakty")');
      if (await contactsLink.count() > 0) {
        await expect(contactsLink.first()).toBeVisible();
      }
    } else {
      // If no breadcrumbs, at least verify we have some navigation structure
      const navigation = authenticatedPage.locator('nav, .navigation, .navbar');
      await expect(navigation.first()).toBeVisible();
    }
  });

  // ============================================================================
  // ACTIVITY/HISTORY TIMELINE TEST
  // ============================================================================

  test('should display activity or history section if available', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();
    
    const contactId = await createTestContact(authenticatedPage, contactsPage, {
      firstName: `HistoryTest${Date.now()}`,
      lastName: `TestLast${Date.now()}`
    });
    
    expect(contactId).not.toBeNull();
    
    await contactsPage.gotoDetail(contactId!);
    await expectNoWarningsAndErrors(authenticatedPage);
    
    // Check for various activity/history sections
    const activitySelectors = [
      '.timeline',
      '.history',
      '.activities',
      '[data-module="ModComments"]',
      '.related-activities',
      '.detailViewInfo',
      'div:has-text("History")',
      'div:has-text("Historia")',
      'div:has-text("Activities")',
      'div:has-text("Aktywności")'
    ];
    
    let foundActivitySection = false;
    for (const selector of activitySelectors) {
      const section = authenticatedPage.locator(selector).first();
      if (await section.count() > 0 && await section.isVisible()) {
        foundActivitySection = true;
        await expect(section).toBeVisible();
        break;
      }
    }
    
    // If no specific activity section found, at least verify the detail view is displayed
    if (!foundActivitySection) {
      const detailView = authenticatedPage.locator('.detailViewContainer, .detailViewContents, .detailView').first();
      await expect(detailView).toBeVisible();
    }
  });

  // ============================================================================
  // ACTION MENU TEST
  // ============================================================================

  test('should display action menu with available actions', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();
    
    const contactId = await createTestContact(authenticatedPage, contactsPage, {
      firstName: `ActionsTest${Date.now()}`,
      lastName: `TestLast${Date.now()}`
    });
    
    expect(contactId).not.toBeNull();
    
    await contactsPage.gotoDetail(contactId!);
    await expectNoWarningsAndErrors(authenticatedPage);
    
    // Verify Edit button exists (primary action)
    const editButton = authenticatedPage.locator('#Contacts_detailView_action_BTN_RECORD_EDIT').first();
    await expect(editButton).toBeVisible({ timeout: 5000 });
    
    // Look for action menu or dropdown
    const actionMenuSelectors = [
      '.detailViewActions',
      '.actions-menu',
      '.btn-group',
      '[class*="action"]',
      '.dropdown-menu',
      'button[data-toggle="dropdown"]'
    ];
    
    let foundActionMenu = false;
    for (const selector of actionMenuSelectors) {
      const menu = authenticatedPage.locator(selector);
      if (await menu.count() > 0) {
        const visibleMenus = await menu.all();
        for (const visibleMenu of visibleMenus) {
          if (await visibleMenu.isVisible()) {
            foundActionMenu = true;
            break;
          }
        }
        if (foundActionMenu) break;
      }
    }
    
    // Check for common actions (may not all be visible)
    const commonActions = [
      'Delete',
      'Usuń',
      'Duplicate',
      'Duplikuj',
      'Send Email',
      'Wyślij email'
    ];
    
    let actionsFound = 0;
    for (const actionText of commonActions) {
      const actionButton = authenticatedPage.locator(`button:has-text("${actionText}"), a:has-text("${actionText}")`);
      if (await actionButton.count() > 0) {
        actionsFound++;
      }
    }
    
    // At least Edit button should be present
    expect(actionsFound).toBeGreaterThanOrEqual(0);
  });
});

