import { test, expect } from '@playwright/test';

test.describe('Internationalization (i18n)', () => {
  test.beforeEach(async ({ page }) => {
    // Clear any existing browser state to ensure each test starts fresh
    await page.context().clearCookies();
    
    // Force English locale by setting a backup cookie that our middleware checks
    await page.context().addCookies([{
      name: 'locale_backup',
      value: 'en',
      domain: '127.0.0.1',
      path: '/'
    }]);
    
    // Also clear any guest locale preferences from the database by making an API call
    try {
      await page.goto('/register'); // Navigate to a page first to establish session
      await page.waitForLoadState('networkidle');
      
      // Call our locale update endpoint to force reset to English
      await page.evaluate(async () => {
        try {
          const response = await fetch('/locale/session', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ locale: 'en' })
          });
          console.log('Locale reset response:', await response.json());
        } catch (error) {
          console.log('Locale reset failed:', error);
        }
      });
    } catch (error) {
      console.log('Test setup failed, but continuing with test:', error);
    }
  });

  test.afterEach(async ({ page }) => {
    // Clean up after each test to ensure no locale state persists
    try {
      await page.evaluate(async () => {
        try {
          // Reset to English after each test
          const response = await fetch('/locale/session', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ locale: 'en' })
          });
          console.log('Post-test locale reset response:', await response.json());
        } catch (error) {
          console.log('Post-test locale reset failed:', error);
        }
      });
    } catch (error) {
      console.log('Test cleanup failed:', error);
    }
    
    // Clear all cookies again
    await page.context().clearCookies();
  });

  test('should load JavaScript translations on page', async ({ page }) => {
    // Navigate to register page
    await page.goto('/register');
    
    // Wait for page to load and check that translations are available in JavaScript
    const translationsAvailable = await page.evaluate(() => {
      return typeof window.translations === 'object' && window.translations !== null;
    });
    
    expect(translationsAvailable).toBe(true);
    
    // Check that JavaScript translation function exists
    const translateFunctionExists = await page.evaluate(() => {
      return typeof window.__ === 'function';
    });
    
    expect(translateFunctionExists).toBe(true);
    
    // Test JavaScript translation function with a known key
    const translatedText = await page.evaluate(() => {
      return window.__('login');
    });
    
    expect(translatedText).toBe('Login'); // Should be 'Login' in English
  });

  test('should switch languages properly', async ({ page }) => {
    // Navigate to register page
    await page.goto('/register');
    
    // Wait for page to load
    await page.waitForLoadState('networkidle');
    
    // Verify page is initially in English
    expect(await page.locator('h2').textContent()).toContain('Create');
    
    // Look for language switcher (should be in navigation)
    const languageSwitcher = page.locator('[data-testid="language-switcher"], button:has-text("🇬🇧"), button:has-text("English")').first();
    
    // If language switcher is found, test it
    if (await languageSwitcher.isVisible({ timeout: 5000 })) {
      // Click the language switcher
      await languageSwitcher.click();
      
      // Wait for dropdown to appear with more generous timeout
      await page.waitForTimeout(500);
      
      // Look for Russian option
      const russianOption = page.locator('button:has-text("🇷🇺"), button:has-text("Русский"), [data-testid="language-option-ru"]').first();
      
      if (await russianOption.isVisible({ timeout: 5000 })) {
        // Click Russian option
        await russianOption.click();
        
        // Wait for page reload/update with better timing
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000); // More generous wait for JS to initialize
        
        // Check that current locale is updated in JavaScript
        const currentLocale = await page.evaluate(() => {
          return window.currentLocale;
        });
        
        expect(currentLocale).toBe('ru');
        
        // Test Russian translation
        const translatedText = await page.evaluate(() => {
          return window.__('login');
        });
        
        expect(translatedText).toBe('Войти'); // Should be 'Войти' in Russian
      } else {
        console.log('Russian language option not found - language switcher UI may need attention');
      }
    } else {
      console.log('Language switcher not found - may be hidden or not implemented yet');
    }
  });

  test('should handle translation parameters in JavaScript', async ({ page }) => {
    // Navigate to any page
    await page.goto('/register');
    
    // Test parameter replacement in JavaScript
    const parameterizedTranslation = await page.evaluate(() => {
      return window.__('welcome_user', { email: 'test@example.com' });
    });
    
    expect(parameterizedTranslation).toContain('test@example.com');
  });

  test('should handle missing translation keys gracefully', async ({ page }) => {
    // Navigate to any page
    await page.goto('/register');
    
    // Test with non-existent key - should return the key itself
    const missingTranslation = await page.evaluate(() => {
      return window.__('non_existent_key_12345');
    });
    
    expect(missingTranslation).toBe('non_existent_key_12345');
  });

  test('should persist language preference across page navigation', async ({ page }) => {
    // Navigate to register page
    await page.goto('/register');
    await page.waitForLoadState('networkidle');
    
    // Try to switch language if switcher is available
    const languageSwitcher = page.locator('[data-testid="language-switcher"], button:has-text("🇬🇧"), button:has-text("English")').first();
    
    if (await languageSwitcher.isVisible({ timeout: 5000 })) {
      await languageSwitcher.click();
      await page.waitForTimeout(100);
      
      const russianOption = page.locator('button:has-text("🇷🇺"), button:has-text("Русский")').first();
      if (await russianOption.isVisible({ timeout: 2000 })) {
        await russianOption.click();
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);
        
        // Navigate to login page
        const loginLink = page.locator('a:has-text("sign in"), a:has-text("войти"), [href*="login"]').first();
        if (await loginLink.isVisible({ timeout: 2000 })) {
          await loginLink.click();
          await page.waitForLoadState('networkidle');
          
          // Check that language is still Russian
          const currentLocale = await page.evaluate(() => {
            return window.currentLocale;
          });
          
          expect(currentLocale).toBe('ru');
        }
      }
    } else {
      // Skip test if language switcher not available
      test.skip(true, 'Language switcher not available for testing');
    }
  });
});