/**
 * AI Feed Reporter
 * 
 * Simple Playwright reporter that shows only test success/failure status.
 * Designed for AI/automated analysis of test results.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import type {
  Reporter,
  TestCase,
  TestResult,
  FullResult,
} from '@playwright/test/reporter';

class AIFeedReporter implements Reporter {
  private passed = 0;
  private failed = 0;
  private skipped = 0;

  onTestEnd(test: TestCase, result: TestResult): void {
    const status = result.status;
    const testName = test.title;
    const suiteName = test.parent.title;
    
    if (status === 'passed') {
      this.passed++;
      console.log(`✓ ${suiteName} > ${testName}`);
    } else if (status === 'failed') {
      this.failed++;
      console.log(`✗ ${suiteName} > ${testName}`);
      
      // Show error message - extract only the text content
      if (result.error) {
        let errorMessage = result.error.message || String(result.error);
        
        // Remove ANSI color codes
        errorMessage = errorMessage.replace(/\x1b\[[0-9;]*m/g, '');
        
        // Extract URL from location
        const urlMatch = errorMessage.match(/"url":\s*"([^"]*)"/);
        const pageUrl = urlMatch ? urlMatch[1] : null;
        
        // Extract text field from Received JSON using regex
        // Match: "text": "..." where ... can be multiline
        const textMatch = errorMessage.match(/"text":\s*"((?:[^"\\]|\\.)*)"/s);
        if (textMatch) {
          // Unescape JSON string
          let text = textMatch[1]
            .replace(/\\n/g, '\n')
            .replace(/\\"/g, '"')
            .replace(/\\\\/g, '\\');
          
          // Remove HTML tags
          // Convert <br> to newlines first
          text = text.replace(/<br\s*\/?>/gi, '\n');
          // Remove all other HTML tags
          text = text.replace(/<[^>]+>/g, '');
          // Remove HTML comments
          text = text.replace(/<!--[\s\S]*?-->/g, '');
          // Clean up multiple consecutive newlines
          text = text.replace(/\n{3,}/g, '\n\n');
          // Trim whitespace
          text = text.trim();
          
          console.log('');
          if (pageUrl) {
            console.log(`Page URL: ${pageUrl}`);
            console.log('');
          }
          console.log(text);
          console.log('');
          return;
        }
        
        // Fallback: show first line only
        const cleanMessage = errorMessage.split('\n')[0].trim();
        console.log(`  Error: ${cleanMessage}\n`);
      }
    } else if (status === 'skipped') {
      this.skipped++;
      console.log(`○ ${suiteName} > ${testName} (skipped)`);
    }
  }

  onEnd(result: FullResult): void {
    console.log('\n' + '='.repeat(60));
    console.log(`Test Summary: Passed: ${this.passed} | Failed: ${this.failed} | Skipped: ${this.skipped} | Total: ${this.passed + this.failed + this.skipped} | Status: ${result.status.toUpperCase()}`);
    console.log('='.repeat(60));
  }
}

export default AIFeedReporter;

