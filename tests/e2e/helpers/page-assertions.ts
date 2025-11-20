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

import { Page } from '@playwright/test';

/**
 * Location information for found text
 */
interface TextLocation {
  text: string;
  tag: string;
  className: string;
  id: string;
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
        const walker = document.createTreeWalker(
          document.body,
          NodeFilter.SHOW_TEXT,
          null
        );
        let node;
        while (node = walker.nextNode()) {
          if (node.textContent && node.textContent.toLowerCase().includes(searchWord)) {
            const parent = node.parentElement;
            // Get full text of the parent element (not just the text node)
            // This captures complete error messages that might span multiple text nodes
            let fullText = node.textContent;
            if (parent) {
              // Try to get full text of parent element for complete error message
              const parentText = parent.textContent || parent.innerText || '';
              // Use parent text if it's not too long (to avoid huge blocks), but longer than node text
              if (parentText.length > fullText.length && parentText.length < 2000) {
                fullText = parentText;
              }
            }
            return {
              text: fullText.trim(),
              tag: parent ? parent.tagName : 'unknown',
              className: parent ? parent.className : 'unknown',
              id: parent ? parent.id : 'unknown'
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
    const message = formatWarningsAndErrors(found);
    console.error(message);
  }
  
  return found;
}

/**
 * Format found warnings/errors into a readable message
 * 
 * @param found - Result from findWarningsAndErrors
 * @returns Formatted error message string
 */
export function formatWarningsAndErrors(
  found: { word: string; location: TextLocation | null }[] | null
): string {
  if (!found) {
    return '';
  }

  const messages = found.map(({ word, location }) => {
    if (location) {
      const selector = `<${location.tag}>${location.className ? `.${location.className.split(' ').join('.')}` : ''}${location.id ? `#${location.id}` : ''}`;
      return `"${word}" found in ${selector}:\n${location.text}`;
    }
    return `"${word}" found but location could not be determined`;
  });

  return `Page contains warning or error text:\n\n${messages.join('\n\n')}`;
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

