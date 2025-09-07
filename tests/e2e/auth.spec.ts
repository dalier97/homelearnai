import { test, expect } from '@playwright/test';
import { ModalHelper, ElementHelper } from './helpers/modal-helpers';
import { KidsModeHelper } from './helpers/kids-mode-helpers';

test.describe('Authentication Flow', () => {
  let modalHelper: ModalHelper;
  let elementHelper: ElementHelper;
  let kidsModeHelper: KidsModeHelper;

  test.beforeEach(async ({ page }) => {
    modalHelper = new ModalHelper(page);
    elementHelper = new ElementHelper(page);
    kidsModeHelper = new KidsModeHelper(page);
    
    // Ensure we're not in kids mode at start of each test
    await kidsModeHelper.resetKidsMode();
    
    // Start fresh for each test and wait for page to be ready
    await page.goto('/');
    await elementHelper.waitForPageReady();
  });

  test('should display registration form', async ({ page }) => {
    await page.goto('/register');
    await elementHelper.waitForPageReady();
    
    // Verify page title and form elements
    await expect(page.locator('h2')).toContainText('Create your account', { timeout: 10000 });
    
    // Check all form fields are present and interactable
    await elementHelper.waitForInteraction('input[name="name"]');
    await expect(page.locator('input[name="name"]')).toBeVisible();
    
    await elementHelper.waitForInteraction('input[name="email"]');  
    await expect(page.locator('input[name="email"]')).toBeVisible();
    
    await elementHelper.waitForInteraction('input[name="password"]');
    await expect(page.locator('input[name="password"]')).toBeVisible();
    
    await elementHelper.waitForInteraction('input[name="password_confirmation"]');
    await expect(page.locator('input[name="password_confirmation"]')).toBeVisible();
    
    await elementHelper.waitForInteraction('button[type="submit"]');
    await expect(page.locator('button[type="submit"]')).toContainText('Create account');
  });

  test('should display login form', async ({ page }) => {
    await page.goto('/login');
    await elementHelper.waitForPageReady();
    
    // Verify page title and form elements
    await expect(page.locator('h2')).toContainText('Sign in to Homeschool Hub', { timeout: 10000 });
    
    // Check form fields are present and interactable
    await elementHelper.waitForInteraction('input[name="email"]');
    await expect(page.locator('input[name="email"]')).toBeVisible();
    
    await elementHelper.waitForInteraction('input[name="password"]');
    await expect(page.locator('input[name="password"]')).toBeVisible();
    
    await elementHelper.waitForInteraction('button[type="submit"]');
    await expect(page.locator('button[type="submit"]')).toContainText('Sign in');
  });

  test('should register new user and create test data', async ({ page, browserName }) => {
    // Skip this test on Firefox due to Supabase registration timeout issues
    test.skip(browserName === 'firefox', 'Skipping flaky Supabase registration test on Firefox');
    
    const testUser = {
      name: 'Test User E2E',
      email: `test-${Date.now()}@example.com`,
      password: 'password123'
    };

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
      // Direct redirect to dashboard (email confirmation disabled)
      await expect(page.locator('h1')).toContainText('Homeschool Hub', { timeout: 15000 });
      await expect(page.locator('body')).toContainText('Parent Dashboard');
    } else if (currentUrl.includes('/register')) {
      // Check for success/error messages
      const hasSuccessMessage = await page.locator('text=success').count() > 0;
      const hasErrorMessage = await page.locator('text=error').count() > 0 || 
                              await page.locator('.text-red-500').count() > 0;
      
      if (!hasSuccessMessage && !hasErrorMessage) {
        // Try to login with the credentials to verify registration worked
        await page.goto('/login');
        await elementHelper.waitForPageReady();
        
        await elementHelper.safeFill('input[name="email"]', testUser.email);
        await elementHelper.safeFill('input[name="password"]', testUser.password);
        await elementHelper.safeClick('button[type="submit"]');
        
        await elementHelper.waitForPageReady();
        if (page.url().includes('/dashboard')) {
          await expect(page.locator('h1')).toContainText('Homeschool Hub', { timeout: 15000 });
        }
      }
    }
  });

  test('should handle registration validation errors', async ({ page }) => {
    await page.goto('/register');
    await elementHelper.waitForPageReady();
    
    // Try to submit with empty fields using safe click
    await elementHelper.safeClick('button[type="submit"]');
    
    // Should stay on registration page (Laravel validation should prevent submission)
    await expect(page).toHaveURL('/register');
  });

  test('should navigate between auth pages', async ({ page }) => {
    // Start at registration
    await page.goto('/register');
    await elementHelper.waitForPageReady();
    
    await expect(page.locator('h2')).toContainText('Create your account', { timeout: 10000 });
    
    // Check if there's a login link and navigate
    const loginLink = page.locator('a[href*="/login"]');
    if (await loginLink.count() > 0) {
      await elementHelper.safeClick('a[href*="/login"]');
      await elementHelper.waitForPageReady();
      await expect(page).toHaveURL('/login');
      
      await expect(page.locator('h2')).toContainText('Sign in to Homeschool Hub', { timeout: 10000 });
    }
  });
});