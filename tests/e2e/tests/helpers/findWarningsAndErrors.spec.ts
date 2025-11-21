/**
 * findWordsInPage Function Tests
 * 
 * Tests for the findWordsInPage helper function.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../fixtures/auth.fixture';
import { findWordsInPage } from '../../helpers/page-assertions';

test.describe('findWordsInPage', () => {
  test('should test findWordsInPage function', async ({ authenticatedPage }) => {
    // Ustaw mock HTML z prostym contentem
    await authenticatedPage.setContent(`
      <!DOCTYPE html>
      <html>
        <head><title>Test Page</title></head>
        <body>
          <B>Hello world!</B>
          <p>ERROR : This is a warning on the test page.</p>

        </body>
      </html>
    `);
    
    // Testuj funkcję findWordsInPage na mock stronie
    const result = await findWordsInPage(authenticatedPage, ['Warning', 'Error']);
    
    // Tutaj możesz dodać asserty sprawdzające wynik
    console.log('Result:', result);
  });
});

