/**
 * Announcements List View E2E Tests
 * 
 * Tests the list view functionality in the Announcements module.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { AnnouncementsPage } from '../../../pages/AnnouncementsPage';

test.describe('Announcements List View', () => {
  let announcementsPage: AnnouncementsPage;

  test.beforeEach(async ({ authenticatedPage }) => {
    announcementsPage = new AnnouncementsPage(authenticatedPage);
    await announcementsPage.goto();
  });

  test('should display Announcements list view', async ({ authenticatedPage }) => {
    // Verify we're on the Announcements page
    await expect(authenticatedPage).toHaveURL(/module=Announcements/);
    
    // Verify the announcements table is visible
    await expect(announcementsPage.announcementsTable.first()).toBeVisible();
    
    console.log('Announcements list view displayed successfully');
  });

  test('should create new announcement', async ({ authenticatedPage }) => {
    await announcementsPage.waitForListLoad();
    
    // Get initial record count
    const initialCount = await announcementsPage.getRecordCount();
    console.log(`Initial announcements count: ${initialCount}`);
    
    // Create a new announcement
    const testTitle = `Test Announcement ${Date.now()}`;
    
    // Click add announcement button
    const addButton = authenticatedPage.locator('a:has-text("Dodaj rekord"), a:has-text("Add"), [href*="module=Announcements&view=Edit"]').first();
    await addButton.click();
    
    // Wait for edit form to load
    await authenticatedPage.waitForURL(/view=Edit/);
    
    // Fill in announcement title
    await authenticatedPage.locator('input[name="subject"], input[name="title"]').first().fill(testTitle);
    
    // Save the announcement
    await authenticatedPage.locator('button:has-text("Zapisz"), button:has-text("Save"), button.btn-success').first().click();
    
    // Wait for save and redirect
    await authenticatedPage.waitForLoadState('networkidle');
    
    // Go back to list view
    await announcementsPage.goto();
    await announcementsPage.waitForListLoad();
    
    // Verify the new announcement exists
    const hasAnnouncement = await announcementsPage.hasAnnouncement(testTitle);
    expect(hasAnnouncement).toBe(true);
    console.log(`Successfully created announcement: ${testTitle}`);
    
    // Get updated count
    const newCount = await announcementsPage.getRecordCount();
    expect(newCount).toBeGreaterThan(initialCount);
    console.log(`Announcements count increased from ${initialCount} to ${newCount}`);
  });
});

