import { test, expect } from '@playwright/test';

test.describe('Debug Selector Fixes', () => {
  let testUser: { email: string; password: string };

  test.beforeEach(async ({ page }) => {
    // Create unique test user
    testUser = {
      email: `selectorfix-${Date.now()}-${Math.random().toString(36).substring(7)}@example.com`,
      password: 'testpass123'
    };

    console.log(`Creating test user: ${testUser.email}`);

    // Register user
    await page.goto('/register', { waitUntil: 'networkidle' });
    await page.fill('input[name="name"]', 'Test Parent');
    await page.fill('input[name="email"]', testUser.email);
    await page.fill('input[name="password"]', testUser.password);
    await page.fill('input[name="password_confirmation"]', testUser.password);
    await page.click('button[type="submit"]');
    
    // Wait for redirect
    await page.waitForTimeout(2000);
    
    // Handle onboarding if redirected there
    if (page.url().includes('/onboarding')) {
      console.log('User redirected to onboarding, skipping for now...');
      await page.goto('/dashboard', { waitUntil: 'networkidle' });
    }
    
    // Navigate to children page
    await page.goto('/children', { waitUntil: 'networkidle' });
  });

  test('should use correct Add Child button selector', async ({ page }) => {
    console.log('Testing correct Add Child button selector...');
    
    // Check if we have both buttons visible
    const headerBtn = page.locator('[data-testid="header-add-child-btn"]');
    const emptyStateBtn = page.locator('[data-testid="empty-state-add-child-btn"]');
    
    const headerBtnCount = await headerBtn.count();
    const emptyStateBtnCount = await emptyStateBtn.count();
    
    console.log(`Header Add Child button count: ${headerBtnCount}`);
    console.log(`Empty state Add Child button count: ${emptyStateBtnCount}`);
    
    // At least one should be visible
    expect(headerBtnCount + emptyStateBtnCount).toBeGreaterThan(0);
    
    // Click the header button if available, otherwise the empty state button
    const targetBtn = headerBtnCount > 0 ? headerBtn : emptyStateBtn;
    
    console.log('Clicking Add Child button...');
    await targetBtn.click();
    
    // Wait for modal to appear with proper selector
    console.log('Waiting for modal to be visible...');
    await page.waitForSelector('#child-form-modal [data-testid="modal-content"]', { timeout: 10000 });
    await page.waitForTimeout(1000);
    
    // Verify modal content is visible
    await expect(page.locator('#child-form-modal [data-testid="modal-content"]')).toBeVisible();
    await expect(page.locator('#child-form-modal input[name="name"]')).toBeVisible();
    await expect(page.locator('#child-form-modal select[name="age"]')).toBeVisible();
    
    console.log('Modal successfully opened with correct selectors!');
    
    // Fill and submit the form to test the complete flow
    await page.fill('#child-form-modal input[name="name"]', 'Test Child');
    await page.selectOption('#child-form-modal select[name="age"]', '8');
    await page.selectOption('#child-form-modal select[name="independence_level"]', '2');
    
    console.log('Submitting form...');
    await page.click('#child-form-modal button[type="submit"]');
    
    // Wait for form submission
    await page.waitForTimeout(3000);
    
    // Verify child appears in the list
    await expect(page.locator('text=Test Child')).toBeVisible();
    
    console.log('Child creation test completed successfully!');
  });

  test('should verify h2 subjects selector works', async ({ page }) => {
    console.log('Testing h2 subjects selector...');
    
    await page.goto('/subjects', { waitUntil: 'networkidle' });
    
    // Check if we're on subjects list page or redirected to a specific subject
    const currentUrl = page.url();
    if (currentUrl.includes('/subjects/') && currentUrl !== '/subjects') {
      // We're on a specific subject detail page
      console.log('Redirected to specific subject page, checking h1 instead');
      await expect(page.locator('h1')).toBeVisible();
    } else {
      // We're on the subjects list page
      console.log('On subjects list page, checking h2');
      await expect(page.locator('h2').filter({ hasText: 'Subjects' })).toBeVisible();
    }
    
    console.log('Subjects selector test completed successfully!');
  });
});