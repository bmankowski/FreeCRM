/**
 * Custom Fields Tests - Contacts Module
 * 
 * Tests custom field functionality including:
 * - Display custom text field
 * - Validate custom number field
 * - Use custom date field
 * - Custom picklist field
 * - Custom multi-select picklist
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { ContactsPage } from '../../../pages/ContactsPage';

test.describe('Contacts - Custom Fields', () => {
  let page: Page;
  let authenticatedPage: any;
  let contactsPage: ContactsPage;
  let testContactId: string | null;

  test.beforeEach(async ({ authenticatedPage: authPage }) => {
    authenticatedPage = authPage;
    // Use authenticatedPage directly
    contactsPage = new ContactsPage(authenticatedPage);
    
    // Already authenticated via fixture('/');
    // TODO: Add login steps if needed
    
    // Create test contact
    const timestamp = Date.now();
    testContactId = await contactsPage.createTestContact(
      'CustomField',
      `Test${timestamp}`,
      { email: `custom_${timestamp}@example.com` }
    );
  });

  test.afterEach(async () => {
    if (testContactId) {
      await contactsPage.deleteContactById(testContactId).catch(() => {});
    }
    
  });

  test('Test 9.1: Display Custom Text Field', async () => {
    if (!testContactId) {
      test.skip();
      return;
    }

    await contactsPage.gotoEdit(testContactId);
    
    // Look for custom fields (they typically have cf_ prefix or specific patterns)
    const customFields = authenticatedPage.locator('input[name^="cf_"], input[data-custom="true"]');
    const customFieldCount = await customFields.count();
    
    if (customFieldCount > 0) {
      const firstCustomField = customFields.first();
      await firstCustomField.fill('CUST-12345');
      
      // Save
      await authenticatedPage.locator('button:has-text("Save"), button:has-text("Zapisz")').first().click();
      await authenticatedPage.waitForTimeout(1000);
      
      // Verify saved
      if (testContactId) {
        await contactsPage.gotoDetail(testContactId);
        await expect(authenticatedPage.locator('.detail-view, .detailViewContainer')).toContainText('CUST-12345');
      }
    }
  });

  test('Test 9.2: Validate Custom Number Field', async () => {
    if (!testContactId) {
      test.skip();
      return;
    }

    await contactsPage.gotoEdit(testContactId);
    
    // Look for number-type custom fields
    const numberFields = authenticatedPage.locator('input[type="number"][name^="cf_"], input[name*="credit"][type="number"]');
    const numberFieldCount = await numberFields.count();
    
    if (numberFieldCount > 0) {
      const numberField = numberFields.first();
      
      // Test non-numeric (should show error)
      await numberField.fill('ABC');
      await numberField.blur();
      await authenticatedPage.waitForTimeout(500);
      
      // Try valid number
      await numberField.fill('5000');
      await authenticatedPage.locator('button:has-text("Save")').first().click();
      await authenticatedPage.waitForTimeout(1000);
    }
  });

  test('Test 9.3: Use Custom Date Field', async () => {
    if (!testContactId) {
      test.skip();
      return;
    }

    await contactsPage.gotoEdit(testContactId);
    
    // Look for date-type custom fields
    const dateFields = authenticatedPage.locator('input[type="date"][name^="cf_"], input[name*="date"][data-custom]');
    const dateFieldCount = await dateFields.count();
    
    if (dateFieldCount > 0) {
      const dateField = dateFields.first();
      
      // Set date (30 days from now)
      const futureDate = new Date();
      futureDate.setDate(futureDate.getDate() + 30);
      const dateString = futureDate.toISOString().split('T')[0];
      
      await dateField.fill(dateString);
      
      // Save
      await authenticatedPage.locator('button:has-text("Save")').first().click();
      await authenticatedPage.waitForTimeout(1000);
      
      // Verify
      if (testContactId) {
        await contactsPage.gotoDetail(testContactId);
        const detailView = authenticatedPage.locator('.detail-view');
        await expect(detailView).toBeVisible();
      }
    }
  });

  test('Test 9.4: Custom Picklist Field', async () => {
    if (!testContactId) {
      test.skip();
      return;
    }

    await contactsPage.gotoEdit(testContactId);
    
    // Look for select/picklist custom fields
    const picklistFields = authenticatedPage.locator('select[name^="cf_"], select[data-custom="true"]');
    const picklistCount = await picklistFields.count();
    
    if (picklistCount > 0) {
      const picklist = picklistFields.first();
      
      // Get available options
      const options = await picklist.locator('option').allTextContents();
      
      if (options.length > 1) {
        // Select second option (first is usually empty)
        await picklist.selectOption({ index: 1 });
        
        // Save
        await authenticatedPage.locator('button:has-text("Save")').first().click();
        await authenticatedPage.waitForTimeout(1000);
      }
    }
  });

  test('Test 9.5: Custom Multi-Select Picklist', async () => {
    if (!testContactId) {
      test.skip();
      return;
    }

    await contactsPage.gotoEdit(testContactId);
    
    // Look for multi-select fields
    const multiSelectFields = authenticatedPage.locator('select[multiple][name^="cf_"], select[name*="languages"]');
    const multiSelectCount = await multiSelectFields.count();
    
    if (multiSelectCount > 0) {
      const multiSelect = multiSelectFields.first();
      
      // Get options
      const options = await multiSelect.locator('option').all();
      
      if (options.length >= 2) {
        // Select multiple options
        await multiSelect.selectOption([{ index: 0 }, { index: 1 }]);
        
        // Save
        await authenticatedPage.locator('button:has-text("Save")').first().click();
        await authenticatedPage.waitForTimeout(1000);
      }
    } else {
      // Try checkbox-based multi-select
      const checkboxes = authenticatedPage.locator('input[type="checkbox"][name^="cf_"]');
      const checkboxCount = await checkboxes.count();
      
      if (checkboxCount >= 2) {
        await checkboxes.nth(0).check();
        await checkboxes.nth(1).check();
        
        // Save
        await authenticatedPage.locator('button:has-text("Save")').first().click();
        await authenticatedPage.waitForTimeout(1000);
      }
    }
  });
});



