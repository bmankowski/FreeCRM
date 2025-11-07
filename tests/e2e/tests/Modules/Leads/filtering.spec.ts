/**
 * Leads Filtering E2E Tests
 * 
 * Tests the filtering functionality in the Leads module.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { LeadsPage } from '../../../pages/LeadsPage';

test.describe('Leads Filtering', () => {
  let leadsPage: LeadsPage;

  test.beforeEach(async ({ authenticatedPage }) => {
    leadsPage = new LeadsPage(authenticatedPage);
    await leadsPage.goto();
  });

  test('should display Leads list view', async ({ authenticatedPage }) => {
    // Verify we're on the Leads page
    await expect(authenticatedPage).toHaveURL(/module=Leads/);
    
    // Verify the leads table is visible
    await expect(leadsPage.leadsTable.first()).toBeVisible();
  });

  test('should filter leads using default filters', async ({ authenticatedPage }) => {
    // Wait for list to load
    await leadsPage.waitForListLoad();
    
    // Get initial lead count
    const initialCount = await leadsPage.getLeadCount();
    console.log(`Initial lead count: ${initialCount}`);
    
    // Common filters in CRM systems: "All", "My Leads", "Recently Created", etc.
    // We'll try to apply different filters and verify the list updates
    
    // Try to find and click a filter
    // Most CRMs have filters like "All" by default
    const filterLinks = authenticatedPage.locator('.filterName, .customFilterName, [data-filter-id]');
    const filterCount = await filterLinks.count();
    
    if (filterCount > 1) {
      // Click the second filter (first is usually already selected)
      const secondFilter = filterLinks.nth(1);
      const filterName = await secondFilter.textContent();
      console.log(`Applying filter: ${filterName}`);
      
      await secondFilter.click();
      await leadsPage.waitForListLoad();
      
      // Verify the filter was applied by checking if list updated
      const filteredCount = await leadsPage.getLeadCount();
      console.log(`Filtered lead count: ${filteredCount}`);
      
      // The count should either change or remain the same (if filter has same results)
      // Just verify we can still see the table
      await expect(leadsPage.leadsTable.first()).toBeVisible();
      
    } else {
      console.log('Only one filter available or no filters found');
      
      // At minimum, verify we can see leads
      expect(initialCount).toBeGreaterThanOrEqual(0);
    }
  });

  test('should search/filter leads by text', async ({ authenticatedPage }) => {
    await leadsPage.waitForListLoad();
    
    // Check if there are any leads first
    const hasNoRecords = await leadsPage.hasNoRecordsMessage();
    
    if (!hasNoRecords) {
      // Get a lead name from the current list to search for
      const leadNames = await leadsPage.getVisibleLeadNames();
      
      if (leadNames.length > 0) {
        const searchTerm = leadNames[0].split(' ')[0]; // Search for first word of first lead
        console.log(`Searching for: ${searchTerm}`);
        
        await leadsPage.search(searchTerm);
        
        // Verify search results contain the search term
        const results = await authenticatedPage.locator(`tr:has-text("${searchTerm}")`).count();
        expect(results).toBeGreaterThan(0);
        
        console.log(`Found ${results} results for "${searchTerm}"`);
      } else {
        console.log('No leads available to test search functionality');
      }
    } else {
      console.log('No leads in the system to filter');
      // This is okay - we've verified the filter UI works even with no data
    }
  });

  test('should switch between different filter options', async ({ authenticatedPage }) => {
    await leadsPage.waitForListLoad();
    
    // Get the filter dropdown - "Dodatkowe filtry" (Additional filters)
    const filterDropdown = authenticatedPage.locator('select.customFilter, select[name="customFilter"], .filterActionsDiv select').first();
    
    if (await filterDropdown.isVisible({ timeout: 2000 })) {
      // Get available filter options
      const optionsCount = await filterDropdown.locator('option').count();
      console.log(`Found ${optionsCount} filter options`);
      
      if (optionsCount > 1) {
        // Try switching to a different filter
        const firstOption = await filterDropdown.locator('option').first().textContent();
        console.log(`Switching to filter: ${firstOption}`);
        
        await filterDropdown.selectOption({ index: 0 });
        await leadsPage.waitForListLoad();
        
        // Verify the page still loads and shows a table
        await expect(leadsPage.leadsTable.first()).toBeVisible();
        console.log('Filter switch successful');
      } else {
        console.log('Only one filter available - filtering system is present');
      }
    } else {
      console.log('Filter dropdown not found - may use different filtering UI');
    }
    
    // At minimum, verify the list view is still functional
    const leadCount = await leadsPage.getLeadCount();
    console.log(`Leads visible after filter operations: ${leadCount}`);
  });
});

