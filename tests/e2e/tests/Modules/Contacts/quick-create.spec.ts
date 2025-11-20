/**
 * Contacts Quick Create E2E Tests
 * 
 * Tests the quick create functionality in the Contacts module.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { ContactsPage } from '../../../pages/ContactsPage';

test.describe('Contacts Quick Create', () => {
  let contactsPage: ContactsPage;

  test.beforeEach(async ({ authenticatedPage }) => {
    contactsPage = new ContactsPage(authenticatedPage);
    await contactsPage.goto();
  });

  test('should create contact using quick create', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();
    
    // Get initial record count
    const initialCount = await contactsPage.getRecordCount();
    console.log(`Initial contacts count: ${initialCount}`);
    
    // Create a new contact using quick create
    const testFirstName = `QuickCreateFirst${Date.now()}`;
    const testLastName = `QuickCreateLast${Date.now()}`;
    
    // Open quick create dropdown by clicking the quick create button in header
    const quickCreateButton = authenticatedPage.locator('.bodyHeader .dropdownMenu, .btn.dropdownMenu, a.dropdownMenu').first();
    await quickCreateButton.waitFor({ state: 'visible', timeout: 5000 });
    await quickCreateButton.click();
    
    // Wait for the dropdown menu to be displayed (check if it's actually shown)
    await authenticatedPage.waitForFunction(
      () => {
        const menu = document.querySelector('.bodyHeader ul.dropdown-menu.commonActionsButtonDropDown');
        if (!menu) return false;
        const style = window.getComputedStyle(menu);
        return style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0';
      },
      { timeout: 5000 }
    );
    
    // Wait for Contacts link to be attached to DOM
    // The element might be in the DOM but Playwright considers it hidden due to CSS
    const contactsQuickCreateLink = authenticatedPage.locator('#menubar_quickCreate_Contacts, .quickCreateModule[data-name="Contacts"], a.quickCreateModule:has-text("Contact"), a.quickCreateModule:has-text("Kontakt")').first();
    await contactsQuickCreateLink.waitFor({ state: 'attached', timeout: 5000 });
    
    // Use JavaScript to click the element directly, bypassing Playwright's visibility checks
    // This is necessary because the dropdown menu item might be considered "hidden" by Playwright
    // even though it's actually visible and clickable in the browser
    await authenticatedPage.evaluate(() => {
      // Try multiple selectors to find the Contacts quick create link
      let element: HTMLElement | null = document.querySelector('#menubar_quickCreate_Contacts');
      if (!element) {
        element = document.querySelector('.quickCreateModule[data-name="Contacts"]');
      }
      if (!element) {
        const allLinks = Array.from(document.querySelectorAll<HTMLElement>('a.quickCreateModule'));
        element = allLinks.find(link => link.getAttribute('data-name') === 'Contacts') || null;
      }
      if (element) {
        element.click();
      } else {
        throw new Error('Contacts quick create link not found');
      }
    });
    
    // Wait for quick create modal to appear
    const quickCreateModal = authenticatedPage.locator('.quickCreateContainer.modal, .modal.quickCreateContainer, .modal.fade.quickCreateContainer').first();
    await quickCreateModal.waitFor({ state: 'visible', timeout: 10000 });
    
    // Fill in contact details in the modal (scope to modal to avoid list view search fields)
    await quickCreateModal.locator('input[name="firstname"]').waitFor({ state: 'visible', timeout: 5000 });
    await quickCreateModal.locator('input[name="firstname"]').fill(testFirstName);
    await quickCreateModal.locator('input[name="lastname"]').fill(testLastName);
    
    // Save the contact using the save button in the modal
    const saveButton = quickCreateModal.locator('button.btn-success:has-text("Zapisz"), button.btn-success:has-text("Save"), button[type="submit"].btn-success').first();
    await saveButton.waitFor({ state: 'visible', timeout: 5000 });
    await saveButton.click();
    
    // Wait for modal to close and save to complete
    await quickCreateModal.waitFor({ state: 'hidden', timeout: 10000 }).catch(() => {});
    await authenticatedPage.waitForLoadState('networkidle');
    
    // Navigate to Contacts list view and verify we're on the list
    await contactsPage.goto();
    await expect(authenticatedPage).toHaveURL(/module=Contacts.*view=ListView/);
    await contactsPage.waitForListLoad();
    
    console.log(`Successfully created contact via quick create: ${testFirstName} ${testLastName}`);
    console.log(`Navigated to Contacts list view`);
    
    // Search for the newly created contact using the search field
    await contactsPage.search(testLastName);
    
    // Verify search found the contact
    await contactsPage.waitForContactRow(testLastName);
    const foundInSearch = await contactsPage.hasContact(testLastName);
    expect(foundInSearch).toBe(true);
    console.log(`Successfully found contact created via quick create in search: ${testLastName}`);
  });
});

