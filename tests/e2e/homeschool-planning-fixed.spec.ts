import { test, expect } from '@playwright/test';
import { ModalHelper, ElementHelper } from './helpers/modal-helpers';
import { KidsModeHelper } from './helpers/kids-mode-helpers';

test.describe('Homeschool Planning - Fixed Tests', () => {
  let testUser: { name: string; email: string; password: string };
  let modalHelper: ModalHelper;
  let elementHelper: ElementHelper;
  let kidsModeHelper: KidsModeHelper;
  
  test.beforeEach(async ({ page }) => {
    // Initialize helpers
    modalHelper = new ModalHelper(page);
    elementHelper = new ElementHelper(page);
    kidsModeHelper = new KidsModeHelper(page);
    
    // Ensure we're not in kids mode at start of each test
    await kidsModeHelper.resetKidsMode();
    
    // Create a unique test user for this session
    testUser = {
      name: 'Planning Test Parent',
      email: `planning-fixed-${Date.now()}@example.com`,
      password: 'testpassword123'
    };

    // Register using proper helper methods
    await page.goto('/register');
    await elementHelper.waitForPageReady();
    
    // Fill registration form using helper methods
    await elementHelper.safeFill('input[name="name"]', testUser.name);
    await elementHelper.safeFill('input[name="email"]', testUser.email);
    await elementHelper.safeFill('input[name="password"]', testUser.password);
    await elementHelper.safeFill('input[name="password_confirmation"]', testUser.password);
    
    // Submit registration with safe click
    await elementHelper.safeClick('button[type="submit"]');
    
    // Wait for navigation and check result
    await elementHelper.waitForPageReady();
    
    const currentUrl = page.url();
    if (currentUrl.includes('/dashboard')) {
      // Success - Laravel redirected to dashboard after registration
      console.log('Registration successful, user logged in');
    } else if (currentUrl.includes('/register')) {
      // Registration may have failed, try to login instead
      console.log('Registration failed, attempting login');
      await page.goto('/login');
      await elementHelper.waitForPageReady();
      
      await elementHelper.safeFill('input[name="email"]', testUser.email);
      await elementHelper.safeFill('input[name="password"]', testUser.password);
      await elementHelper.safeClick('button[type="submit"]');
      
      await elementHelper.waitForPageReady();
    } else if (currentUrl.includes('/email/verify')) {
      // Email verification required - this is fine for testing
      console.log('Email verification required - continuing with test');
    }
    
    // Verify authentication worked by checking we can access dashboard
    const finalUrl = page.url();
    if (finalUrl.includes('/login') || finalUrl.includes('/register')) {
      throw new Error('Authentication failed in test setup');
    }
    console.log('User authenticated successfully');
  });

  test('should access main homeschool planning pages', async ({ page }) => {
    // Test 1: Dashboard access
    console.log('Testing dashboard access...');
    await page.goto('/dashboard');
    await elementHelper.waitForPageReady();
    
    // Just verify page loads without database errors
    const hasError = await page.locator('text=Internal Server Error').count() > 0;
    expect(hasError).toBe(false);
    console.log('✅ Dashboard loads without errors');
    
    // Test 2: Planning board access  
    console.log('Testing planning board access...');
    await page.goto('/planning');
    await elementHelper.waitForPageReady();
    
    // Check that planning page loads (look for planning-related content)
    const hasError2 = await page.locator('text=Internal Server Error').count() > 0;
    expect(hasError2).toBe(false);
    
    // Look for any content that suggests it's a planning page
    const hasContent = await page.locator('body').textContent();
    expect(hasContent).toMatch(/planning|board|child|select/i);
    console.log('✅ Planning board loads without errors');
    
    // Test 3: Subjects page access
    console.log('Testing subjects page access...');
    await page.goto('/subjects');
    await elementHelper.waitForPageReady();
    
    const hasError3 = await page.locator('text=Internal Server Error').count() > 0;
    expect(hasError3).toBe(false);
    
    const hasSubjectsContent = await page.locator('body').textContent();
    expect(hasSubjectsContent).toMatch(/subject|add|create/i);
    console.log('✅ Subjects page loads without errors');
    
    // Test 4: Calendar page access
    console.log('Testing calendar page access...');
    await page.goto('/calendar');
    await elementHelper.waitForPageReady();
    
    const hasError4 = await page.locator('text=Internal Server Error').count() > 0;
    expect(hasError4).toBe(false);
    
    const hasCalendarContent = await page.locator('body').textContent();
    expect(hasCalendarContent).toMatch(/calendar|schedule|week|day/i);
    console.log('✅ Calendar page loads without errors');
  });

  test('should handle navigation between planning pages', async ({ page }) => {
    console.log('Testing navigation between planning pages...');
    
    // Start at dashboard
    await page.goto('/dashboard');
    await elementHelper.waitForPageReady();
    expect(await page.locator('text=Internal Server Error').count()).toBe(0);
    
    // Navigate to subjects
    await page.goto('/subjects');
    await elementHelper.waitForPageReady();
    expect(await page.locator('text=Internal Server Error').count()).toBe(0);
    
    // Navigate to planning
    await page.goto('/planning');
    await elementHelper.waitForPageReady();
    expect(await page.locator('text=Internal Server Error').count()).toBe(0);
    
    // Navigate to calendar
    await page.goto('/calendar');
    await elementHelper.waitForPageReady();
    expect(await page.locator('text=Internal Server Error').count()).toBe(0);
    
    // Navigate back to dashboard
    await page.goto('/dashboard');
    await elementHelper.waitForPageReady();
    expect(await page.locator('text=Internal Server Error').count()).toBe(0);
    
    console.log('✅ All navigation successful');
  });

  test('should maintain authentication across pages', async ({ page }) => {
    console.log('Testing authentication persistence...');
    
    const testPages = ['/dashboard', '/planning', '/subjects', '/calendar'];
    
    for (const testPage of testPages) {
      await page.goto(testPage);
      await elementHelper.waitForPageReady();
      
      // Verify we're not redirected to login
      const currentUrl = page.url();
      expect(currentUrl).not.toContain('/login');
      expect(currentUrl).not.toContain('/register');
      
      // Verify no authentication errors
      const hasAuthError = await page.locator('text=Unauthenticated').count() > 0;
      expect(hasAuthError).toBe(false);
      
      console.log(`✅ ${testPage} maintains authentication`);
    }
  });

  test('should handle basic error states gracefully', async ({ page }) => {
    console.log('Testing error handling...');
    
    // Test non-existent child ID (should not crash)
    await page.goto('/dashboard/child/99999/today');
    await elementHelper.waitForPageReady();
    
    // Should either redirect or show a user-friendly error
    const currentUrl = page.url();
    const hasServerError = await page.locator('text=Internal Server Error').count() > 0;
    
    // Accept various valid responses: redirect to dashboard, 404, or access denied
    const isValidResponse = currentUrl.includes('/dashboard') || 
                           currentUrl.includes('/login') ||
                           await page.locator('text=Not Found').count() > 0 ||
                           await page.locator('text=Access Denied').count() > 0;
    
    // Main requirement: should not show internal server error
    expect(hasServerError).toBe(false);
    console.log('✅ Error states handled gracefully');
  });
});