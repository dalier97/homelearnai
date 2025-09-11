import { test, expect } from '@playwright/test';
import { KidsModeHelper } from './helpers/kids-mode-helpers';

/**
 * Debug Child ID Detection
 * 
 * Simple test to debug child ID detection logic
 */

test.describe('Debug Child ID Detection', () => {
  let kidsModeHelper: KidsModeHelper;
  
  // Increase timeout
  test.describe.configure({ timeout: 60000 });

  test.beforeEach(async ({ page }) => {
    kidsModeHelper = new KidsModeHelper(page);
    
    // Create fresh test user for each test
    await kidsModeHelper.createTestUserWithKidsMode();
  });

  test.afterEach(async ({ page }) => {
    // Clean up kids mode state
    await kidsModeHelper.resetKidsMode();
  });

  test('Debug Child ID Detection Flow', async ({ page }) => {
    console.log('=== DEBUG: Starting child ID detection test ===');
    
    // Go to children page
    await page.goto('/children');
    await page.waitForTimeout(2000);
    
    // Look for existing children first
    const existingScheduleLinks = page.locator('a[href*="/children/"]:has-text("View Schedule")');
    const existingCount = await existingScheduleLinks.count();
    console.log(`DEBUG: Found ${existingCount} existing children`);
    
    if (existingCount > 0) {
      for (let i = 0; i < existingCount; i++) {
        const link = existingScheduleLinks.nth(i);
        const href = await link.getAttribute('href');
        console.log(`DEBUG: Existing child ${i}: href=${href}`);
      }
    }
    
    // Create additional child
    const addChildBtn = page.locator('[data-testid="header-add-child-btn"]');
    if (await addChildBtn.count() > 0) {
      console.log('DEBUG: Creating additional child...');
      await addChildBtn.click();
      
      // Wait for Alpine.js modal to be visible
      await page.waitForSelector('#child-form-modal [data-testid="modal-content"]', { timeout: 10000 });
      await page.waitForTimeout(1000);
      const nameInput = page.locator('input[name="name"]');
      if (await nameInput.isVisible()) {
        await nameInput.fill('Debug Test Child');
        const ageSelect = page.locator('select[name="age"]');
        if (await ageSelect.isVisible()) {
          await ageSelect.selectOption('9');
        }
        const submitBtn = page.locator('button[type="submit"]:has-text("Add")');
        if (await submitBtn.isVisible()) {
          await submitBtn.click();
          await page.waitForTimeout(3000);
        }
      }
    }
    
    // Check children after creation - try refreshing the page
    console.log('DEBUG: Refreshing page to see if View Schedule links appear...');
    await page.reload();
    await page.waitForTimeout(3000);
    
    const newScheduleLinks = page.locator('a[href*="/children/"]:has-text("View Schedule")');
    const newCount = await newScheduleLinks.count();
    console.log(`DEBUG: Found ${newCount} children after page refresh`);
    
    for (let i = 0; i < newCount; i++) {
      const link = newScheduleLinks.nth(i);
      const href = await link.getAttribute('href');
      console.log(`DEBUG: Child ${i}: href=${href}`);
    }
    
    // Also check what child headers exist
    const allChildHeaders = page.locator('h3');
    const headerCount = await allChildHeaders.count();
    console.log(`DEBUG: Found ${headerCount} h3 headers on page:`);
    for (let i = 0; i < Math.min(headerCount, 5); i++) {
      const headerText = await allChildHeaders.nth(i).textContent();
      console.log(`DEBUG: Header ${i}: "${headerText}"`);
    }
    
    // Try to find "Debug Test Child" specifically
    const debugChildHeader = page.locator('h3:has-text("Debug Test Child")');
    const debugChildCount = await debugChildHeader.count();
    console.log(`DEBUG: Found ${debugChildCount} instances of "Debug Test Child" header`);
    
    if (debugChildCount > 0) {
      // Find the View Schedule link in the same child card
      const childCard = debugChildHeader.first().locator('xpath=ancestor::*[contains(@class, "child") or contains(@class, "generic")]').first();
      const scheduleLink = childCard.locator('a[href*="/children/"]:has-text("View Schedule")');
      if (await scheduleLink.count() > 0) {
        const href = await scheduleLink.getAttribute('href');
        const match = href?.match(/\/children\/(\d+)/);
        if (match) {
          console.log(`DEBUG: Successfully detected child ID: ${match[1]} for "Debug Test Child"`);
        } else {
          console.log(`DEBUG: Failed to match child ID from href: ${href}`);
        }
      } else {
        console.log('DEBUG: No schedule link found in child card');
      }
    } else {
      console.log('DEBUG: "Debug Test Child" header not found, looking for any Test Child...');
      const testChildHeader = page.locator('h3:has-text("Test Child")');
      const testChildCount = await testChildHeader.count();
      console.log(`DEBUG: Found ${testChildCount} instances of "Test Child" header`);
    }
    
    // Final fallback - just use last schedule link
    if (newCount > 0) {
      const lastLink = newScheduleLinks.last();
      const href = await lastLink.getAttribute('href');
      const match = href?.match(/\/children\/(\d+)/);
      if (match) {
        const childId = match[1];
        console.log(`DEBUG: Using last child ID as fallback: ${childId}`);
        
        // Try to verify this child exists
        const response = await page.goto(`/dashboard/child/${childId}/today`);
        console.log(`DEBUG: Child ${childId} page status: ${response?.status()}`);
        
        if (response?.status() === 200) {
          console.log('DEBUG: Child verification successful!');
        } else {
          console.log('DEBUG: Child verification failed');
        }
      }
    }
    
    console.log('=== DEBUG: Child ID detection test completed ===');
    expect(true).toBe(true); // Pass test if we get here without errors
  });
});