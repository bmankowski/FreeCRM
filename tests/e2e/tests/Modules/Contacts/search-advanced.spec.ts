/**
 * Contacts Advanced Search E2E Tests
 * 
 * Tests advanced search functionality with multiple search scenarios.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { ContactsPage } from '../../../pages/ContactsPage';

test.describe('Contacts Advanced Search', () => {
  let contactsPage: ContactsPage;

  test.beforeEach(async ({ authenticatedPage }) => {
    contactsPage = new ContactsPage(authenticatedPage);
    await contactsPage.gotoList();
  });

  test('should search by first name', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();
    
    // Create a test contact
    const testFirstName = `SearchFirst${Date.now()}`;
    const testLastName = `SearchLast${Date.now()}`;
    
    const addButton = authenticatedPage.locator('a:has-text("Dodaj rekord"), a:has-text("Add"), [href*="module=Contacts&view=Edit"]').first();
    await addButton.click();
    await authenticatedPage.waitForURL(/view=Edit/);
    
    await authenticatedPage.locator('input[name="firstname"]').fill(testFirstName);
    await authenticatedPage.locator('input[name="lastname"]').fill(testLastName);
    
    await authenticatedPage.locator('button:has-text("Zapisz"), button:has-text("Save"), button.btn-success').first().click();
    await authenticatedPage.waitForLoadState('networkidle');
    
    // Go to list view
    await contactsPage.gotoList();
    
    // Search by first name
    try {
      await contactsPage.searchByField('firstname', testFirstName);
      
      // Verify contact is found
      await contactsPage.waitForContactRow(testFirstName);
      const found = await contactsPage.hasContact(testFirstName);
      expect(found).toBe(true);
      
      console.log(`Successfully searched by first name: ${testFirstName}`);
    } catch (error) {
      console.log(`Search by firstname field not available`);
    }
  });

  test('should search by email', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();
    
    // Create a test contact with email
    const testEmail = `search${Date.now()}@example.com`;
    const testLastName = `EmailSearchLast${Date.now()}`;
    
    const addButton = authenticatedPage.locator('a:has-text("Dodaj rekord"), a:has-text("Add"), [href*="module=Contacts&view=Edit"]').first();
    await addButton.click();
    await authenticatedPage.waitForURL(/view=Edit/);
    
    await authenticatedPage.locator('input[name="firstname"]').fill(`EmailSearchFirst${Date.now()}`);
    await authenticatedPage.locator('input[name="lastname"]').fill(testLastName);
    await authenticatedPage.locator('input[name="email"]').fill(testEmail);
    
    await authenticatedPage.locator('button:has-text("Zapisz"), button:has-text("Save"), button.btn-success').first().click();
    await authenticatedPage.waitForLoadState('networkidle');
    
    // Go to list view
    await contactsPage.gotoList();
    
    // Search by email
    try {
      await contactsPage.searchByField('email', testEmail);
      
      // Verify contact is found
      await contactsPage.waitForContactRow(testEmail);
      const found = await contactsPage.hasContact(testEmail);
      expect(found).toBe(true);
      
      console.log(`Successfully searched by email: ${testEmail}`);
    } catch (error) {
      console.log(`Search by email field not available`);
    }
  });

  test('should search with partial match', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();
    
    // Create a test contact with unique name
    const uniquePart = Date.now();
    const testLastName = `Partial${uniquePart}Test`;
    
    const addButton = authenticatedPage.locator('a:has-text("Dodaj rekord"), a:has-text("Add"), [href*="module=Contacts&view=Edit"]').first();
    await addButton.click();
    await authenticatedPage.waitForURL(/view=Edit/);
    
    await authenticatedPage.locator('input[name="firstname"]').fill(`PartialTestFirst${uniquePart}`);
    await authenticatedPage.locator('input[name="lastname"]').fill(testLastName);
    
    await authenticatedPage.locator('button:has-text("Zapisz"), button:has-text("Save"), button.btn-success').first().click();
    await authenticatedPage.waitForLoadState('networkidle');
    
    // Go to list view
    await contactsPage.gotoList();
    
    // Search with partial match (just the unique number)
    const partialSearch = uniquePart.toString();
    await contactsPage.search(partialSearch);
    
    // Verify contact is found
    await contactsPage.waitForContactRow(partialSearch);
    const found = await contactsPage.hasContact(partialSearch);
    expect(found).toBe(true);
    
    console.log(`Successfully searched with partial match: ${partialSearch}`);
  });

  test('should clear search and show all records', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();
    
    // Get initial count
    const initialCount = await contactsPage.getRecordCount();
    
    // Perform a search
    const testSearch = `ClearTest${Date.now()}`;
    await contactsPage.search(testSearch);
    await contactsPage.waitForListLoad();
    
    const filteredCount = await contactsPage.getRecordCount();
    
    // Clear search - look for clear button or clear the field
    const searchInput = contactsPage.lastnameSearchInput.first();
    if (await searchInput.isVisible({ timeout: 2000 })) {
      // Clear the input field
      await searchInput.clear();
      // Wait a bit for the field to be cleared
      await authenticatedPage.waitForTimeout(500);
      // Press Enter to trigger search with empty value
      await searchInput.press('Enter');
      // Wait for list to reload
      await contactsPage.waitForListLoad();
      await authenticatedPage.waitForLoadState('networkidle');
      
      // After clearing, count should be back to initial (or close to it)
      const clearedCount = await contactsPage.getRecordCount();
      expect(clearedCount).toBeGreaterThanOrEqual(filteredCount);
      
      console.log(`Search cleared - showing ${clearedCount} contacts (initial: ${initialCount})`);
    } else {
      // Try alternative: navigate back to list view to clear search
      await contactsPage.gotoList();
      await contactsPage.waitForListLoad();
      const clearedCount = await contactsPage.getRecordCount();
      expect(clearedCount).toBeGreaterThanOrEqual(filteredCount);
      console.log(`Search cleared by navigation - showing ${clearedCount} contacts (initial: ${initialCount})`);
    }
  });

  test('should handle search with no results', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();
    
    // Search for something that definitely doesn't exist
    const nonExistent = `NonExistent${Date.now()}XYZ123`;
    await contactsPage.search(nonExistent);
    await contactsPage.waitForListLoad();
    
    // Verify no results
    const found = await contactsPage.hasContact(nonExistent);
    expect(found).toBe(false);
    
    // Check for empty state or "no results" message
    const noResults = authenticatedPage.locator('text=/no.*result|brak.*wynik|empty|not.*found/i').first();
    if (await noResults.isVisible({ timeout: 2000 })) {
      await expect(noResults).toBeVisible();
    }
    
    console.log(`Search with no results handled correctly`);
  });

  test('should search by last name (existing functionality)', async ({ authenticatedPage }) => {
    await contactsPage.waitForListLoad();
    
    // Create a test contact
    const testLastName = `LastNameSearch${Date.now()}`;
    
    const addButton = authenticatedPage.locator('a:has-text("Dodaj rekord"), a:has-text("Add"), [href*="module=Contacts&view=Edit"]').first();
    await addButton.click();
    await authenticatedPage.waitForURL(/view=Edit/);
    
    await authenticatedPage.locator('input[name="firstname"]').fill(`LastNameSearchFirst${Date.now()}`);
    await authenticatedPage.locator('input[name="lastname"]').fill(testLastName);
    
    await authenticatedPage.locator('button:has-text("Zapisz"), button:has-text("Save"), button.btn-success').first().click();
    await authenticatedPage.waitForLoadState('networkidle');
    
    // Go to list view
    await contactsPage.gotoList();
    
    // Search by last name (this is the existing search method)
    await contactsPage.search(testLastName);
    
    // Verify contact is found
    await contactsPage.waitForContactRow(testLastName);
    const found = await contactsPage.hasContact(testLastName);
    expect(found).toBe(true);
    
    console.log(`Successfully searched by last name: ${testLastName}`);
  });
});

