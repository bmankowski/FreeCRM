/**
 * Page Assertion Helpers
 * 
 * Reusable helper functions for common page assertions in E2E tests.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { Page, expect } from '@playwright/test';

/**
 * Location information for found text
 */
interface TextLocation {
  text: string;
  tag: string;
  className: string;
  id: string;
  url?: string;
}

/**
 * Check if page contains specific words anywhere in the page content (including hidden elements)
 * 
 * @param page - Playwright Page object
 * @param words - Array of words to search for (case-insensitive)
 * @returns Object with found words and their locations, or null if none found
 */
export async function findWordsInPage(
  page: Page,
  words: string[]
): Promise<{ word: string; location: TextLocation | null }[] | null> {
  const pageContent = await page.evaluate(() => {
    // Code runs in browser context, so DOM APIs are available
    const visibleText = document.body.innerText || '';
    const textContent = document.body.textContent || '';
    const htmlSource = document.body.innerHTML || '';
    const allText = (textContent + visibleText + htmlSource);
    return allText;
  });

  const foundWords: { word: string; location: TextLocation | null }[] = [];

  for (const word of words) {
    if (pageContent.includes(word)) {
      // Find where the word appears for debugging
      const location = await page.evaluate((searchWord: string) => {
        // Capture URL at the moment we find the error
        // Try multiple methods to get the full URL with query parameters
        const currentUrl = document.URL || window.location.href || 
          (window.location.protocol + '//' + window.location.host + window.location.pathname + window.location.search + window.location.hash);
        
        const walker = document.createTreeWalker(
          document.body,
          NodeFilter.SHOW_TEXT,
          null
        );
        let node;
        while (node = walker.nextNode()) {
          if (node.textContent && node.textContent.includes(searchWord)) {
            const parent = node.parentElement;
            if (!parent) {
              continue;
            }
            
            // Get the parent of the element containing the word
            // If the word is in <b>, get the parent of <b> (e.g., <div>)
            let container: HTMLElement = parent as HTMLElement;
            
            // If parent is <b> or similar inline element, go up one more level to get the div
            if (parent.parentElement && ['B', 'STRONG', 'SPAN', 'I', 'EM'].includes(parent.tagName)) {
              container = parent.parentElement as HTMLElement;
            }
            
            // Get the full HTML content of the container
            const fullText = (container.innerHTML || container.innerText || container.textContent || '').trim();
            
            return {
              text: fullText.trim(),
              tag: parent.tagName || 'unknown',
              className: parent.className || 'unknown',
              id: parent.id || 'unknown',
              url: currentUrl
            };
          }
        }
        return null;
      }, word);

      foundWords.push({ word, location });
    }
  }

  return foundWords.length > 0 ? foundWords : null;
}


/**
 * Format found warnings/errors into a readable message
 * 
 * @param found - Result from findWordsInPage (with pageUrl attached)
 * @param browserUrl - URL from browser (window.location.href)
 * @param playwrightUrl - URL from Playwright (page.url())
 * @returns Formatted error message string
 */
export function formatWarningsAndErrors(
  found: { word: string; location: TextLocation | null; pageUrl?: string }[] | null,
  browserUrl?: string,
  playwrightUrl?: string
): string {
  if (!found || found.length === 0) {
    return '';
  }

  // Use URL from first error if available, otherwise use browser URL
  const errorPageUrl = found[0]?.pageUrl || browserUrl || 'unknown';

  const messages = found.map(({ word, location }, index) => {
    let message = `\n[${index + 1}] Found "${word.toUpperCase()}"`;
    
    if (location) {
      // Build detailed selector
      const selectorParts: string[] = [];
      if (location.tag && location.tag !== 'unknown') {
        selectorParts.push(location.tag.toLowerCase());
      }
      if (location.id && location.id !== 'unknown') {
        selectorParts.push(`#${location.id}`);
      }
      if (location.className && location.className !== 'unknown') {
        const classes = location.className.split(' ').filter(c => c.trim());
        if (classes.length > 0) {
          selectorParts.push(`.${classes.join('.')}`);
        }
      }
      const selector = selectorParts.length > 0 ? selectorParts.join('') : 'unknown element';
      
      message += `\n  Location: <${location.tag}>${location.className && location.className !== 'unknown' ? `.${location.className.split(' ').join('.')}` : ''}${location.id && location.id !== 'unknown' ? `#${location.id}` : ''}`;
      message += `\n  CSS Selector: ${selector}`;
      message += `\n  Error Text:\n    ${location.text.split('\n').join('\n    ')}`;
    } else {
      message += `\n  Location: Could not determine element location`;
    }
    
    return message;
  });

  let result = `\nPage URL (from browser): ${errorPageUrl}`;
  if (playwrightUrl && playwrightUrl !== errorPageUrl) {
    result += `\nPage URL (from Playwright): ${playwrightUrl}`;
    
  }

  return result;
}

/**
 * Assert that page does not contain warning or error messages
 * 
 * Searches for 'Warning' and 'Error' text on the page and fails the test if found.
 * It provides better error messages and is more readable in tests.
 * 
 * @param page - Playwright Page object
 * @example
 * ```typescript
 * await expectNoWarningsAndErrors(authenticatedPage);
 * ```
 */
export async function expectNoWarningsAndErrors(page: Page): Promise<void> {
  const found = await findWordsInPage(page, ['Warning', 'Error']);
  
  if (found) {
    // Use URL from first error's location (captured in browser context when error was found)
    const errorPageUrl = found[0]?.location?.url;
    const fallbackUrl = errorPageUrl || await page.evaluate(() => window.location.href);
    const playwrightUrl = page.url();
    const errorMessage = formatWarningsAndErrors(found, fallbackUrl, playwrightUrl);
    console.error(errorMessage);
    expect(found, errorMessage).toBeNull();
  }
}

