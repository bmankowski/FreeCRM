/**
 * Accounts List View E2E Tests
 * 
 * Tests the list view functionality in the Accounts module.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { AccountsPage } from '../../../pages/AccountsPage';

test.describe('Accounts List View', () => {
  let accountsPage: AccountsPage;

  test.beforeEach(async ({ authenticatedPage }) => {
    accountsPage = new AccountsPage(authenticatedPage);
    await accountsPage.goto();
  });

  test('should display Accounts list view', async ({ authenticatedPage }) => {
    // Verify we're on the Accounts page
    await expect(authenticatedPage).toHaveURL(/module=Accounts/);
    
    // Verify the accounts table is visible
    await expect(accountsPage.accountsTable.first()).toBeVisible();
    
    console.log('Accounts list view displayed successfully');
  });

  test('should create and search for new account', async ({ authenticatedPage }) => {
    await accountsPage.waitForListLoad();
    
    // Get initial record count
    const initialCount = await accountsPage.getRecordCount();
    console.log(`Initial accounts count: ${initialCount}`);
    
    // Create a new account
    const testAccountName = `Test Account ${Date.now()}`;
    
    // Click add account button
    const addButton = authenticatedPage.locator('a:has-text("Dodaj rekord"), a:has-text("Add"), [href*="module=Accounts&view=Edit"]').first();
    await addButton.click();
    
    // Wait for edit form to load
    await authenticatedPage.waitForURL(/view=Edit/);
    
    // Fill in account name
    await authenticatedPage.locator('input[name="accountname"]').fill(testAccountName);
    
    // Save the account
    await authenticatedPage.locator('button:has-text("Zapisz"), button:has-text("Save"), button.btn-success').first().click();
    
    // Wait for save and redirect to list or detail view
    await authenticatedPage.waitForLoadState('networkidle');
    
    // Go back to list view
    await accountsPage.goto();
    await accountsPage.waitForListLoad();
    
    // Verify the new account exists
    const hasAccount = await accountsPage.hasAccount(testAccountName);
    expect(hasAccount).toBe(true);
    console.log(`Successfully created account: ${testAccountName}`);
    
    // Search for the newly created account
    await accountsPage.search(testAccountName);
    await accountsPage.waitForListLoad();
    
    // Verify search found the account
    const foundInSearch = await accountsPage.hasAccount(testAccountName);
    expect(foundInSearch).toBe(true);
    console.log(`Successfully found account in search: ${testAccountName}`);
  });
});

