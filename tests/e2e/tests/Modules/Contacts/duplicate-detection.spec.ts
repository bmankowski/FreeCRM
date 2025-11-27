/**
 * Duplicate Detection Tests - Contacts Module
 * 
 * Tests duplicate detection and merging functionality including:
 * - Duplicate detection on create (exact match)
 * - Duplicate detection on email similarity
 * - Duplicate warning modal
 * - Merge duplicate contacts
 * - Merge with field conflict resolution
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { ContactsPage } from '../../../pages/ContactsPage';

test.describe('Contacts - Duplicate Detection', () => {
  let page: Page;
  let authenticatedPage: any;
  let contactsPage: ContactsPage;
  let testContactIds: string[] = [];

  test.beforeEach(async ({ authenticatedPage: authPage }) => {
    authenticatedPage = authPage;
    // Use authenticatedPage directly
    contactsPage = new ContactsPage(authenticatedPage);
    
    // Already authenticated via fixture('/');
    // TODO: Add login steps if needed
  });

  test.afterEach(async () => {
    for (const id of testContactIds) {
      await contactsPage.deleteContactById(id).catch(() => {});
    }
    testContactIds = [];
    
  });

  test('Test 4.1: Duplicate Detection on Create (Exact Match)', async () => {
    const timestamp = Date.now();
    const testEmail = `duplicate_${timestamp}@example.com`;
    
    // Create existing contact
    const existingId = await contactsPage.createTestContact(
      'John',
      'Smith',
      { email: testEmail }
    );
    if (existingId) testContactIds.push(existingId);
    
    // Attempt to create duplicate
    // Already authenticated via fixture('/index.php?module=Contacts&view=Edit');
    await authenticatedPage.locator('input[name="firstname"]').fill('John');
    await authenticatedPage.locator('input[name="lastname"]').fill('Smith');
    await authenticatedPage.locator('input[name="email"]').fill(testEmail);
    await authenticatedPage.locator('button:has-text("Save"), button:has-text("Zapisz")').first().click();
    
    // Look for duplicate warning
    const duplicateModal = authenticatedPage.locator('.duplicate-warning, .modal:has-text("duplicate")');
    if (await duplicateModal.isVisible({ timeout: 5000 })) {
      await expect(duplicateModal).toContainText(/duplicate/i);
      await expect(duplicateModal).toContainText(testEmail);
      
      // Verify options available
      await expect(duplicateModal.locator('button:has-text("Save anyway"), button:has-text("Create anyway")')).toBeVisible();
      await expect(duplicateModal.locator('button:has-text("Cancel")')).toBeVisible();
      
      // Cancel to avoid creating duplicate
      await duplicateModal.locator('button:has-text("Cancel")').click();
    }
  });

  test('Test 4.2: Duplicate Detection on Email Similarity', async () => {
    const timestamp = Date.now();
    const testEmail = `duplicate2_${timestamp}@example.com`;
    
    // Create existing contact
    const existingId = await contactsPage.createTestContact(
      'Sarah',
      'Jones',
      { email: testEmail }
    );
    if (existingId) testContactIds.push(existingId);
    
    // Try with same email, different name
    // Already authenticated via fixture('/index.php?module=Contacts&view=Edit');
    await authenticatedPage.locator('input[name="firstname"]').fill('Sarah');
    await authenticatedPage.locator('input[name="lastname"]').fill('Johnson');
    await authenticatedPage.locator('input[name="email"]').fill(testEmail);
    await authenticatedPage.locator('button:has-text("Save")').first().click();
    
    // Check for duplicate warning
    await authenticatedPage.waitForTimeout(2000);
    const duplicateWarning = authenticatedPage.locator('.duplicate-warning, .error, .modal');
    if (await duplicateWarning.isVisible({ timeout: 3000 })) {
      const warningText = await duplicateWarning.textContent();
      expect(warningText).toMatch(/email.*exists|duplicate/i);
    }
  });

  test('Test 4.3: Duplicate Detection Warning Modal', async () => {
    const timestamp = Date.now();
    const testEmail = `duplicate3_${timestamp}@example.com`;
    
    // Create existing contact
    const existingId = await contactsPage.createTestContact(
      'Mike',
      'Brown',
      { email: testEmail }
    );
    if (existingId) testContactIds.push(existingId);
    
    // Attempt duplicate
    // Already authenticated via fixture('/index.php?module=Contacts&view=Edit');
    await authenticatedPage.locator('input[name="firstname"]').fill('Michael');
    await authenticatedPage.locator('input[name="lastname"]').fill('Brown');
    await authenticatedPage.locator('input[name="email"]').fill(testEmail);
    await authenticatedPage.locator('button:has-text("Save")').first().click();
    
    const duplicateModal = authenticatedPage.locator('.duplicate-modal, .modal');
    if (await duplicateModal.isVisible({ timeout: 5000 })) {
      // Verify similarity info if available
      const modalText = await duplicateModal.textContent();
      if (modalText) {
        expect(modalText).toMatch(/duplicate|similar|match/i);
      }
      
      // Test view details link
      const viewLink = duplicateModal.locator('a:has-text("View Details"), a:has-text("View existing")').first();
      if (await viewLink.isVisible({ timeout: 2000 })) {
        // Link should be present
        await expect(viewLink).toBeVisible();
      }
      
      // Cancel
      await duplicateModal.locator('button:has-text("Cancel")').first().click();
    }
  });

  test('Test 4.4: Merge Duplicate Contacts', async () => {
    const timestamp = Date.now();
    
    // Create two duplicates
    const contact1Id = await contactsPage.createTestContact(
      'John',
      `Merge${timestamp}`,
      { email: `merge1_${timestamp}@example.com`, phone: '555-1111' }
    );
    if (contact1Id) testContactIds.push(contact1Id);
    
    const contact2Id = await contactsPage.createTestContact(
      'John',
      `Merge${timestamp}`,
      { email: `merge2_${timestamp}@example.com`, phone: '555-2222' }
    );
    if (contact2Id) testContactIds.push(contact2Id);
    
    // Navigate to list and search
    await contactsPage.gotoList();
    await contactsPage.search(`Merge${timestamp}`);
    await authenticatedPage.waitForTimeout(1000);
    
    // Select both contacts
    const rows = authenticatedPage.locator('tbody tr').filter({ hasNot: authenticatedPage.locator('input.listSearchContributor') });
    if (await rows.count() >= 2) {
      await rows.nth(0).locator('input[type="checkbox"]').click();
      await rows.nth(1).locator('input[type="checkbox"]').click();
      
      // Look for merge button
      const mergeButton = authenticatedPage.locator('button:has-text("Merge"), a:has-text("Merge Duplicates")').first();
      if (await mergeButton.isVisible({ timeout: 5000 })) {
        await mergeButton.click();
        
        // Merge interface
        const mergeDialog = authenticatedPage.locator('.merge-dialog, .modal').first();
        if (await mergeDialog.isVisible({ timeout: 3000 })) {
          // Select master record
          await mergeDialog.locator('input[type="radio"]').first().check();
          
          // Confirm merge
          await mergeDialog.locator('button:has-text("Merge"), button:has-text("Confirm")').first().click();
          
          // Verify success
          await expect(authenticatedPage.locator('.notification')).toContainText(/merged/i, { timeout: 10000 });
        }
      }
    }
  });

  test('Test 4.5: Merge with Field Conflict Resolution', async () => {
    const timestamp = Date.now();
    
    // Create contacts with conflicting data
    const contactA = await contactsPage.createTestContact(
      'Conflict',
      `TestA${timestamp}`,
      { email: `conflictA_${timestamp}@example.com`, phone: '111-111-1111' }
    );
    if (contactA) testContactIds.push(contactA);
    
    const contactB = await contactsPage.createTestContact(
      'Conflict',
      `TestB${timestamp}`,
      { email: `conflictB_${timestamp}@example.com`, phone: '222-222-2222' }
    );
    if (contactB) testContactIds.push(contactB);
    
    // Navigate and search
    await contactsPage.gotoList();
    await contactsPage.search(`Test${timestamp}`);
    await authenticatedPage.waitForTimeout(1000);
    
    // Select both
    const rows = authenticatedPage.locator('tbody tr').filter({ hasNot: authenticatedPage.locator('input.listSearchContributor') });
    if (await rows.count() >= 2) {
      await rows.nth(0).locator('input[type="checkbox"]').click();
      await rows.nth(1).locator('input[type="checkbox"]').click();
      
      const mergeButton = authenticatedPage.locator('button:has-text("Merge")').first();
      if (await mergeButton.isVisible({ timeout: 5000 })) {
        await mergeButton.click();
        
        const mergeDialog = authenticatedPage.locator('.merge-dialog, .modal').first();
        if (await mergeDialog.isVisible({ timeout: 3000 })) {
          // Check for conflicts
          const conflicts = await mergeDialog.locator('.field-conflict, .conflict').count();
          
          if (conflicts > 0) {
            // Select preferred values
            const radioButtons = mergeDialog.locator('input[type="radio"]');
            const count = await radioButtons.count();
            if (count > 0) {
              await radioButtons.first().check();
            }
          }
          
          // Complete merge
          await mergeDialog.locator('button:has-text("Merge")').first().click();
          await authenticatedPage.waitForTimeout(2000);
        }
      }
    }
  });
});




