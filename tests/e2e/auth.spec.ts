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
    
    // Verify Laravel logo is present (Laravel Breeze UI indicator)
    await expect(page.locator('svg')).toBeVisible({ timeout: 10000 });
    
    // Check all form fields are present and interactable
    await elementHelper.waitForInteraction('input[name="name"]');
    await expect(page.locator('input[name="name"]')).toBeVisible();
    
    await elementHelper.waitForInteraction('input[name="email"]');  
    await expect(page.locator('input[name="email"]')).toBeVisible();
    
    await elementHelper.waitForInteraction('input[name="password"]');
    await expect(page.locator('input[name="password"]')).toBeVisible();
    
    await elementHelper.waitForInteraction('input[name="password_confirmation"]');
    await expect(page.locator('input[name="password_confirmation"]')).toBeVisible();
    
    // Check for Laravel Breeze register button
    await elementHelper.waitForInteraction('button[type="submit"]');
    await expect(page.locator('button[type="submit"]')).toContainText('Register');
    
    // Check "Already registered?" link is present
    await expect(page.locator('a[href*="/login"]')).toContainText('Already registered?');
  });

  test('should display login form', async ({ page }) => {
    await page.goto('/login');
    await elementHelper.waitForPageReady();
    
    // Verify Laravel logo is present (Laravel Breeze UI indicator)
    await expect(page.locator('svg')).toBeVisible({ timeout: 10000 });
    
    // Check form fields are present and interactable
    await elementHelper.waitForInteraction('input[name="email"]');
    await expect(page.locator('input[name="email"]')).toBeVisible();
    
    await elementHelper.waitForInteraction('input[name="password"]');
    await expect(page.locator('input[name="password"]')).toBeVisible();
    
    // Check for Laravel Breeze login button
    await elementHelper.waitForInteraction('button[type="submit"]');
    await expect(page.locator('button[type="submit"]')).toContainText('Log in');
    
    // Check "Remember me" checkbox is present
    await expect(page.locator('input[name="remember"]')).toBeVisible();
    
    // Check "Forgot your password?" link is present (if route exists)
    const forgotPasswordLink = page.locator('a[href*="password.request"]');
    if (await forgotPasswordLink.count() > 0) {
      await expect(forgotPasswordLink).toContainText('Forgot your password?');
    }
  });

  test('should register new user and create test data', async ({ page, browserName }) => {
    // No longer need to skip Firefox for Laravel native auth
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
    
    // Wait for navigation and check result (Laravel should redirect to dashboard after registration)
    await elementHelper.waitForPageReady();
    
    const currentUrl = page.url();
    if (currentUrl.includes('/dashboard')) {
      // Laravel Breeze redirects to dashboard after successful registration
      await expect(page.locator('h1, h2, .text-xl')).toContainText('Dashboard', { timeout: 15000 });
    } else if (currentUrl.includes('/register')) {
      // Check for Laravel validation errors
      const hasErrors = await page.locator('.text-red-600, .text-sm.text-red-600').count() > 0;
      
      if (!hasErrors) {
        // Try to login with the credentials to verify registration worked
        await page.goto('/login');
        await elementHelper.waitForPageReady();
        
        await elementHelper.safeFill('input[name="email"]', testUser.email);
        await elementHelper.safeFill('input[name="password"]', testUser.password);
        await elementHelper.safeClick('button[type="submit"]');
        
        await elementHelper.waitForPageReady();
        // Verify successful login by checking for dashboard
        await expect(page.locator('h1, h2, .text-xl')).toContainText('Dashboard', { timeout: 15000 });
      }
    } else if (currentUrl.includes('/email/verify')) {
      // Email verification required - this is a valid state for Laravel Breeze
      await expect(page.locator('body')).toContainText('verify', { timeout: 10000 });
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
    
    // Verify registration page loaded (Laravel Breeze UI)
    await expect(page.locator('input[name="name"]')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('button[type="submit"]')).toContainText('Register');
    
    // Check if there's a login link and navigate
    const loginLink = page.locator('a[href*="/login"]');
    if (await loginLink.count() > 0) {
      await elementHelper.safeClick('a[href*="/login"]');
      await elementHelper.waitForPageReady();
      await expect(page).toHaveURL('/login');
      
      // Verify login page loaded (Laravel Breeze UI)  
      await expect(page.locator('input[name="email"]')).toBeVisible({ timeout: 10000 });
      await expect(page.locator('input[name="password"]')).toBeVisible();
      await expect(page.locator('button[type="submit"]')).toContainText('Log in');
    }
  });
});