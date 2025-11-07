/**
 * Calendar Page Object Model
 * 
 * Provides methods for interacting with the Calendar module.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { Page, Locator, expect } from '@playwright/test';

export class CalendarPage {
  readonly page: Page;
  readonly calendarContainer: Locator;
  readonly addEventButton: Locator;

  constructor(page: Page) {
    this.page = page;
    
    // Common selectors for Calendar module
    this.calendarContainer = page.locator('.fc, .calendar-container, [data-test="calendar"]');
    this.addEventButton = page.locator('button:has-text("Add"), .addButton, [data-test="add-event"]');
  }

  /**
   * Navigate to Calendar view
   */
  async goto() {
    await this.page.goto('/index.php?module=Calendar&view=Calendar&mid=46&parent=44');
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Wait for the calendar to finish loading
   */
  async waitForCalendarLoad() {
    await this.page.waitForSelector('.loading, .calendarViewLoadingImageBlock', { 
      state: 'hidden', 
      timeout: 10000 
    }).catch(() => {
      // If no loading indicator exists, that's fine
    });
    
    await this.page.waitForLoadState('networkidle');
    // Additional wait for calendar rendering
    await this.page.waitForTimeout(1000);
  }

  /**
   * Check if calendar view is properly displayed
   * @returns true if calendar elements are visible
   */
  async hasCalendarView(): Promise<boolean> {
    // Check for common calendar UI elements
    const calendarExists = await this.page.locator('.fc, .calendar, .calendarview').count() > 0;
    return calendarExists;
  }

  /**
   * Check if navigation controls are visible
   * @returns true if navigation buttons exist
   */
  async hasNavigationControls(): Promise<boolean> {
    const navButtons = await this.page.locator('button:has-text("Today"), button:has-text("Next"), button:has-text("Previous"), .fc-button, .calendar-nav').count();
    return navButtons > 0;
  }

  /**
   * Get the current view mode (if determinable)
   * @returns The view mode or 'unknown'
   */
  async getCurrentViewMode(): Promise<string> {
    // Try to detect if we're in day/week/month view
    const viewIndicators = [
      { selector: '.fc-timeGridDay-view, .fc-dayGridMonth-view, .fc-timeGridWeek-view', name: 'fullcalendar' },
      { selector: '.calendarview', name: 'calendar' },
    ];
    
    for (const indicator of viewIndicators) {
      if (await this.page.locator(indicator.selector).count() > 0) {
        return indicator.name;
      }
    }
    
    return 'unknown';
  }
}

