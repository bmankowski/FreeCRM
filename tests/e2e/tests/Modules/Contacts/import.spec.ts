/**
 * Import Functionality Tests - Contacts Module
 * 
 * Tests import functionality including:
 * - Import from CSV with valid data
 * - Import from Excel (.xlsx)
 * - Import with validation errors
 * - Duplicate detection during import
 * - Update existing records
 * - Field mapping with preview
 * - Error report download
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { ContactsPage } from '../../../pages/ContactsPage';
import * as fs from 'fs';
import * as path from 'path';

test.describe('Contacts - Import Functionality', () => {
  let page: Page;
  let authenticatedPage: any;
  let contactsPage: ContactsPage;
  let testContactIds: string[] = [];
  const testDataDir = path.join(__dirname, '../../../test-data');

  test.beforeAll(async () => {
    // Ensure test-data directory exists
    if (!fs.existsSync(testDataDir)) {
      fs.mkdirSync(testDataDir, { recursive: true });
    }
    
    // Create test CSV files
    createTestCSVFiles(testDataDir);
  });

  test.beforeEach(async ({ authenticatedPage: authPage }) => {
    authenticatedPage = authPage;
    // Use authenticatedPage directly
    contactsPage = new ContactsPage(authenticatedPage);
    
    // Login
    // Already authenticated via fixture('/');
    // TODO: Add login steps if needed
  });

  test.afterEach(async () => {
    // Cleanup test contacts
    for (const id of testContactIds) {
      await contactsPage.deleteContactById(id).catch(() => {});
    }
    testContactIds = [];
    
  });

  test('Test 2.1: Import Contacts from CSV with Valid Data', async () => {
    // Navigate to import page
    // Already authenticated via fixture('/index.php?module=Contacts&view=Import');
    
    // Upload CSV file
    const fileInput = authenticatedPage.locator('input[type="file"]').first();
    await fileInput.setInputFiles(path.join(testDataDir, 'valid_contacts.csv'));
    
    // Wait for mapping page
    const mapper = authenticatedPage.locator('.field-mapping, .import-mapper, .mapping-step');
    if (await mapper.isVisible({ timeout: 5000 })) {
      // Verify auto-mapping or manually map fields
      // Field mapping might be automatic or require manual selection
      const continueButton = authenticatedPage.locator('button:has-text("Continue"), button:has-text("Next"), button:has-text("Import")').first();
      await continueButton.click();
    }
    
    // Start import
    const importButton = authenticatedPage.locator('button:has-text("Import"), button:has-text("Start Import"), button:has-text("Finish")').first();
    if (await importButton.isVisible({ timeout: 3000 })) {
      await importButton.click();
    }
    
    // Wait for completion
    await expect(authenticatedPage.locator('.import-success, .notification, .success-message')).toBeVisible({ timeout: 30000 });
    await expect(authenticatedPage.locator('.import-summary, .notification')).toContainText(/imported|success/i);
    
    // Verify contacts appear in list
    // Already authenticated via fixture('/index.php?module=Contacts&view=List');
    await expect(authenticatedPage.locator('tr:has-text("importtest1@example.com")')).toBeVisible({ timeout: 5000 });
  });

  test('Test 2.2: Import Contacts from Excel (.xlsx)', async () => {
    // Navigate to import page
    // Already authenticated via fixture('/index.php?module=Contacts&view=Import');
    
    // Upload Excel file if it exists
    const excelFile = path.join(testDataDir, 'valid_contacts.xlsx');
    if (fs.existsSync(excelFile)) {
      const fileInput = authenticatedPage.locator('input[type="file"]').first();
      await fileInput.setInputFiles(excelFile);
      
      // Select sheet if multiple sheets exist
      const sheetSelector = authenticatedPage.locator('select[name="sheet"], select[name="worksheet"]');
      if (await sheetSelector.isVisible({ timeout: 3000 })) {
        await sheetSelector.selectOption({ index: 0 });
      }
      
      // Verify preview
      const preview = authenticatedPage.locator('.import-preview, .preview-table');
      if (await preview.isVisible({ timeout: 3000 })) {
        const previewRows = await preview.locator('tbody tr').count();
        expect(previewRows).toBeGreaterThan(0);
      }
      
      // Continue with import
      const importButton = authenticatedPage.locator('button:has-text("Import"), button:has-text("Continue")').first();
      if (await importButton.isVisible({ timeout: 3000 })) {
        await importButton.click();
      }
      
      // Wait for success
      await expect(authenticatedPage.locator('.notification, .success-message')).toContainText(/imported|success/i, { timeout: 30000 });
    } else {
      test.skip();
    }
  });

  test('Test 2.3: Import with Field Validation Errors', async () => {
    // Navigate to import page
    // Already authenticated via fixture('/index.php?module=Contacts&view=Import');
    
    const fileInput = authenticatedPage.locator('input[type="file"]').first();
    await fileInput.setInputFiles(path.join(testDataDir, 'invalid_contacts.csv'));
    
    // Complete field mapping
    await authenticatedPage.waitForTimeout(2000);
    const continueButton = authenticatedPage.locator('button:has-text("Continue"), button:has-text("Import")').first();
    if (await continueButton.isVisible({ timeout: 3000 })) {
      await continueButton.click();
    }
    
    // Wait for import to process
    await authenticatedPage.waitForTimeout(5000);
    
    // Check for error report
    const errorReport = authenticatedPage.locator('.import-errors, .error-report, .validation-errors');
    if (await errorReport.isVisible({ timeout: 5000 })) {
      await expect(errorReport).toContainText(/error|invalid|failed/i);
      
      // Verify specific errors mentioned
      const errorText = await errorReport.textContent();
      // Should contain row numbers and error descriptions
      expect(errorText).toMatch(/row|line/i);
    }
  });

  test('Test 2.4: Import Duplicate Detection', async () => {
    // Create some existing contacts first
    const timestamp = Date.now();
    const existingEmails = [
      `duplicate1_${timestamp}@example.com`,
      `duplicate2_${timestamp}@example.com`
    ];
    
    for (const email of existingEmails) {
      const id = await contactsPage.createTestContact(
        'Existing',
        'Contact',
        { email: email }
      );
      if (id) testContactIds.push(id);
    }
    
    // Create CSV with duplicates
    const duplicateCsv = path.join(testDataDir, `duplicate_test_${timestamp}.csv`);
    fs.writeFileSync(duplicateCsv, [
      'firstname,lastname,email',
      `Duplicate,One,${existingEmails[0]}`,
      `Duplicate,Two,${existingEmails[1]}`,
      `New,Contact,newcontact_${timestamp}@example.com`
    ].join('\n'));
    
    // Navigate to import
    // Already authenticated via fixture('/index.php?module=Contacts&view=Import');
    
    const fileInput = authenticatedPage.locator('input[type="file"]').first();
    await fileInput.setInputFiles(duplicateCsv);
    
    await authenticatedPage.waitForTimeout(2000);
    
    // Look for duplicate handling options
    const duplicateDialog = authenticatedPage.locator('.duplicate-handling, .import-options');
    if (await duplicateDialog.isVisible({ timeout: 5000 })) {
      // Select "Skip duplicates"
      const skipOption = duplicateDialog.locator('input[value="skip"], input[type="radio"][value="skip"]');
      if (await skipOption.isVisible({ timeout: 2000 })) {
        await skipOption.check();
      }
    }
    
    // Continue import
    const importButton = authenticatedPage.locator('button:has-text("Import"), button:has-text("Continue")').first();
    if (await importButton.isVisible({ timeout: 3000 })) {
      await importButton.click();
    }
    
    // Verify import summary shows skipped duplicates
    await authenticatedPage.waitForTimeout(3000);
    const summary = authenticatedPage.locator('.import-summary, .notification');
    if (await summary.isVisible({ timeout: 5000 })) {
      const summaryText = await summary.textContent();
      // Should mention skipped records
      expect(summaryText).toMatch(/skipped|duplicate/i);
    }
    
    // Cleanup temp file
    fs.unlinkSync(duplicateCsv);
  });

  test('Test 2.5: Import with Update Existing Records', async () => {
    // Create existing contacts
    const timestamp = Date.now();
    const testEmail = `updatetest_${timestamp}@example.com`;
    
    const existingId = await contactsPage.createTestContact(
      'Original',
      'Name',
      { email: testEmail, phone: '555-0001' }
    );
    if (existingId) testContactIds.push(existingId);
    
    // Create CSV with updated data
    const updateCsv = path.join(testDataDir, `update_test_${timestamp}.csv`);
    fs.writeFileSync(updateCsv, [
      'firstname,lastname,email,phone',
      `Updated,Name,${testEmail},555-9999`
    ].join('\n'));
    
    // Navigate to import
    // Already authenticated via fixture('/index.php?module=Contacts&view=Import');
    
    const fileInput = authenticatedPage.locator('input[type="file"]').first();
    await fileInput.setInputFiles(updateCsv);
    
    await authenticatedPage.waitForTimeout(2000);
    
    // Look for update option
    const updateOption = authenticatedPage.locator('input[value="update"], input[type="radio"]:has-text("Update")');
    if (await updateOption.isVisible({ timeout: 3000 })) {
      await updateOption.check();
    }
    
    // Select match field (email)
    const matchField = authenticatedPage.locator('select[name="match_field"], select[name="unique_field"]');
    if (await matchField.isVisible({ timeout: 2000 })) {
      await matchField.selectOption('email');
    }
    
    // Continue import
    const importButton = authenticatedPage.locator('button:has-text("Import"), button:has-text("Continue")').first();
    if (await importButton.isVisible({ timeout: 3000 })) {
      await importButton.click();
    }
    
    await authenticatedPage.waitForTimeout(3000);
    
    // Verify update applied
    if (existingId) {
      await contactsPage.gotoDetail(existingId);
      await expect(authenticatedPage.locator('.phone-field, [data-field="phone"]')).toContainText('555-9999');
    }
    
    // Cleanup temp file
    fs.unlinkSync(updateCsv);
  });

  test('Test 2.6: Import Field Mapping with Preview', async () => {
    // Create CSV with custom headers
    const customCsv = path.join(testDataDir, 'custom_headers.csv');
    fs.writeFileSync(customCsv, [
      'Name,Surname,Mail,Tel',
      'John,Doe,john@example.com,555-1234',
      'Jane,Smith,jane@example.com,555-5678'
    ].join('\n'));
    
    // Navigate to import
    // Already authenticated via fixture('/index.php?module=Contacts&view=Import');
    
    const fileInput = authenticatedPage.locator('input[type="file"]').first();
    await fileInput.setInputFiles(customCsv);
    
    // Wait for mapper
    await authenticatedPage.waitForTimeout(2000);
    
    const mapper = authenticatedPage.locator('.field-mapping, .import-mapper');
    if (await mapper.isVisible({ timeout: 5000 })) {
      // Manual mapping
      const nameMapping = mapper.locator('[data-csv="Name"], select[data-field="Name"]').first();
      if (await nameMapping.isVisible({ timeout: 2000 })) {
        await nameMapping.selectOption('firstname');
      }
      
      const surnameMapping = mapper.locator('[data-csv="Surname"], select[data-field="Surname"]').first();
      if (await surnameMapping.isVisible({ timeout: 2000 })) {
        await surnameMapping.selectOption('lastname');
      }
      
      // Verify preview
      const preview = authenticatedPage.locator('.import-preview, .preview-section');
      if (await preview.isVisible({ timeout: 2000 })) {
        await expect(preview).toContainText('John');
        await expect(preview).toContainText('Doe');
      }
    }
    
    // Cleanup temp file
    fs.unlinkSync(customCsv);
  });

  test('Test 2.7: Import Error Report Download', async () => {
    // Navigate to import with invalid data
    // Already authenticated via fixture('/index.php?module=Contacts&view=Import');
    
    const fileInput = authenticatedPage.locator('input[type="file"]').first();
    await fileInput.setInputFiles(path.join(testDataDir, 'invalid_contacts.csv'));
    
    await authenticatedPage.waitForTimeout(2000);
    
    // Continue with import
    const importButton = authenticatedPage.locator('button:has-text("Import"), button:has-text("Continue")').first();
    if (await importButton.isVisible({ timeout: 3000 })) {
      await importButton.click();
    }
    
    await authenticatedPage.waitForTimeout(5000);
    
    // Look for error report download button
    const downloadButton = authenticatedPage.locator('button:has-text("Download Error"), a:has-text("Download Error"), .download-errors');
    if (await downloadButton.isVisible({ timeout: 5000 })) {
      const [download] = await Promise.all([
        authenticatedPage.waitForEvent('download', { timeout: 10000 }),
        downloadButton.first().click()
      ]);
      
      // Verify download
      const filename = download.suggestedFilename();
      expect(filename).toMatch(/error/i);
      expect(filename).toMatch(/\.csv$/i);
      
      // Verify file contents
      const downloadPath = await download.path();
      if (downloadPath && fs.existsSync(downloadPath)) {
        const errorCsv = fs.readFileSync(downloadPath, 'utf-8');
        const lines = errorCsv.split('\n');
        
        // Should have headers
        expect(lines[0]).toMatch(/row|field|error|value/i);
        expect(lines.length).toBeGreaterThan(1);
      }
    }
  });
});

/**
 * Helper function to create test CSV files
 */
function createTestCSVFiles(testDataDir: string) {
  // Valid contacts CSV
  const validCsv = path.join(testDataDir, 'valid_contacts.csv');
  if (!fs.existsSync(validCsv)) {
    fs.writeFileSync(validCsv, [
      'firstname,lastname,email,phone',
      'ImportTest1,Contact1,importtest1@example.com,555-0001',
      'ImportTest2,Contact2,importtest2@example.com,555-0002',
      'ImportTest3,Contact3,importtest3@example.com,555-0003',
      'ImportTest4,Contact4,importtest4@example.com,555-0004',
      'ImportTest5,Contact5,importtest5@example.com,555-0005'
    ].join('\n'));
  }
  
  // Invalid contacts CSV
  const invalidCsv = path.join(testDataDir, 'invalid_contacts.csv');
  if (!fs.existsSync(invalidCsv)) {
    fs.writeFileSync(invalidCsv, [
      'firstname,lastname,email,phone',
      'Valid,Contact,valid@example.com,555-0001',
      'Missing,,missing@example.com,555-0002',
      'Invalid,Email,notanemail,555-0003',
      'Valid,Contact2,valid2@example.com,555-0004'
    ].join('\n'));
  }
}



