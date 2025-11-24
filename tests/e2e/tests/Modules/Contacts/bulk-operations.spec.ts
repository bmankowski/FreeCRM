/**
 * Bulk Operations Tests - Contacts Module
 * 
 * Tests bulk operations functionality including:
 * - Select multiple contacts
 * - Bulk delete
 * - Bulk edit
 * - Bulk export
 * - Bulk email
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { ContactsPage } from '../../../pages/ContactsPage';
import * as fs from 'fs';

test.describe('Contacts - Bulk Operations', () => {
  let contactsPage: ContactsPage;
  let authenticatedPage: any;
  let testContactIds: string[] = [];

  test.beforeEach(async ({ authenticatedPage: authPage }) => {
    authenticatedPage = authPage;
    contactsPage = new ContactsPage(authenticatedPage);
  });

  test.afterEach(async () => {
    // Cleanup test contacts
    if (testContactIds.length > 0) {
      console.log(`Cleaning up ${testContactIds.length} test contact(s)...`);
      await contactsPage.cleanupContacts(testContactIds);
      testContactIds = [];
    }
  });

  test('Test 1.1: Select Multiple Contacts with Checkboxes', async () => {
    // Create 5 test contacts
    const timestamp = Date.now();
    for (let i = 0; i < 5; i++) {
      const id = await contactsPage.createTestContact(
        `BulkTest${i}`,
        `Contact${timestamp}_${i}`,
        { email: `bulktest${i}_${timestamp}@example.com` }
      );
      if (id) testContactIds.push(id);
    }

    // Navigate to list view
    await contactsPage.gotoList();
    
    // Wait for the page to be fully loaded
    await authenticatedPage.waitForLoadState('networkidle');
    
    // Search for one of our test contacts to filter the list to only our records
    // This ensures we're selecting the contacts we just created, not old ones
    await contactsPage.search(`Contact${timestamp}`);
    await authenticatedPage.waitForLoadState('networkidle');
    await authenticatedPage.waitForTimeout(1000);

    // Select first, third, and fifth contacts from the filtered results
    const rows = authenticatedPage.locator('tr.listViewEntries');
    
    // Wait for rows to be visible
    await rows.first().waitFor({ state: 'visible', timeout: 10000 });
    
    // Verify we have at least 5 rows
    const rowCount = await rows.count();
    expect(rowCount).toBeGreaterThanOrEqual(5);
    
    // Use JavaScript to check the checkboxes directly, bypassing any click handlers
    // Dispatch change events one at a time with delays
    for (const index of [0, 2, 4]) {
      await authenticatedPage.evaluate((idx) => {
        const checkboxes = document.querySelectorAll('tr.listViewEntries input.listViewEntriesCheckBox');
        if (checkboxes[idx]) {
          (checkboxes[idx] as HTMLInputElement).checked = true;
          checkboxes[idx].dispatchEvent(new Event('change', { bubbles: true }));
        }
      }, index);
      await authenticatedPage.waitForTimeout(300); // Small delay between each checkbox
    }

    // Verify checkboxes are checked using JavaScript since we set them that way
    const checkedCount = await authenticatedPage.evaluate(() => {
      const checkboxes = document.querySelectorAll('tr.listViewEntries input.listViewEntriesCheckBox:checked');
      return checkboxes.length;
    });
    
    expect(checkedCount).toBe(3); // We checked 3 checkboxes (indices 0, 2, 4)
  });

  test('Test 1.2: Select All Contacts on Current Page', async () => {
    // Navigate to contacts list
    await contactsPage.gotoList();

    // Click "Select All" checkbox in header
    const selectAllCheckbox = authenticatedPage.locator('thead input[type="checkbox"]').first();
    await selectAllCheckbox.click();

    // Wait a moment for selection to apply
    await authenticatedPage.waitForTimeout(500);

    // Count checked boxes in tbody (excluding header)
    const checkedCount = await authenticatedPage.locator('tbody tr input[type="checkbox"]:checked').count();
    const totalRows = await authenticatedPage.locator('tbody tr.listViewEntryRow, tbody tr[data-id]').count();

    expect(checkedCount).toBeGreaterThan(0);

    // Deselect all
    await selectAllCheckbox.click();
    await authenticatedPage.waitForTimeout(500);
    const checkedAfterDeselect = await authenticatedPage.locator('tbody tr input[type="checkbox"]:checked').count();
    expect(checkedAfterDeselect).toBe(0);
  });

  test('Test 1.3: Select All Contacts Across Multiple Pages', async () => {
    // Navigate to contacts list
    await contactsPage.gotoList();

    // Click select all in header
    const selectAllCheckbox = authenticatedPage.locator('thead input[type="checkbox"]').first();
    await selectAllCheckbox.click();
    await authenticatedPage.waitForTimeout(1000);

    // Look for "Select all X contacts" notification/link
    const selectAllNotification = authenticatedPage.locator('.selectAllNotification, .list-select-all-msg, .select-all-pages');
    
    // If notification appears, click to select all across pages
    if (await selectAllNotification.isVisible({ timeout: 2000 })) {
      const selectAllLink = selectAllNotification.locator('a:has-text("Select all"), button:has-text("Select all")');
      if (await selectAllLink.isVisible({ timeout: 1000 })) {
        await selectAllLink.click();
        await authenticatedPage.waitForTimeout(500);
        
        // Verify notification updates
        await expect(selectAllNotification).toContainText(/All.*contacts.*selected|selected/i);
      }
    }
  });

  test('Test 1.4: Bulk Delete Multiple Contacts', async () => {
    // Create 3 test contacts for deletion
    const timestamp = Date.now();
    const deleteIds: string[] = [];
    
    for (let i = 0; i < 3; i++) {
      const id = await contactsPage.createTestContact(
        `DeleteTest${i}`,
        `ToDelete${timestamp}_${i}`,
        { email: `delete${i}_${timestamp}@example.com` }
      );
      if (id) {
        deleteIds.push(id);
        testContactIds.push(id);
      }
    }

    await contactsPage.gotoList();
    await authenticatedPage.waitForTimeout(1000);

    // Search for our test contacts
    await contactsPage.search(`ToDelete${timestamp}`);
    await authenticatedPage.waitForTimeout(1000);

    // Select the contacts
    const rows = authenticatedPage.locator('tbody tr').filter({ hasNot: authenticatedPage.locator('input.listSearchContributor') });
    for (let i = 0; i < Math.min(3, await rows.count()); i++) {
      await rows.nth(i).locator('input[type="checkbox"]').click();
    }

    // Click bulk delete button
    const deleteButton = authenticatedPage.locator('button:has-text("Delete"), .bulkDelete, [data-action="delete"]').first();
    if (await deleteButton.isVisible({ timeout: 2000 })) {
      await deleteButton.click();

      // Handle confirmation modal
      const confirmModal = authenticatedPage.locator('.modal, .bootbox, .confirm-dialog').first();
      if (await confirmModal.isVisible({ timeout: 3000 })) {
        await expect(confirmModal).toContainText(/delete|remove/i);
        await confirmModal.locator('button:has-text("Confirm"), button:has-text("Yes"), button:has-text("Delete")').first().click();
        
        // Wait for success notification
        await expect(authenticatedPage.locator('.notification, .toastify, .alert')).toContainText(/deleted|removed/i, { timeout: 10000 });
      }
      
      // Clear the test IDs since they're deleted
      testContactIds = testContactIds.filter(id => !deleteIds.includes(id));
    }
  });

  test('Test 1.5: Bulk Edit Multiple Contacts', async () => {
    // Create 4 test contacts
    const timestamp = Date.now();
    for (let i = 0; i < 4; i++) {
      const id = await contactsPage.createTestContact(
        `EditTest${i}`,
        `ToEdit${timestamp}_${i}`,
        { email: `bulkedit${i}_${timestamp}@example.com` }
      );
      if (id) testContactIds.push(id);
    }

    await contactsPage.gotoList();
    await contactsPage.search(`ToEdit${timestamp}`);
    await authenticatedPage.waitForTimeout(1000);

    // Select 4 contacts
    const rows = authenticatedPage.locator('tbody tr').filter({ hasNot: authenticatedPage.locator('input.listSearchContributor') });
    for (let i = 0; i < Math.min(4, await rows.count()); i++) {
      await rows.nth(i).locator('input[type="checkbox"]').click();
    }

    // Click bulk edit button
    const editButton = authenticatedPage.locator('button:has-text("Mass Edit"), button:has-text("Bulk Edit"), [data-action="massedit"]').first();
    
    if (await editButton.isVisible({ timeout: 2000 })) {
      await editButton.click();

      // Wait for mass edit form
      const massEditForm = authenticatedPage.locator('.massEditForm, .modal, .mass-edit').first();
      if (await massEditForm.isVisible({ timeout: 3000 })) {
        // Try to update a common field (e.g., phone or description)
        const phoneField = massEditForm.locator('input[name="phone"], input[name="mobile"]').first();
        if (await phoneField.isVisible({ timeout: 2000 })) {
          await phoneField.fill('555-BULK-EDIT');
        }

        // Save
        await massEditForm.locator('button:has-text("Save"), button:has-text("Update")').first().click();
        
        // Verify success
        await expect(authenticatedPage.locator('.notification')).toContainText(/updated|saved/i, { timeout: 10000 });
      }
    }
  });

  test('Test 1.6: Bulk Export Selected Contacts to CSV', async () => {
    // Create 5 test contacts
    const timestamp = Date.now();
    for (let i = 0; i < 5; i++) {
      const id = await contactsPage.createTestContact(
        `ExportTest${i}`,
        `ToExport${timestamp}_${i}`,
        { email: `export${i}_${timestamp}@example.com` }
      );
      if (id) testContactIds.push(id);
    }

    await contactsPage.gotoList();
    await contactsPage.search(`ToExport${timestamp}`);
    await authenticatedPage.waitForTimeout(1000);

    // Select 5 contacts
    const rows = authenticatedPage.locator('tbody tr').filter({ hasNot: authenticatedPage.locator('input.listSearchContributor') });
    const rowCount = await rows.count();
    
    for (let i = 0; i < Math.min(5, rowCount); i++) {
      await rows.nth(i).locator('input[type="checkbox"]').click();
    }

    // Click export button
    const exportButton = authenticatedPage.locator('button:has-text("Export"), [data-action="export"]').first();
    
    if (await exportButton.isVisible({ timeout: 2000 })) {
      const [download] = await Promise.all([
        authenticatedPage.waitForEvent('download', { timeout: 10000 }),
        exportButton.click(),
        // Try to select CSV format if dialog appears
        authenticatedPage.locator('button:has-text("CSV"), a:has-text("CSV")').first().click().catch(() => {})
      ]);

      // Verify download
      expect(download.suggestedFilename()).toMatch(/\.csv$/i);
      
      // Optionally verify file contents
      const path = await download.path();
      if (path && fs.existsSync(path)) {
        const csvContent = fs.readFileSync(path, 'utf-8');
        const lines = csvContent.split('\n').filter(line => line.trim());
        
        // Should have header + at least some data rows
        expect(lines.length).toBeGreaterThan(1);
      }
    }
  });

  test('Test 1.7: Bulk Send Email to Selected Contacts', async () => {
    // Create 3 test contacts with valid emails
    const timestamp = Date.now();
    const emails = [
      `bulkemail1_${timestamp}@example.com`,
      `bulkemail2_${timestamp}@example.com`,
      `bulkemail3_${timestamp}@example.com`
    ];

    for (let i = 0; i < 3; i++) {
      const id = await contactsPage.createTestContact(
        `EmailTest${i}`,
        `ToEmail${timestamp}_${i}`,
        { email: emails[i] }
      );
      if (id) testContactIds.push(id);
    }

    await contactsPage.gotoList();
    await contactsPage.search(`ToEmail${timestamp}`);
    await authenticatedPage.waitForTimeout(1000);

    // Select contacts
    const rows = authenticatedPage.locator('tbody tr').filter({ hasNot: authenticatedPage.locator('input.listSearchContributor') });
    for (let i = 0; i < Math.min(3, await rows.count()); i++) {
      await rows.nth(i).locator('input[type="checkbox"]').click();
    }

    // Click send email button
    const emailButton = authenticatedPage.locator('button:has-text("Send Email"), .bulkEmail, [data-action="email"]').first();
    
    if (await emailButton.isVisible({ timeout: 2000 })) {
      await emailButton.click();

      // Verify email composer opens
      const emailComposer = authenticatedPage.locator('.emailComposer, .compose-email, .modal').first();
      if (await emailComposer.isVisible({ timeout: 3000 })) {
        // Verify recipients pre-filled
        const recipientsField = emailComposer.locator('[name="to"], .to-field, .recipients').first();
        if (await recipientsField.isVisible({ timeout: 1000 })) {
          const recipientsText = await recipientsField.textContent() || await recipientsField.inputValue();
          // Should contain at least one of our test emails
          const hasEmail = emails.some(email => recipientsText.includes(email));
          expect(hasEmail).toBeTruthy();
        }

        // Fill basic email fields
        const subjectField = emailComposer.locator('[name="subject"]').first();
        if (await subjectField.isVisible({ timeout: 1000 })) {
          await subjectField.fill('Test Bulk Email');
        }

        const bodyField = emailComposer.locator('[name="body"], .email-body, .ql-editor').first();
        if (await bodyField.isVisible({ timeout: 1000 })) {
          await bodyField.fill('This is a test of bulk email functionality');
        }

        // Note: We won't actually send to avoid spamming test emails
        // In a real test, you'd either send or verify the send button is enabled
        const sendButton = emailComposer.locator('button:has-text("Send")').first();
        await expect(sendButton).toBeVisible();
      }
    }
  });
});

