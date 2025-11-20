/**
 * Contacts List View E2E Tests
 * 
 * Tests the list view display functionality in the Contacts module.
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
});


