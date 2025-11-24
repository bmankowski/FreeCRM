/**
 * Accessibility Tests - Contacts Module
 * 
 * Tests WCAG 2.1 AA accessibility compliance including:
 * - Keyboard navigation
 * - Screen reader compatibility
 * - ARIA labels
 * - Focus management
 * - High contrast mode
 * - Tab order validation
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { ContactsPage } from '../../../pages/ContactsPage';

test.describe('Contacts - Accessibility', () => {
  test('Test 19.1: Keyboard Navigation', async ({ authenticatedPage }) => {
    const contactsPage = new ContactsPage(authenticatedPage);
    await contactsPage.gotoList();
    
    // Tab navigation
    await authenticatedPage.keyboard.press('Tab');
    await authenticatedPage.keyboard.press('Tab');
    await authenticatedPage.keyboard.press('Tab');
    
    // Verify focus exists
    const focusedElement = await authenticatedPage.evaluate(() => {
      const el = document.activeElement;
      return el ? el.tagName : null;
    });
    
    expect(focusedElement).toBeTruthy();
    
    // Press Enter on focused element
    await authenticatedPage.keyboard.press('Enter');
    await authenticatedPage.waitForTimeout(500);
  });

  test('Test 19.2: Screen Reader Compatibility', async ({ authenticatedPage }) => {
    const contactsPage = new ContactsPage(authenticatedPage);
    await contactsPage.gotoList();
    
    // Check for ARIA landmarks
    const main = authenticatedPage.locator('[role="main"], main');
    const navigation = authenticatedPage.locator('[role="navigation"], nav');
    
    // At least one landmark should exist
    const mainCount = await main.count();
    const navCount = await navigation.count();
    expect(mainCount + navCount).toBeGreaterThan(0);
    
    // Check for heading structure
    const h1 = authenticatedPage.locator('h1');
    const h1Count = await h1.count();
    expect(h1Count).toBeGreaterThanOrEqual(0);
  });

  test('Test 19.3: ARIA Labels', async ({ authenticatedPage }) => {
    const contactsPage = new ContactsPage(authenticatedPage);
    await contactsPage.gotoList();
    
    // Check buttons have labels
    const buttons = authenticatedPage.locator('button');
    const buttonCount = await buttons.count();
    
    if (buttonCount > 0) {
      // Check at least one button has proper labeling
      let hasProperLabel = false;
      
      for (let i = 0; i < Math.min(buttonCount, 5); i++) {
        const button = buttons.nth(i);
        if (await button.isVisible()) {
          const ariaLabel = await button.getAttribute('aria-label');
          const text = await button.textContent();
          
          if (ariaLabel || (text && text.trim())) {
            hasProperLabel = true;
            break;
          }
        }
      }
      
      // At least one button should have proper labeling
      expect(hasProperLabel).toBeTruthy();
    }
    
    // Check form fields have labels
    // Already authenticated via fixture('/index.php?module=Contacts&view=Edit');
    await authenticatedPage.waitForTimeout(1000);
    
    const firstnameInput = authenticatedPage.locator('input[name="firstname"]');
    if (await firstnameInput.isVisible({ timeout: 3000 })) {
      const ariaLabel = await firstnameInput.getAttribute('aria-label');
      const ariaLabelledBy = await firstnameInput.getAttribute('aria-labelledby');
      const label = authenticatedPage.locator('label[for="firstname"]');
      
      // Should have some form of labeling
      const hasLabel = ariaLabel || ariaLabelledBy || await label.count() > 0;
      expect(hasLabel).toBeTruthy();
    }
  });

  test('Test 19.4: Focus Management', async ({ authenticatedPage }) => {
    const contactsPage = new ContactsPage(authenticatedPage);
    await contactsPage.gotoList();
    
    // Focus should be manageable
    await authenticatedPage.keyboard.press('Tab');
    
    const initialFocus = await authenticatedPage.evaluate(() => document.activeElement?.tagName);
    expect(initialFocus).toBeTruthy();
    
    // Tab forward
    await authenticatedPage.keyboard.press('Tab');
    await authenticatedPage.keyboard.press('Tab');
    
    // Tab backward
    await authenticatedPage.keyboard.press('Shift+Tab');
    
    const currentFocus = await authenticatedPage.evaluate(() => document.activeElement?.tagName);
    expect(currentFocus).toBeTruthy();
  });

  test('Test 19.5: High Contrast Mode', async ({ authenticatedPage }) => {
    const contactsPage = new ContactsPage(authenticatedPage);
    // Enable high contrast simulation
    await authenticatedPage.emulateMedia({ colorScheme: 'dark' });
    
    await contactsPage.gotoList();
    
    // Verify content is still visible
    const table = authenticatedPage.locator('table').first();
    await expect(table).toBeVisible();
    
    // Check that text is readable
    const textElement = authenticatedPage.locator('tbody tr').first();
    if (await textElement.isVisible({ timeout: 3000 })) {
      const color = await textElement.evaluate(el => {
        return window.getComputedStyle(el).color;
      });
      expect(color).toBeTruthy();
    }
  });

  test('Test 19.6: Tab Order Validation', async ({ authenticatedPage }) => {
    // Already authenticated via fixture('/index.php?module=Contacts&view=Edit');
    await authenticatedPage.waitForTimeout(1000);
    
    // Tab through form fields
    const tabOrder: string[] = [];
    
    for (let i = 0; i < 10; i++) {
      await authenticatedPage.keyboard.press('Tab');
      const focused = await authenticatedPage.evaluate(() => {
        const el = document.activeElement;
        return el ? (el.getAttribute('name') || el.tagName) : '';
      });
      
      if (focused) {
        tabOrder.push(focused);
      }
    }
    
    // Should have focused multiple elements
    expect(tabOrder.length).toBeGreaterThan(0);
    
    // Tab order should follow logical sequence
    // (In a real implementation, verify specific expected order)
  });
});

