/**
 * Assets List View E2E Tests
 * 
 * Tests the list view functionality in the Assets module.
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { AssetsPage } from '../../../pages/AssetsPage';

test.describe('Assets List View', () => {
  let assetsPage: AssetsPage;

  test.beforeEach(async ({ authenticatedPage }) => {
    assetsPage = new AssetsPage(authenticatedPage);
    await assetsPage.goto();
  });

  test('should display Assets list view', async ({ authenticatedPage }) => {
    // Verify we're on the Assets page
    await expect(authenticatedPage).toHaveURL(/module=Assets/);
    
    // Verify the assets table is visible
    await expect(assetsPage.assetsTable.first()).toBeVisible();
    
    console.log('Assets list view displayed successfully');
  });

  test('should create new asset', async ({ authenticatedPage }) => {
    await assetsPage.waitForListLoad();
    
    // Get initial record count
    const initialCount = await assetsPage.getRecordCount();
    console.log(`Initial assets count: ${initialCount}`);
    
    // Create a new asset
    const testAssetName = `Test Asset ${Date.now()}`;
    
    // Click add asset button
    const addButton = authenticatedPage.locator('a:has-text("Dodaj rekord"), a:has-text("Add"), [href*="module=Assets&view=Edit"]').first();
    await addButton.click();
    
    // Wait for edit form to load
    await authenticatedPage.waitForURL(/view=Edit/);
    
    // Fill in asset name
    await authenticatedPage.locator('input[name="assetname"]').fill(testAssetName);
    
    // Save the asset
    await authenticatedPage.locator('button:has-text("Zapisz"), button:has-text("Save"), button.btn-success').first().click();
    
    // Wait for save and redirect
    await authenticatedPage.waitForLoadState('networkidle');
    
    // Go back to list view
    await assetsPage.goto();
    await assetsPage.waitForListLoad();
    
    // Verify the new asset exists
    const hasAsset = await assetsPage.hasAsset(testAssetName);
    expect(hasAsset).toBe(true);
    console.log(`Successfully created asset: ${testAssetName}`);
    
    // Get updated count
    const newCount = await assetsPage.getRecordCount();
    expect(newCount).toBeGreaterThan(initialCount);
    console.log(`Assets count increased from ${initialCount} to ${newCount}`);
  });
});

