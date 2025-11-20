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
    const allText = document.body.innerText || document.body.textContent || '';
    return allText.toLowerCase();
  });

  const foundWords: { word: string; location: TextLocation | null }[] = [];

  for (const word of words) {
    const lowerWord = word.toLowerCase();
    if (pageContent.includes(lowerWord)) {
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
          if (node.textContent && node.textContent.toLowerCase().includes(searchWord)) {
            const parent = node.parentElement;
            if (!parent) {
              continue;
            }
            
            // Try to get full error message - it might span multiple elements
            // Start with parent, but also check if parent is too small
            let container: HTMLElement = parent as HTMLElement;
            let fullText = (container.innerText || container.textContent || '').trim();
            
            // If parent is small (like <B> or <strong>), try to get more context
            // Check parent's parent or siblings that might contain continuation
            if (fullText.length < 100 && container.parentElement) {
              // Check if siblings contain more of the error message
              const siblings = Array.from(container.parentElement.children);
              const currentIndex = siblings.indexOf(container);
              
              // Collect text from previous and next siblings if they're small
              let extendedText = fullText;
              for (let i = Math.max(0, currentIndex - 2); i < Math.min(siblings.length, currentIndex + 3); i++) {
                const sibling = siblings[i] as HTMLElement;
                if (sibling && sibling !== container) {
                  const siblingText = (sibling.innerText || sibling.textContent || '').trim();
                  // Only include if sibling is small (likely part of error message)
                  if (siblingText.length < 200) {
                    extendedText += ' ' + siblingText;
                  }
                }
              }
              
              // Use extended text if it's longer
              if (extendedText.length > fullText.length && extendedText.length < 2000) {
                fullText = extendedText.trim();
              }
              
              // Also try parent's parent if current container is small
              if (fullText.length < 100 && container.parentElement) {
                const grandParent = container.parentElement as HTMLElement;
                const grandParentText = (grandParent.innerText || grandParent.textContent || '').trim();
                if (grandParentText.length > fullText.length && grandParentText.length < 2000) {
                  container = grandParent;
                  fullText = grandParentText;
                }
              }
            }
            
            // Use innerText for better formatting (preserves line breaks)
            const containerText = (container.innerText || container.textContent || '').trim();
            if (containerText.length > fullText.length && containerText.length < 2000) {
              fullText = containerText;
            }
            
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
      }, lowerWord);

      foundWords.push({ word, location });
    }
  }

  return foundWords.length > 0 ? foundWords : null;
}

/**
 * Find warning or error messages on the page
 * 
 * @param page - Playwright Page object
 * @returns Array of found warnings/errors with location information, or null if none found
 */
export async function findWarningsAndErrors(
  page: Page
): Promise<{ word: string; location: TextLocation | null }[] | null> {
  const found = await findWordsInPage(page, ['warning', 'error']);
  
  if (found) {
    // Use URL from first error's location (captured in browser context when error was found)
    // This is the most accurate URL as it's captured at the exact moment the error is detected
    const errorPageUrl = found[0]?.location?.url;
    // Fallback to current page URL if not available
    const fallbackUrl = errorPageUrl || await page.evaluate(() => window.location.href);
    const playwrightUrl = page.url();
    const message = formatWarningsAndErrors(found, fallbackUrl, playwrightUrl);
    console.error(message);
    return found;
  }
  
  return null;
}

/**
 * Format found warnings/errors into a readable message
 * 
 * @param found - Result from findWarningsAndErrors (with pageUrl attached)
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
  const timestamp = new Date().toISOString();

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

  let result = `═══════════════════════════════════════════════════════════`;
  result += `\n⚠️  PAGE CONTAINS WARNING OR ERROR TEXT`;
  result += `\n═══════════════════════════════════════════════════════════`;
  result += `\n\nPage URL (from browser): ${errorPageUrl}`;
  if (playwrightUrl && playwrightUrl !== errorPageUrl) {
    result += `\nPage URL (from Playwright): ${playwrightUrl}`;
  }
  result += `\nTimestamp: ${timestamp}`;
  result += `\nTotal issues found: ${found.length}`;
  result += `\n\n${messages.join('\n')}`;
  result += `\n\n═══════════════════════════════════════════════════════════\n`;

  return result;
}

/**
 * Assert that page does not contain specific words anywhere
 * 
 * @param page - Playwright Page object
 * @param words - Array of words to check for (case-insensitive)
 * @throws Error if any of the words is found, with location information
 */
export async function assertPageDoesNotContain(page: Page, words: string[]): Promise<void> {
  const found = await findWordsInPage(page, words);

  if (found) {
    const messages = found.map(({ word, location }) => {
      if (location) {
        const selector = `<${location.tag}>${location.className ? `.${location.className.split(' ').join('.')}` : ''}${location.id ? `#${location.id}` : ''}`;
        return `"${word}" found in ${selector}:\n${location.text}`;
      }
      return `"${word}" found but location could not be determined`;
    });

    throw new Error(
      `Page contains forbidden words:\n\n${messages.join('\n\n')}`
    );
  }
}

/**
 * Assert that page does not contain warning or error messages
 * 
 * This is a convenience wrapper around findWarningsAndErrors with expect.
 * It provides better error messages and is more readable in tests.
 * 
 * @param page - Playwright Page object
 * @example
 * ```typescript
 * await expectNoWarningsAndErrors(authenticatedPage);
 * ```
 */
export async function expectNoWarningsAndErrors(page: Page): Promise<void> {
  const found = await findWarningsAndErrors(page);
  const errorMessage = found 
    ? formatWarningsAndErrors(found, await page.evaluate(() => window.location.href), page.url())
    : 'No warnings or errors expected';
  expect(found, errorMessage).toBeNull();
}

