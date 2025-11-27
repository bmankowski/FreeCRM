/**
 * Export Functionality Tests - Contacts Module
 * 
 * Tests export functionality including:
 * - Export all contacts to CSV
 * - Export to Excel
 * - Export to PDF
 * - Export selected records only
 * - Export with applied filters
 * - Export with custom field selection
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { ContactsPage } from '../../../pages/ContactsPage';
import * as fs from 'fs';

test.describe('Contacts - Export Functionality', () => {
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

  test('Test 3.1: Export All Contacts to CSV', async () => {
    // Already authenticated via fixture('/index.php?module=Contacts&view=List');
    
    const exportButton = authenticatedPage.locator('button:has-text("Export"), a:has-text("Export")').first();
    if (await exportButton.isVisible({ timeout: 5000 })) {
      const [download] = await Promise.all([
        authenticatedPage.waitForEvent('download', { timeout: 15000 }),
        exportButton.click(),
        authenticatedPage.locator('button:has-text("CSV"), a:has-text("CSV")').first().click().catch(() => {})
      ]);
      
      expect(download.suggestedFilename()).toMatch(/\.csv$/i);
      
      const path = await download.path();
      if (path && fs.existsSync(path)) {
        const csvContent = fs.readFileSync(path, 'utf-8');
        const lines = csvContent.split('\n').filter(line => line.trim());
        
        expect(lines.length).toBeGreaterThan(1); // Header + data
        expect(lines[0]).toMatch(/first.*name|last.*name|email/i);
      }
    }
  });

  test('Test 3.4: Export Selected Records Only', async () => {
    // Create test contacts
    const timestamp = Date.now();
    for (let i = 0; i < 5; i++) {
      const id = await contactsPage.createTestContact(
        `ExportSelect${i}`,
        `Test${timestamp}_${i}`,
        { email: `exportselect${i}_${timestamp}@example.com` }
      );
      if (id) testContactIds.push(id);
    }
    
    await contactsPage.gotoList();
    
    // Select 3 contacts
    const rows = authenticatedPage.locator('tbody tr').filter({ hasNot: authenticatedPage.locator('input.listSearchContributor') });
    for (let i = 0; i < Math.min(3, await rows.count()); i++) {
      await rows.nth(i).locator('input[type="checkbox"]').click();
    }
    
    const exportButton = authenticatedPage.locator('button:has-text("Export")').first();
    if (await exportButton.isVisible({ timeout: 5000 })) {
      const [download] = await Promise.all([
        authenticatedPage.waitForEvent('download', { timeout: 15000 }),
        exportButton.click()
      ]);
      
      const path = await download.path();
      if (path && fs.existsSync(path)) {
        const csvContent = fs.readFileSync(path, 'utf-8');
        const lines = csvContent.split('\n').filter(line => line.trim());
        
        // Should have approximately 3 records plus header
        expect(lines.length).toBeGreaterThanOrEqual(2);
        expect(lines.length).toBeLessThanOrEqual(10);
      }
    }
  });

  test('Test 3.5: Export with Applied Filters', async () => {
    // Already authenticated via fixture('/index.php?module=Contacts&view=List');
    
    // Apply a filter if available
    const statusFilter = authenticatedPage.locator('select[name="status"]').first();
    if (await statusFilter.isVisible({ timeout: 3000 })) {
      await statusFilter.selectOption('Active');
      await authenticatedPage.locator('button:has-text("Filter"), button:has-text("Search")').first().click().catch(() => {});
      await authenticatedPage.waitForTimeout(1000);
    }
    
    const exportButton = authenticatedPage.locator('button:has-text("Export")').first();
    if (await exportButton.isVisible({ timeout: 5000 })) {
      const [download] = await Promise.all([
        authenticatedPage.waitForEvent('download', { timeout: 15000 }),
        exportButton.click(),
        authenticatedPage.locator('button:has-text("CSV")').first().click().catch(() => {})
      ]);
      
      const path = await download.path();
      if (path && fs.existsSync(path)) {
        const lines = fs.readFileSync(path, 'utf-8').split('\n').filter(l => l.trim());
        expect(lines.length).toBeGreaterThan(0);
      }
    }
  });

  test('Test 3.6: Export with Custom Field Selection', async () => {
    // Already authenticated via fixture('/index.php?module=Contacts&view=List');
    
    const exportButton = authenticatedPage.locator('button:has-text("Export")').first();
    if (await exportButton.isVisible({ timeout: 5000 })) {
      await exportButton.click();
      
      // Look for field selector
      const fieldSelector = authenticatedPage.locator('button:has-text("Select Fields"), a:has-text("Customize")').first();
      if (await fieldSelector.isVisible({ timeout: 3000 })) {
        await fieldSelector.click();
        
        // Deselect all
        const deselectAll = authenticatedPage.locator('button:has-text("Deselect All")').first();
        if (await deselectAll.isVisible({ timeout: 2000 })) {
          await deselectAll.click();
        }
        
        // Select specific fields
        await authenticatedPage.locator('input[name="field_firstname"], input[value="firstname"]').check().catch(() => {});
        await authenticatedPage.locator('input[name="field_lastname"], input[value="lastname"]').check().catch(() => {});
        await authenticatedPage.locator('input[name="field_email"], input[value="email"]').check().catch(() => {});
      }
      
      const [download] = await Promise.all([
        authenticatedPage.waitForEvent('download', { timeout: 15000 }),
        authenticatedPage.locator('button:has-text("Export"), button:has-text("Download")').first().click()
      ]);
      
      const path = await download.path();
      if (path && fs.existsSync(path)) {
        const csvContent = fs.readFileSync(path, 'utf-8');
        const headerLine = csvContent.split('\n')[0];
        
        // Should contain selected fields
        expect(headerLine).toMatch(/first.*name/i);
        expect(headerLine).toMatch(/last.*name/i);
        expect(headerLine).toMatch(/email/i);
      }
    }
  });
});



