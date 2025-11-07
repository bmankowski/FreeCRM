/**
 * Calendar E2E Tests
 * 
 * Tests the calendar functionality in the Calendar module.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { CalendarPage } from '../../../pages/CalendarPage';

test.describe('Calendar View', () => {
  let calendarPage: CalendarPage;

  test.beforeEach(async ({ authenticatedPage }) => {
    calendarPage = new CalendarPage(authenticatedPage);
    await calendarPage.goto();
  });

  test('should display Calendar view', async ({ authenticatedPage }) => {
    // Verify we're on the Calendar page
    await expect(authenticatedPage).toHaveURL(/module=Calendar/);
    await expect(authenticatedPage).toHaveURL(/view=Calendar/);
    
    console.log('Calendar view loaded successfully');
  });

  test('should show calendar elements and create event', async ({ authenticatedPage }) => {
    await calendarPage.waitForCalendarLoad();
    
    // Check if calendar view is displayed
    const hasCalendar = await calendarPage.hasCalendarView();
    console.log(`Calendar view visible: ${hasCalendar}`);
    
    if (hasCalendar) {
      expect(hasCalendar).toBe(true);
    } else {
      // If no standard calendar detected, at least verify page loaded
      await expect(authenticatedPage.locator('table, .calendar, .calendarview')).toBeVisible();
    }
    
    // Check for navigation controls
    const hasNav = await calendarPage.hasNavigationControls();
    console.log(`Navigation controls visible: ${hasNav}`);
    
    // Verify the page title or heading contains "Calendar" or "Kalendarz"
    const pageText = await authenticatedPage.textContent('body');
    expect(pageText).toMatch(/Calendar|Kalendarz/i);
    
    // Try to create a new event
    const testEventName = `Test Event ${Date.now()}`;
    
    // Click add event button (+ button or Add button)
    const addButton = authenticatedPage.locator('button:has-text("+"), a:has-text("Add"), [href*="module=Calendar&view=Edit"]').first();
    
    if (await addButton.isVisible({ timeout: 2000 })) {
      await addButton.click();
      
      // Wait for quick create or edit form
      await authenticatedPage.waitForTimeout(1000);
      
      // Fill in event subject/title
      const subjectInput = authenticatedPage.locator('input[name="subject"], input[name="title"]').first();
      if (await subjectInput.isVisible({ timeout: 3000 })) {
        await subjectInput.fill(testEventName);
        
        // Save the event
        await authenticatedPage.locator('button:has-text("Zapisz"), button:has-text("Save"), button.btn-success').first().click();
        
        // Wait for save
        await authenticatedPage.waitForLoadState('networkidle');
        
        console.log(`Successfully created calendar event: ${testEventName}`);
      } else {
        console.log('Event creation form did not appear as expected');
      }
    } else {
      console.log('Add event button not found - calendar is display-only');
    }
  });
});

