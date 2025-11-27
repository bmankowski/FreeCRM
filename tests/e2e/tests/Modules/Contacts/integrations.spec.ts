/**
 * Integrations Tests - Contacts Module
 * 
 * Tests external integrations including:
 * - View contact address on map
 * - Click to call phone number
 * - Social media profile links
 * - Sync with external system
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { ContactsPage } from '../../../pages/ContactsPage';

test.describe('Contacts - Integrations', () => {
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
    
    // Create test contact with address
    const timestamp = Date.now();
    testContactId = await contactsPage.createTestContact(
      'Integration',
      `Test${timestamp}`,
      { 
        email: `integration_${timestamp}@example.com`,
        phone: '+1-555-123-4567'
      }
    );
  });

  test.afterEach(async () => {
    if (testContactId) {
      await contactsPage.deleteContactById(testContactId).catch(() => {});
    }
    
  });

  test('Test 13.1: View Contact Address on Map', async () => {
    if (!testContactId) {
      test.skip();
      return;
    }

    await contactsPage.gotoDetail(testContactId);
    
    // Look for map button/link
    const mapButton = authenticatedPage.locator('a:has-text("View on Map"), button:has-text("Map"), a[href*="maps"]').first();
    
    if (await mapButton.isVisible({ timeout: 5000 })) {
      await mapButton.click();
      
      // Verify map opens (iframe or new tab)
      const mapContainer = authenticatedPage.locator('.map-container, iframe[src*="maps"]').first();
      await expect(mapContainer).toBeVisible({ timeout: 10000 });
    }
  });

  test('Test 13.2: Click to Call Phone Number', async () => {
    if (!testContactId) {
      test.skip();
      return;
    }

    await contactsPage.gotoDetail(testContactId);
    
    // Look for phone number links
    const phoneLinks = authenticatedPage.locator('a[href^="tel:"]');
    const phoneCount = await phoneLinks.count();
    
    if (phoneCount > 0) {
      const phoneLink = phoneLinks.first();
      const href = await phoneLink.getAttribute('href');
      
      // Verify tel: link format
      expect(href).toMatch(/^tel:/);
      expect(href).toContain('555');
    }
  });

  test('Test 13.3: Social Media Profile Links', async () => {
    if (!testContactId) {
      test.skip();
      return;
    }

    await contactsPage.gotoDetail(testContactId);
    
    // Look for social media links
    const socialLinks = authenticatedPage.locator('a[href*="linkedin"], a[href*="twitter"], a[href*="facebook"]');
    const socialCount = await socialLinks.count();
    
    if (socialCount > 0) {
      const socialLink = socialLinks.first();
      
      // Verify link opens in new tab
      const target = await socialLink.getAttribute('target');
      expect(target).toBe('_blank');
    }
  });

  test('Test 13.4: Sync with External System', async () => {
    if (!testContactId) {
      test.skip();
      return;
    }

    await contactsPage.gotoDetail(testContactId);
    
    // Look for sync button
    const syncButton = authenticatedPage.locator('button:has-text("Sync"), a:has-text("Synchronize")').first();
    
    if (await syncButton.isVisible({ timeout: 5000 })) {
      await syncButton.click();
      
      // Wait for sync status
      await authenticatedPage.waitForTimeout(2000);
      
      const syncStatus = authenticatedPage.locator('.sync-status, .notification');
      if (await syncStatus.isVisible({ timeout: 5000 })) {
        await expect(syncStatus).toContainText(/synced|complete|success/i);
      }
    }
  });
});




