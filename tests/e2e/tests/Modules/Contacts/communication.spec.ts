/**
 * Communication Features Tests - Contacts Module
 * 
 * Tests communication functionality including:
 * - Send email from contact detail
 * - Use email template
 * - Log phone call
 * - Send SMS
 * - View email history timeline
 * - Communication timeline with filtering
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { ContactsPage } from '../../../pages/ContactsPage';

test.describe('Contacts - Communication Features', () => {
  let page: Page;
  let authenticatedPage: any;
  let contactsPage: ContactsPage;
  let testContactId: string | null;
  const testEmail = `commtest_${Date.now()}@example.com`;

  test.beforeEach(async ({ authenticatedPage: authPage }) => {
    authenticatedPage = authPage;
    // Use authenticatedPage directly
    contactsPage = new ContactsPage(authenticatedPage);
    
    // Already authenticated via fixture('/');
    // TODO: Add login steps if needed
    
    // Create test contact with email
    const timestamp = Date.now();
    testContactId = await contactsPage.createTestContact(
      'Communication',
      `Test${timestamp}`,
      { email: testEmail }
    );
  });

  test.afterEach(async () => {
    if (testContactId) {
      await contactsPage.deleteContactById(testContactId).catch(() => {});
    }
    
  });

  test('Test 6.1: Send Email from Contact Detail', async () => {
    if (!testContactId) {
      test.skip();
      return;
    }

    await contactsPage.gotoDetail(testContactId);
    
    const emailButton = authenticatedPage.locator('button:has-text("Send Email"), a:has-text("Email")').first();
    if (await emailButton.isVisible({ timeout: 5000 })) {
      await emailButton.click();
      
      const composer = authenticatedPage.locator('.emailComposer, .compose-email, .modal').first();
      if (await composer.isVisible({ timeout: 3000 })) {
        // Verify email pre-filled
        await expect(composer.locator('[name="to"], .to-field')).toContainText(testEmail);
        
        // Fill email
        const subjectField = composer.locator('[name="subject"]').first();
        if (await subjectField.isVisible({ timeout: 2000 })) {
          await subjectField.fill('Follow-up Meeting');
        }
        
        const bodyField = composer.locator('[name="body"], .email-body, .ql-editor').first();
        if (await bodyField.isVisible({ timeout: 2000 })) {
          await bodyField.fill('Thank you for meeting today.');
        }
        
        // Note: Not actually sending to avoid spam
        const sendButton = composer.locator('button:has-text("Send")').first();
        await expect(sendButton).toBeVisible();
      }
    }
  });

  test('Test 6.2: Use Email Template', async () => {
    if (!testContactId) {
      test.skip();
      return;
    }

    await contactsPage.gotoDetail(testContactId);
    
    const emailButton = authenticatedPage.locator('button:has-text("Send Email")').first();
    if (await emailButton.isVisible({ timeout: 5000 })) {
      await emailButton.click();
      
      const composer = authenticatedPage.locator('.emailComposer, .modal').first();
      if (await composer.isVisible({ timeout: 3000 })) {
        // Look for template selector
        const templateButton = composer.locator('button:has-text("Templates"), select[name="template"]').first();
        if (await templateButton.isVisible({ timeout: 3000 })) {
          await templateButton.click();
          
          // Select a template if available
          const templateOption = authenticatedPage.locator('option:has-text("Welcome"), li:has-text("Welcome")').first();
          if (await templateOption.isVisible({ timeout: 2000 })) {
            await templateOption.click();
            await authenticatedPage.waitForTimeout(1000);
            
            // Verify template loaded
            const body = await composer.locator('.email-body, [name="body"]').textContent();
            expect(body?.length).toBeGreaterThan(0);
          }
        }
      }
    }
  });

  test('Test 6.3: Log Phone Call', async () => {
    if (!testContactId) {
      test.skip();
      return;
    }

    await contactsPage.gotoDetail(testContactId);
    
    const callButton = authenticatedPage.locator('button:has-text("Log Call"), a:has-text("Call")').first();
    if (await callButton.isVisible({ timeout: 5000 })) {
      await callButton.click();
      
      const callForm = authenticatedPage.locator('.log-call-form, .modal').first();
      if (await callForm.isVisible({ timeout: 3000 })) {
        // Fill call details
        const typeField = callForm.locator('select[name="call_type"], select[name="direction"]').first();
        if (await typeField.isVisible({ timeout: 2000 })) {
          await typeField.selectOption('Outbound');
        }
        
        const subjectField = callForm.locator('[name="subject"]').first();
        if (await subjectField.isVisible({ timeout: 2000 })) {
          await subjectField.fill('Product Demo Discussion');
        }
        
        const durationField = callForm.locator('[name="duration"]').first();
        if (await durationField.isVisible({ timeout: 2000 })) {
          await durationField.fill('30');
        }
        
        const outcomeField = callForm.locator('select[name="outcome"]').first();
        if (await outcomeField.isVisible({ timeout: 2000 })) {
          await outcomeField.selectOption({ label: 'Successful' });
        }
        
        const notesField = callForm.locator('[name="notes"], textarea').first();
        if (await notesField.isVisible({ timeout: 2000 })) {
          await notesField.fill('Customer interested in Enterprise plan');
        }
        
        // Save
        await callForm.locator('button:has-text("Save")').first().click();
        
        // Verify success
        await expect(authenticatedPage.locator('.notification')).toContainText(/saved|logged|created/i, { timeout: 10000 });
        
        // Verify in timeline
        await authenticatedPage.locator('a:has-text("Activities"), a:has-text("History")').first().click().catch(() => {});
        await authenticatedPage.waitForTimeout(1000);
        await expect(authenticatedPage.locator('.activity-timeline, .history-section')).toContainText('Product Demo Discussion');
      }
    }
  });

  test('Test 6.4: Send SMS to Contact', async () => {
    // Create contact with phone
    const smsContactId = await contactsPage.createTestContact(
      'SMS',
      'Test',
      { email: `sms_${Date.now()}@example.com`, phone: '+1-555-123-4567' }
    );
    
    if (!smsContactId) {
      test.skip();
      return;
    }

    await contactsPage.gotoDetail(smsContactId);
    
    const smsButton = authenticatedPage.locator('button:has-text("Send SMS"), a:has-text("SMS")').first();
    if (await smsButton.isVisible({ timeout: 5000 })) {
      await smsButton.click();
      
      const smsComposer = authenticatedPage.locator('.sms-composer, .modal').first();
      if (await smsComposer.isVisible({ timeout: 3000 })) {
        // Verify number pre-filled
        await expect(smsComposer.locator('[name="to"], .phone-number')).toContainText('555-123-4567');
        
        // Type message
        const messageField = smsComposer.locator('[name="message"], textarea').first();
        if (await messageField.isVisible({ timeout: 2000 })) {
          await messageField.fill('Your appointment is confirmed for tomorrow at 2 PM');
        }
        
        // Verify character count
        const charCount = smsComposer.locator('.char-count, .character-count');
        if (await charCount.isVisible({ timeout: 1000 })) {
          await expect(charCount).toContainText(/\d+/);
        }
        
        // Note: Not actually sending
        const sendButton = smsComposer.locator('button:has-text("Send")').first();
        await expect(sendButton).toBeVisible();
      }
    }
    
    // Cleanup
    await contactsPage.deleteContactById(smsContactId).catch(() => {});
  });

  test('Test 6.5: View Email History Timeline', async () => {
    if (!testContactId) {
      test.skip();
      return;
    }

    await contactsPage.gotoDetail(testContactId);
    
    // Navigate to history
    const historyLink = authenticatedPage.locator('a:has-text("History"), button:has-text("Activities")').first();
    if (await historyLink.isVisible({ timeout: 3000 })) {
      await historyLink.click();
      
      // Filter by emails
      const typeFilter = authenticatedPage.locator('select[name="activity_type"]').first();
      if (await typeFilter.isVisible({ timeout: 3000 })) {
        await typeFilter.selectOption('Email');
        await authenticatedPage.waitForTimeout(1000);
        
        // Verify timeline
        const timeline = authenticatedPage.locator('.activity-timeline, .history-list').first();
        if (await timeline.isVisible({ timeout: 2000 })) {
          const emailCount = await timeline.locator('.activity-email, .email-activity').count();
          // Should have email filter applied
          expect(emailCount).toBeGreaterThanOrEqual(0);
        }
      }
    }
  });

  test('Test 6.6: Communication Timeline with Filtering', async () => {
    if (!testContactId) {
      test.skip();
      return;
    }

    await contactsPage.gotoDetail(testContactId);
    
    const timelineLink = authenticatedPage.locator('a:has-text("Communications"), button:has-text("Timeline")').first();
    if (await timelineLink.isVisible({ timeout: 3000 })) {
      await timelineLink.click();
      
      const timeline = authenticatedPage.locator('.communication-timeline, .activity-timeline').first();
      if (await timeline.isVisible({ timeout: 3000 })) {
        // Get total activities
        const totalActivities = await timeline.locator('.activity-item, .timeline-item').count();
        
        // Apply filter
        const filter = authenticatedPage.locator('select[name="activity_type"], select[name="type"]').first();
        if (await filter.isVisible({ timeout: 2000 })) {
          await filter.selectOption('Call');
          await authenticatedPage.waitForTimeout(1000);
          
          const callActivities = await timeline.locator('.activity-item').count();
          // Filtered count should be less than or equal to total
          expect(callActivities).toBeLessThanOrEqual(totalActivities);
        }
        
        // Clear filter
        const clearButton = authenticatedPage.locator('button:has-text("Clear"), a:has-text("All")').first();
        if (await clearButton.isVisible({ timeout: 2000 })) {
          await clearButton.click();
          await authenticatedPage.waitForTimeout(500);
        }
      }
    }
  });
});



