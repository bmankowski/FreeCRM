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
            return {
              text: node.textContent.substring(0, 150).trim(),
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
 * Assert that page does not contain warning or error messages anywhere
 * 
 * @param page - Playwright Page object
 * @throws Error if warning or error text is found, with location information
 */
export async function assertNoWarningsOrErrors(page: Page): Promise<void> {
  const found = await findWordsInPage(page, ['warning', 'error']);

  if (found) {
    const messages = found.map(({ word, location }) => {
      if (location) {
        return `"${word}" found in <${location.tag}>${location.className ? `.${location.className.split(' ').join('.')}` : ''}${location.id ? `#${location.id}` : ''}: "${location.text}"`;
      }
      return `"${word}" found but location could not be determined`;
    });

    throw new Error(
      `Page contains warning or error text:\n${messages.join('\n')}`
    );
  }
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
        return `"${word}" found in <${location.tag}>${location.className ? `.${location.className.split(' ').join('.')}` : ''}${location.id ? `#${location.id}` : ''}: "${location.text}"`;
      }
      return `"${word}" found but location could not be determined`;
    });

    throw new Error(
      `Page contains forbidden words:\n${messages.join('\n')}`
    );
  }
}

