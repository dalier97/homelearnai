import { test, expect } from '@playwright/test';

test.describe('Basic Review System Tests', () => {
  
  // Helper function to select first child (same as in review-system.spec.ts)
  async function selectFirstChild(page) {
    const childSelector = '[data-child-id]';
    const childOption = page.locator(childSelector).first();
    if (await childOption.isVisible()) {
      await childOption.click();
      await page.waitForTimeout(1000);
    }
  }

  test.beforeEach(async ({ page }) => {
    // Register a test user and create a test child
    await page.goto('/register');
    
    const timestamp = Date.now();
    const email = `review-test-${timestamp}@example.com`;
    
    await page.fill('input[name="name"]', 'Review Tester');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', 'testpass123');
    await page.fill('input[name="password_confirmation"]', 'testpass123');
    await page.click('button[type="submit"]');
    
    // Should redirect to dashboard after registration
    await page.waitForURL('**/dashboard**');
    
    // Create a test child
    await page.goto('/children');
    await page.click('[data-testid="header-add-child-btn"]');
    await page.waitForSelector('#child-form-modal [data-testid="modal-content"]', { timeout: 10000 });
    await page.waitForTimeout(1000);
    await page.fill('input[name="name"]', 'Review Test Child');
    await page.selectOption('select[name="age"]', '10');
    await page.selectOption('select[name="independence_level"]', '2');
    await page.click('button[type="submit"]:has-text("Add Child")');
    
    await page.waitForLoadState('networkidle');
  });

  test('basic review workflow: navigate pages and verify functionality', async ({ page }) => {
    console.log('Testing basic review system workflow...');
    
    // Step 1: Navigate to reviews
    await page.goto('/reviews');
    
    // Select the first available child
    await selectFirstChild(page);
    
    // Verify we can access the review dashboard
    await expect(page.locator('h1:has-text("Review System")')).toBeVisible();
    
    // Navigate to slot management to verify the page loads
    await page.click('text=Manage Review Slots');
    await expect(page.locator('h1:has-text("Review Slots")')).toBeVisible();
    
    // Verify the slot creation modal can be opened
    await page.click('button:has-text("Add Slot")');
    await page.waitForSelector('#add-slot-modal:not(.hidden)', { timeout: 10000 });
    await expect(page.locator('#add-slot-modal')).toBeVisible();
    
    // Close the modal
    await page.click('#add-slot-modal button:has-text("Cancel")');
    await page.waitForFunction(() => {
      const modal = document.getElementById('add-slot-modal');
      return modal && modal.classList.contains('hidden');
    }, { timeout: 10000 });
    
    // Verify existing slots are displayed (created by default)
    await expect(page.locator('#day-1-slots .review-slot').first()).toBeVisible();
    await expect(page.locator('[data-time-range]').first()).toBeVisible();
    
    // Step 2: Go back to reviews main page
    await page.click('text=Back to Reviews');
    await page.waitForURL('**/reviews**');
    
    // Verify review dashboard elements are present
    await expect(page.locator('h1:has-text("Review System")')).toBeVisible();
    
    console.log('Basic review workflow test completed successfully');
  });
});