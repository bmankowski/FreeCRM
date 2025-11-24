/**
 * Performance Tests - Contacts Module
 * 
 * Tests performance and load testing including:
 * - List view load time with 1000+ records
 * - Search performance with large dataset
 * - Sorting performance
 * - Pagination performance
 * - Memory usage
 * - Concurrent user operations
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { ContactsPage } from '../../../pages/ContactsPage';

test.describe('Contacts - Performance', () => {
  let page: Page;
  let authenticatedPage: any;
  let contactsPage: ContactsPage;

  test.beforeEach(async ({ authenticatedPage: authPage }) => {
    authenticatedPage = authPage;
    // Use authenticatedPage directly
    contactsPage = new ContactsPage(authenticatedPage);
    
    // Already authenticated via fixture('/');
    // TODO: Add login steps if needed
  });

  test.afterEach(async () => {
    
  });

  test('Test 15.1: List View Load Time with 1000+ Records', async () => {
    const startTime = Date.now();
    
    await contactsPage.gotoList();
    await authenticatedPage.waitForLoadState('networkidle');
    
    const loadTime = Date.now() - startTime;
    
    // Load time should be reasonable (less than 5 seconds)
    expect(loadTime).toBeLessThan(5000);
    
    // Verify content loaded
    const table = authenticatedPage.locator('table').first();
    await expect(table).toBeVisible();
  });

  test('Test 15.2: Search Performance with Large Dataset', async () => {
    await contactsPage.gotoList();
    
    const startTime = Date.now();
    
    await contactsPage.search('Test');
    await authenticatedPage.waitForTimeout(500);
    
    const searchTime = Date.now() - startTime;
    
    // Search should be fast (less than 2 seconds)
    expect(searchTime).toBeLessThan(2000);
  });

  test('Test 15.3: Sorting Performance', async () => {
    await contactsPage.gotoList();
    
    // Find sortable column header
    const columnHeader = authenticatedPage.locator('th[data-sort], th.sortable').first();
    
    if (await columnHeader.isVisible({ timeout: 3000 })) {
      const startTime = Date.now();
      
      await columnHeader.click();
      await authenticatedPage.waitForLoadState('networkidle');
      
      const sortTime = Date.now() - startTime;
      
      // Sorting should complete within 2 seconds
      expect(sortTime).toBeLessThan(2000);
    }
  });

  test('Test 15.4: Pagination Performance', async () => {
    await contactsPage.gotoList();
    
    // Find next page button
    const nextButton = authenticatedPage.locator('.pagination a:has-text("Next"), .pagination .next').first();
    
    if (await nextButton.isVisible({ timeout: 3000 })) {
      const startTime = Date.now();
      
      await nextButton.click();
      await authenticatedPage.waitForLoadState('networkidle');
      
      const pageLoadTime = Date.now() - startTime;
      
      // Page navigation should be fast (less than 1 second)
      expect(pageLoadTime).toBeLessThan(1000);
    }
  });

  test('Test 15.5: Memory Usage', async () => {
    await contactsPage.gotoList();
    
    const initialMetrics = await authenticatedPage.metrics();
    const initialHeap = initialMetrics.JSHeapUsedSize;
    
    // Navigate through several pages
    for (let i = 0; i < 5; i++) {
      await authenticatedPage.reload();
      await authenticatedPage.waitForTimeout(500);
    }
    
    const finalMetrics = await authenticatedPage.metrics();
    const finalHeap = finalMetrics.JSHeapUsedSize;
    
    // Memory growth should be reasonable (less than 50MB)
    const growth = (finalHeap - initialHeap) / (1024 * 1024);
    expect(growth).toBeLessThan(50);
  });

  test('Test 15.6: Concurrent User Operations', async () => {
    // Simulate concurrent operations
    const promises = [];
    
    for (let i = 0; i < 3; i++) {
      const promise = (async () => {
        const newPage = await authenticatedPage.context().newPage();
        await newPage.goto('/index.php?module=Contacts&view=List');
        await newPage.waitForLoadState('networkidle');
        await newPage.close();
      })();
      
      promises.push(promise);
    }
    
    const startTime = Date.now();
    await Promise.all(promises);
    const concurrentTime = Date.now() - startTime;
    
    // Concurrent operations should complete reasonably
    expect(concurrentTime).toBeLessThan(10000);
  });
});



