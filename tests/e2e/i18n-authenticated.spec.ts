import { test, expect } from '@playwright/test';

// Helper function to login
async function loginUser(page, email: string, password: string) {
  await page.goto('/login');
  await page.waitForLoadState('networkidle');
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  await page.click('button[type="submit"]');
  // Wait for navigation - could go to dashboard or children page
  await page.waitForURL(/(dashboard|children)/, { timeout: 10000 });
}

// Helper function to register a new user
async function registerUser(page, email: string, password: string, name: string) {
  await page.goto('/register');
  await page.fill('input[name="name"]', name);
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  await page.fill('input[name="password_confirmation"]', password);
  await page.click('button[type="submit"]');
  // Wait for either dashboard or login page (in case email confirmation is required)
  await page.waitForURL(/(dashboard|login)/);
}

// Helper function to change language
async function changeLanguage(page, targetLocale: string) {
  // Click language switcher
  const languageSwitcher = page.locator('[data-testid="language-switcher"]');
  await languageSwitcher.click();
  
  // Wait for dropdown to open
  await page.waitForTimeout(200);
  
  // Click target language option
  const languageOption = page.locator(`[data-testid="language-option-${targetLocale}"]`);
  await languageOption.click();
  
  // Wait for language change to complete
  await page.waitForTimeout(500);
}

// Helper to get current locale from window
async function getCurrentLocale(page) {
  return await page.evaluate(() => window.currentLocale || document.documentElement.lang);
}

test.describe('i18n for Authenticated Users', () => {
  // Generate unique email for each test run
  const getTestEmail = () => `test_${Date.now()}_${Math.random().toString(36).substr(2, 9)}@example.com`;
  const testPassword = 'Test1234!';
  const testName = 'Test User';

  test('should persist language preference for authenticated users across page navigation', async ({ page, browserName }) => {
    // Skip this test on Firefox due to Supabase registration timeout issues
    test.skip(browserName === 'firefox', 'Skipping flaky Supabase registration test on Firefox');
    const testEmail = getTestEmail();
    
    // Step 1: Register a new user
    await registerUser(page, testEmail, testPassword, testName);
    
    // Step 2: Login if we were redirected to login page
    const currentUrl = page.url();
    if (currentUrl.includes('/login')) {
      await loginUser(page, testEmail, testPassword);
    }
    
    // Step 3: Verify we're on dashboard
    await expect(page).toHaveURL(/dashboard/);
    
    // Step 4: Ensure we start with English (reset if needed)
    let currentLocale = await getCurrentLocale(page);
    if (currentLocale !== 'en') {
      await changeLanguage(page, 'en');
      currentLocale = await getCurrentLocale(page);
    }
    expect(currentLocale).toBe('en');
    
    // Step 5: Change language to Russian
    await changeLanguage(page, 'ru');
    
    // Step 6: Verify language changed
    currentLocale = await getCurrentLocale(page);
    expect(currentLocale).toBe('ru');
    
    // Step 7: Navigate to different authenticated pages
    await page.goto('/children');
    await page.waitForLoadState('networkidle');
    currentLocale = await getCurrentLocale(page);
    expect(currentLocale).toBe('ru');
    
    await page.goto('/subjects');
    await page.waitForLoadState('networkidle');
    currentLocale = await getCurrentLocale(page);
    expect(currentLocale).toBe('ru');
    
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');
    currentLocale = await getCurrentLocale(page);
    expect(currentLocale).toBe('ru');
    
    // Step 8: Reload page completely
    await page.reload();
    await page.waitForLoadState('networkidle');
    currentLocale = await getCurrentLocale(page);
    expect(currentLocale).toBe('ru');
    
    // Step 9: Change back to English
    await changeLanguage(page, 'en');
    currentLocale = await getCurrentLocale(page);
    expect(currentLocale).toBe('en');
    
    // Step 10: Navigate and verify English persists
    await page.goto('/children');
    await page.waitForLoadState('networkidle');
    currentLocale = await getCurrentLocale(page);
    expect(currentLocale).toBe('en');
  });

  test('should maintain language preference after logout and login', async ({ page, browserName }) => {
    // Skip this test on Firefox due to Supabase registration timeout issues
    test.skip(browserName === 'firefox', 'Skipping flaky Supabase registration test on Firefox');
    const testEmail = getTestEmail();
    
    // First register the user
    await registerUser(page, testEmail, testPassword, testName);
    
    // Login if redirected to login page
    const currentUrl = page.url();
    if (currentUrl.includes('/login')) {
      await loginUser(page, testEmail, testPassword);
    }
    
    // Step 2: Change to Russian
    await changeLanguage(page, 'ru');
    let currentLocale = await getCurrentLocale(page);
    expect(currentLocale).toBe('ru');
    
    // Step 3: Logout
    await page.click('button:has-text("Logout"), a:has-text("Logout"), button:has-text("Выйти"), a:has-text("Выйти")');
    await page.waitForURL('**/login**');
    
    // Step 4: Login again
    await loginUser(page, testEmail, testPassword);
    
    // Step 5: Verify language is still Russian
    currentLocale = await getCurrentLocale(page);
    expect(currentLocale).toBe('ru');
  });

  test('should update UI elements when language changes for authenticated user', async ({ page, browserName }) => {
    // Skip this test on Firefox due to Supabase registration timeout issues
    test.skip(browserName === 'firefox', 'Skipping flaky Supabase registration test on Firefox');
    const testEmail = getTestEmail();
    
    // First register the user
    await registerUser(page, testEmail, testPassword, testName);
    
    // Login if redirected to login page  
    const currentUrl = page.url();
    if (currentUrl.includes('/login')) {
      await loginUser(page, testEmail, testPassword);
    }
    
    // Ensure we're in English first
    const currentLocale = await getCurrentLocale(page);
    if (currentLocale !== 'en') {
      await changeLanguage(page, 'en');
    }
    
    // Check English text is visible
    await expect(page.locator('text=Dashboard').first()).toBeVisible({ timeout: 5000 });
    
    // Switch to Russian
    await changeLanguage(page, 'ru');
    
    // Check Russian text appears (Parent Dashboard in Russian is "Панель родителя")
    await expect(page.locator('text=Панель родителя').first()).toBeVisible({ timeout: 5000 });
    
    // Check that English text is no longer visible
    const dashboardText = await page.locator('h1, h2, h3, h4, h5, h6').filter({ hasText: /^Dashboard$/ }).count();
    expect(dashboardText).toBe(0);
  });

  test('should handle rapid language switches without errors', async ({ page, browserName }) => {
    // Skip this test on Firefox due to Supabase registration timeout issues
    test.skip(browserName === 'firefox', 'Skipping flaky Supabase registration test on Firefox');
    const testEmail = getTestEmail();
    
    // First register the user
    await registerUser(page, testEmail, testPassword, testName);
    
    // Login if redirected to login page
    const currentUrl = page.url();
    if (currentUrl.includes('/login')) {
      await loginUser(page, testEmail, testPassword);
    }
    
    // Rapidly switch languages
    for (let i = 0; i < 3; i++) {
      await changeLanguage(page, 'ru');
      await page.waitForTimeout(100);
      await changeLanguage(page, 'en');
      await page.waitForTimeout(100);
    }
    
    // Final switch to Russian
    await changeLanguage(page, 'ru');
    
    // Verify it stuck
    const currentLocale = await getCurrentLocale(page);
    expect(currentLocale).toBe('ru');
    
    // Navigate to ensure no errors
    await page.goto('/subjects');
    await expect(page).toHaveURL(/subjects/);
  });
});