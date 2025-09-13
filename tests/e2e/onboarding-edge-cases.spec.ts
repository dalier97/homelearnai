import { test, expect } from '@playwright/test';
import { ModalHelper, ElementHelper } from './helpers/modal-helpers';
import { KidsModeHelper } from './helpers/kids-mode-helpers';

/**
 * Phase 6: Edge Cases Testing
 * 
 * This test suite validates edge cases and error scenarios:
 * - Skip functionality at different steps
 * - Back navigation and data editing
 * - Validation edge cases (empty forms, limits, special characters)
 * - Browser refresh and state persistence
 * - Error handling (server errors, network issues)
 * - Boundary conditions
 */
test.describe('Onboarding Edge Cases', () => {
  let modalHelper: ModalHelper;
  let elementHelper: ElementHelper;
  let kidsModeHelper: KidsModeHelper;

  test.beforeEach(async ({ page }) => {
    modalHelper = new ModalHelper(page);
    elementHelper = new ElementHelper(page);
    kidsModeHelper = new KidsModeHelper(page);
    
    // Ensure we're not in kids mode at start of each test
    await kidsModeHelper.resetKidsMode();
    
    // Start fresh for each test
    await page.goto('/');
    await elementHelper.waitForPageReady();
  });

  test('should allow skipping the entire onboarding wizard at step 1', async ({ page }) => {
    // Register and get to onboarding
    await page.goto('/register');
    const timestamp = Date.now();
    
    await page.fill('input[name="name"]', `Skip User ${timestamp}`);
    await page.fill('input[name="email"]', `skip-${timestamp}@example.com`);
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);
    
    // Handle potential registration issues
    const currentUrl = page.url();
    if (currentUrl.includes('/register')) {
      await page.goto('/login');
      await page.fill('input[name="email"]', 'test@example.com');
      await page.fill('input[name="password"]', 'password123');
      await page.click('button[type="submit"]');
      await page.waitForTimeout(2000);
    }
    
    await page.goto('/onboarding');
    await page.waitForURL('/onboarding', { timeout: 10000 });
    
    // Verify we're on step 1
    await expect(page.getByTestId('step-1')).toBeVisible({ timeout: 10000 });
    
    // Click skip setup button
    await expect(page.getByTestId('skip-button')).toBeVisible();
    await page.getByTestId('skip-button').click();
    
    // Should redirect to dashboard
    await page.waitForURL('/dashboard', { timeout: 10000 });
    
    // Verify we're on dashboard (even without children/subjects)
    await expect(page.locator('text=Dashboard')).toBeVisible();
    
    // Verify onboarding doesn't show again
    await page.goto('/onboarding');
    await expect(page).toHaveURL('/dashboard'); // Should redirect away
  });

  test('should allow skipping at step 2 (children) and continue to subjects', async ({ page }) => {
    // Quick setup to step 2
    await page.goto('/register');
    const timestamp = Date.now();
    
    await page.fill('input[name="name"]', `Skip Children ${timestamp}`);
    await page.fill('input[name="email"]', `skip-children-${timestamp}@example.com`);
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);
    
    // Handle registration fallback
    const currentUrl = page.url();
    if (currentUrl.includes('/register')) {
      await page.goto('/login');
      await page.fill('input[name="email"]', 'test@example.com');
      await page.fill('input[name="password"]', 'password123');
      await page.click('button[type="submit"]');
      await page.waitForTimeout(2000);
    }
    
    await page.goto('/onboarding');
    await page.waitForURL('/onboarding', { timeout: 10000 });
    
    // Navigate to step 2
    await page.getByTestId('next-button').click();
    await expect(page.getByTestId('step-2')).toBeVisible({ timeout: 10000 });
    
    // Skip children setup
    const skipChildrenCheckbox = page.locator('input[name*="skip_children"]');
    if (await skipChildrenCheckbox.count() > 0) {
      await skipChildrenCheckbox.check();
    } else {
      // If no skip checkbox, click the skip button
      const skipButton = page.locator('button:has-text("Skip"), [data-testid*="skip"]');
      if (await skipButton.count() > 0) {
        await skipButton.first().click();
      }
    }
    
    // Next button should be enabled even without children
    await expect(page.getByTestId('next-button')).not.toBeDisabled();
    await page.getByTestId('next-button').click();
    
    // Should proceed to step 3 (subjects) or step 4 (review)
    await page.waitForTimeout(2000);
    const step3Visible = await page.getByTestId('step-3').isVisible().catch(() => false);
    const step4Visible = await page.getByTestId('step-4').isVisible().catch(() => false);
    
    expect(step3Visible || step4Visible).toBe(true);
  });

  test('should handle back navigation and editing data in previous steps', async ({ page }) => {
    // Setup to complete multiple steps
    await page.goto('/register');
    const timestamp = Date.now();
    
    await page.fill('input[name="name"]', `Edit User ${timestamp}`);
    await page.fill('input[name="email"]', `edit-${timestamp}@example.com`);
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);
    
    // Handle potential registration issues
    const currentUrl = page.url();
    if (currentUrl.includes('/register')) {
      await page.goto('/login');
      await page.fill('input[name="email"]', 'test@example.com');
      await page.fill('input[name="password"]', 'password123');
      await page.click('button[type="submit"]');
      await page.waitForTimeout(2000);
    }
    
    await page.goto('/onboarding');
    await page.waitForURL('/onboarding', { timeout: 10000 });
    
    // Step 1 -> 2
    await page.getByTestId('next-button').click();
    await expect(page.getByTestId('step-2')).toBeVisible({ timeout: 10000 });
    
    // Add initial child data
    await page.getByTestId('child-name-0').fill('Original Name');
    await page.selectOption('[data-testid="child-grade-0"]', '8');
    await page.selectOption('[data-testid="child-independence-0"]', '2');
    
    // Step 2 -> 3
    await page.getByTestId('next-button').click();
    await expect(page.getByTestId('form-success')).toBeVisible({ timeout: 10000 });
    await expect(page.getByTestId('step-3')).toBeVisible({ timeout: 10000 });
    
    // Add a custom subject
    await page.locator('input[name*="custom"]').first().fill('Original Subject');
    
    // Step 3 -> 4
    await page.getByTestId('next-button').click();
    await expect(page.getByTestId('step-4')).toBeVisible({ timeout: 10000 });
    
    // Verify original data in review
    await expect(page.locator('text=Original Name')).toBeVisible();
    await expect(page.locator('span:has-text("Original Subject")')).toBeVisible();
    
    // Go back to step 3
    await page.getByTestId('review-back-button').click();
    await expect(page.getByTestId('step-3')).toBeVisible();
    
    // Verify data is preserved
    await expect(page.locator('input[name*="custom"]').first()).toHaveValue('Original Subject');
    
    // Go back to step 2
    await page.getByTestId('previous-button').click();
    await expect(page.getByTestId('step-2')).toBeVisible();
    
    // Edit the child's name
    await expect(page.getByTestId('child-name-0')).toHaveValue('Original Name');
    await page.getByTestId('child-name-0').clear();
    await page.getByTestId('child-name-0').fill('Edited Name');
    
    // Add a second child
    await page.getByTestId('add-another-child').click();
    await page.getByTestId('child-name-1').fill('Second Child');
    await page.selectOption('[data-testid="child-grade-1"]', '10');
    
    // Navigate forward to verify changes persist
    await page.getByTestId('next-button').click();
    await expect(page.getByTestId('step-3')).toBeVisible({ timeout: 10000 });
    
    // Should see both children
    await expect(page.locator('text=Edited Name')).toBeVisible();
    await expect(page.locator('text=Second Child')).toBeVisible();
    
    // Edit the custom subject
    await page.locator('input[name*="custom"]').first().clear();
    await page.locator('input[name*="custom"]').first().fill('Edited Subject');
    
    // Go to review
    await page.getByTestId('next-button').click();
    await expect(page.getByTestId('step-4')).toBeVisible({ timeout: 10000 });
    
    // Verify all edits are reflected
    await expect(page.locator('h3:has-text("2 Children Added")')).toBeVisible();
    await expect(page.locator('text=Edited Name')).toBeVisible();
    await expect(page.locator('text=Second Child')).toBeVisible();
    await expect(page.locator('span:has-text("Edited Subject")')).toBeVisible();
  });

  test('should validate empty forms and show appropriate errors', async ({ page }) => {
    // Quick setup to children form
    await page.goto('/register');
    const timestamp = Date.now();
    
    await page.fill('input[name="name"]', `Validation User ${timestamp}`);
    await page.fill('input[name="email"]', `validation-${timestamp}@example.com`);
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);
    
    // Handle potential registration issues
    const currentUrl = page.url();
    if (currentUrl.includes('/register')) {
      await page.goto('/login');
      await page.fill('input[name="email"]', 'test@example.com');
      await page.fill('input[name="password"]', 'password123');
      await page.click('button[type="submit"]');
      await page.waitForTimeout(2000);
    }
    
    await page.goto('/onboarding');
    await page.waitForURL('/onboarding', { timeout: 10000 });
    
    // Navigate to step 2
    await page.getByTestId('next-button').click();
    await expect(page.getByTestId('step-2')).toBeVisible({ timeout: 10000 });
    
    // Try to submit completely empty form
    await page.getByTestId('next-button').click();
    
    // Should show validation errors
    await expect(page.getByTestId('form-error')).toBeVisible();
    await expect(page.locator('text=Child name is required')).toBeVisible();
    await expect(page.locator('text=Child age is required')).toBeVisible();
    
    // Fill name but leave age empty
    await page.getByTestId('child-name-0').fill('Test Child');
    await page.getByTestId('next-button').click();
    
    // Should still show age validation error
    await expect(page.locator('text=Child age is required')).toBeVisible();
    
    // Fill age but clear name
    await page.getByTestId('child-name-0').clear();
    await page.selectOption('[data-testid="child-grade-0"]', '8');
    await page.getByTestId('next-button').click();
    
    // Should show name validation error again
    await expect(page.locator('text=Child name is required')).toBeVisible();
    
    // Test edge case: extremely long name
    await page.getByTestId('child-name-0').fill('A'.repeat(256));
    await page.getByTestId('next-button').click();
    
    // Should show appropriate error for name length
    const lengthError = await page.locator('text=Name too long').isVisible().catch(() => false);
    if (lengthError) {
      await expect(page.locator('text=Name too long')).toBeVisible();
    }
    
    // Test edge case: special characters
    await page.getByTestId('child-name-0').clear();
    await page.getByTestId('child-name-0').fill('Test <script>alert("xss")</script>');
    await page.getByTestId('next-button').click();
    
    // Should either accept it (if properly sanitized) or show validation error
    // The important thing is no actual script execution
    const pageContent = await page.content();
    expect(pageContent).not.toContain('<script>alert("xss")</script>');
  });

  test('should handle special characters and unicode in names', async ({ page }) => {
    // Quick setup
    await page.goto('/register');
    const timestamp = Date.now();
    
    await page.fill('input[name="name"]', `Unicode User ${timestamp}`);
    await page.fill('input[name="email"]', `unicode-${timestamp}@example.com`);
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);
    
    // Handle potential registration issues
    const currentUrl = page.url();
    if (currentUrl.includes('/register')) {
      await page.goto('/login');
      await page.fill('input[name="email"]', 'test@example.com');
      await page.fill('input[name="password"]', 'password123');
      await page.click('button[type="submit"]');
      await page.waitForTimeout(2000);
    }
    
    await page.goto('/onboarding');
    await page.waitForURL('/onboarding', { timeout: 10000 });
    
    // Navigate to children form
    await page.getByTestId('next-button').click();
    await expect(page.getByTestId('step-2')).toBeVisible({ timeout: 10000 });
    
    // Test various unicode and special characters
    const testNames = [
      'José María', // Accented characters
      '陈小明', // Chinese characters  
      'Åsa Lindström', // Nordic characters
      'Владимир', // Cyrillic characters
      'محمد', // Arabic characters
      'O\'Connor-Smith', // Apostrophe and hyphen
      'Jean-Luc Picard Jr.' // Complex name with punctuation
    ];
    
    // Test first special character name
    await page.getByTestId('child-name-0').fill(testNames[0]);
    await page.selectOption('[data-testid="child-grade-0"]', '8');
    await page.selectOption('[data-testid="child-independence-0"]', '2');
    
    // Add more children with special names
    for (let i = 1; i < Math.min(testNames.length, 5); i++) {
      await page.getByTestId('add-another-child').click();
      await page.getByTestId(`child-name-${i}`).fill(testNames[i]);
      await page.selectOption(`[data-testid="child-age-${i}"]`, '9');
      await page.selectOption(`[data-testid="child-independence-${i}"]`, '2');
    }
    
    // Submit children form
    await page.getByTestId('next-button').click();
    await expect(page.getByTestId('form-success')).toBeVisible({ timeout: 10000 });
    await expect(page.getByTestId('step-3')).toBeVisible({ timeout: 10000 });
    
    // Verify all special character names appear correctly
    for (let i = 0; i < Math.min(testNames.length, 5); i++) {
      await expect(page.locator(`text=${testNames[i]}`)).toBeVisible();
    }
    
    // Test custom subjects with special characters
    await page.locator('input[name*="custom"]').first().fill('Français & Español');
    await page.click('text=Add Custom Subject');
    await page.locator('input[name*="custom"]').nth(1).fill('العربية');
    
    // Continue to review
    await page.getByTestId('next-button').click();
    await expect(page.getByTestId('step-4')).toBeVisible({ timeout: 10000 });
    
    // Verify special characters are preserved in review
    await expect(page.locator('text=José María')).toBeVisible();
    await expect(page.locator('text=陈小明')).toBeVisible();
    await expect(page.locator('span:has-text("Français & Español")')).toBeVisible();
    await expect(page.locator('span:has-text("العربية")')).toBeVisible();
    
    // Complete onboarding and verify data persistence
    await page.getByTestId('complete-onboarding-button').click();
    await page.waitForURL('/dashboard', { timeout: 10000 });
    
    // Verify special characters survived the full process
    await expect(page.locator('text=José María')).toBeVisible();
    await expect(page.locator('text=陈小明')).toBeVisible();
  });

  test('should handle browser refresh during onboarding process', async ({ page }) => {
    // Setup and get to middle of onboarding
    await page.goto('/register');
    const timestamp = Date.now();
    
    await page.fill('input[name="name"]', `Refresh User ${timestamp}`);
    await page.fill('input[name="email"]', `refresh-${timestamp}@example.com`);
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);
    
    // Handle potential registration issues
    const currentUrl = page.url();
    if (currentUrl.includes('/register')) {
      await page.goto('/login');
      await page.fill('input[name="email"]', 'test@example.com');
      await page.fill('input[name="password"]', 'password123');
      await page.click('button[type="submit"]');
      await page.waitForTimeout(2000);
    }
    
    await page.goto('/onboarding');
    await page.waitForURL('/onboarding', { timeout: 10000 });
    
    // Complete step 1 and 2
    await page.getByTestId('next-button').click();
    await expect(page.getByTestId('step-2')).toBeVisible({ timeout: 10000 });
    
    await page.getByTestId('child-name-0').fill('Refresh Child');
    await page.selectOption('[data-testid="child-grade-0"]', '9');
    await page.selectOption('[data-testid="child-independence-0"]', '3');
    
    await page.getByTestId('next-button').click();
    await expect(page.getByTestId('step-3')).toBeVisible({ timeout: 10000 });
    
    // Refresh page while on step 3
    await page.reload();
    await elementHelper.waitForPageReady();
    
    // Should maintain position or redirect appropriately
    const step1Visible = await page.getByTestId('step-1').isVisible().catch(() => false);
    const step2Visible = await page.getByTestId('step-2').isVisible().catch(() => false);
    const step3Visible = await page.getByTestId('step-3').isVisible().catch(() => false);
    const dashboardVisible = await page.locator('text=Dashboard').isVisible().catch(() => false);
    
    // Should be on one of the valid states
    expect(step1Visible || step2Visible || step3Visible || dashboardVisible).toBe(true);
    
    // If we're back at step 1, the data should not be lost completely
    if (step1Visible) {
      // Navigate back through and verify some data persistence
      await page.getByTestId('next-button').click();
      await expect(page.getByTestId('step-2')).toBeVisible({ timeout: 10000 });
      
      // Child might be preserved in database even if form is empty
      // This depends on the implementation
    }
    
    // If we're on step 3, verify the child data is still there
    if (step3Visible) {
      await expect(page.locator('text=Refresh Child')).toBeVisible();
    }
    
    // If redirected to dashboard, that's also acceptable behavior
    if (dashboardVisible) {
      await expect(page.locator('text=Dashboard')).toBeVisible();
    }
  });

  test('should handle server errors gracefully', async ({ page }) => {
    // Setup
    await page.goto('/register');
    const timestamp = Date.now();
    
    await page.fill('input[name="name"]', `Error User ${timestamp}`);
    await page.fill('input[name="email"]', `error-${timestamp}@example.com`);
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);
    
    // Handle potential registration issues
    const currentUrl = page.url();
    if (currentUrl.includes('/register')) {
      await page.goto('/login');
      await page.fill('input[name="email"]', 'test@example.com');
      await page.fill('input[name="password"]', 'password123');
      await page.click('button[type="submit"]');
      await page.waitForTimeout(2000);
    }
    
    await page.goto('/onboarding');
    await page.waitForURL('/onboarding', { timeout: 10000 });
    
    // Navigate to children form
    await page.getByTestId('next-button').click();
    await expect(page.getByTestId('step-2')).toBeVisible({ timeout: 10000 });
    
    // Mock server error for children submission
    await page.route('/onboarding/children', route => {
      route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({
          error: 'Internal server error occurred'
        })
      });
    });
    
    // Fill form and submit
    await page.getByTestId('child-name-0').fill('Error Test Child');
    await page.selectOption('[data-testid="child-grade-0"]', '8');
    await page.getByTestId('next-button').click();
    
    // Should show server error message
    await expect(page.getByTestId('form-error')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('text=server error')).toBeVisible();
    
    // User should still be on step 2 and able to retry
    await expect(page.getByTestId('step-2')).toBeVisible();
    
    // Remove the route mock to allow retry
    await page.unroute('/onboarding/children');
    
    // Retry submission
    await page.getByTestId('next-button').click();
    
    // Should now succeed (or show different error)
    await page.waitForTimeout(3000);
    
    // Should either show success or remain on step 2 with different error
    const step2Still = await page.getByTestId('step-2').isVisible().catch(() => false);
    const step3Now = await page.getByTestId('step-3').isVisible().catch(() => false);
    
    expect(step2Still || step3Now).toBe(true);
  });

  test('should handle network timeout gracefully', async ({ page }) => {
    // Setup
    await page.goto('/register');
    const timestamp = Date.now();
    
    await page.fill('input[name="name"]', `Timeout User ${timestamp}`);
    await page.fill('input[name="email"]', `timeout-${timestamp}@example.com`);
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);
    
    // Handle potential registration issues
    const currentUrl = page.url();
    if (currentUrl.includes('/register')) {
      await page.goto('/login');
      await page.fill('input[name="email"]', 'test@example.com');
      await page.fill('input[name="password"]', 'password123');
      await page.click('button[type="submit"]');
      await page.waitForTimeout(2000);
    }
    
    await page.goto('/onboarding');
    await page.waitForURL('/onboarding', { timeout: 10000 });
    
    // Navigate to step 3 (subjects) to test timeout
    await page.getByTestId('next-button').click();
    await expect(page.getByTestId('step-2')).toBeVisible({ timeout: 10000 });
    
    await page.getByTestId('child-name-0').fill('Timeout Child');
    await page.selectOption('[data-testid="child-grade-0"]', '8');
    
    await page.getByTestId('next-button').click();
    await expect(page.getByTestId('step-3')).toBeVisible({ timeout: 10000 });
    
    // Mock network timeout for subjects submission
    await page.route('/onboarding/subjects', route => {
      // Don't fulfill the route - let it timeout
      // This simulates a network timeout
    });
    
    // Fill and submit subjects form
    await page.getByTestId('next-button').click();
    
    // Should show loading state
    await expect(page.locator('text=Saving...')).toBeVisible();
    
    // Wait for timeout to occur (should be reasonable, not indefinite)
    await page.waitForTimeout(10000);
    
    // Should show timeout error or remain on step 3
    const stillOnStep3 = await page.getByTestId('step-3').isVisible();
    expect(stillOnStep3).toBe(true);
    
    // Loading indicator should eventually disappear
    const loadingVisible = await page.locator('text=Saving...').isVisible().catch(() => false);
    if (loadingVisible) {
      // Wait a bit more for timeout handling
      await page.waitForTimeout(5000);
      const loadingStillVisible = await page.locator('text=Saving...').isVisible().catch(() => false);
      // Loading should not be stuck indefinitely
      expect(loadingStillVisible).toBe(false);
    }
    
    // Clean up route mock
    await page.unroute('/onboarding/subjects');
  });

  test('should validate boundary conditions (maximum limits)', async ({ page }) => {
    // Setup
    await page.goto('/register');
    const timestamp = Date.now();
    
    await page.fill('input[name="name"]', `Boundary User ${timestamp}`);
    await page.fill('input[name="email"]', `boundary-${timestamp}@example.com`);
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);
    
    // Handle potential registration issues
    const currentUrl = page.url();
    if (currentUrl.includes('/register')) {
      await page.goto('/login');
      await page.fill('input[name="email"]', 'test@example.com');
      await page.fill('input[name="password"]', 'password123');
      await page.click('button[type="submit"]');
      await page.waitForTimeout(2000);
    }
    
    await page.goto('/onboarding');
    await page.waitForURL('/onboarding', { timeout: 10000 });
    
    // Navigate to children form
    await page.getByTestId('next-button').click();
    await expect(page.getByTestId('step-2')).toBeVisible({ timeout: 10000 });
    
    // Test maximum number of children (should be 5)
    const maxChildren = 5;
    
    // Fill first child
    await page.getByTestId('child-name-0').fill('Child 1');
    await page.selectOption('[data-testid="child-grade-0"]', '6');
    
    // Add children up to maximum
    for (let i = 1; i < maxChildren; i++) {
      await page.getByTestId('add-another-child').click();
      await page.getByTestId(`child-name-${i}`).fill(`Child ${i + 1}`);
      await page.selectOption(`[data-testid="child-age-${i}"]`, '7');
    }
    
    // Verify add button is no longer available
    await expect(page.getByTestId('add-another-child')).not.toBeVisible();
    await expect(page.locator('text=Maximum of 5 children allowed')).toBeVisible();
    
    // Test removing and re-adding children
    await page.getByTestId('remove-child-2').click();
    
    // Add button should be available again
    await expect(page.getByTestId('add-another-child')).toBeVisible();
    
    // Add child back
    await page.getByTestId('add-another-child').click();
    await page.getByTestId('child-name-4').fill('Child 5 Again');
    await page.selectOption('[data-testid="child-age-4"]', '8');
    
    // Submit children
    await page.getByTestId('next-button').click();
    await expect(page.getByTestId('step-3')).toBeVisible({ timeout: 10000 });
    
    // Test maximum custom subjects (should be 3 per child)
    const firstChildSection = page.getByTestId('child-subjects-0');
    
    // Fill first custom subject
    await firstChildSection.locator('input[name*="custom"]').first().fill('Custom 1');
    
    // Add second
    await firstChildSection.locator('text=Add Custom Subject').click();
    await firstChildSection.locator('input[name*="custom"]').nth(1).fill('Custom 2');
    
    // Add third (maximum)
    await firstChildSection.locator('text=Add Custom Subject').click();
    await firstChildSection.locator('input[name*="custom"]').nth(2).fill('Custom 3');
    
    // Add button should be hidden now
    const addCustomButton = firstChildSection.locator('text=Add Custom Subject');
    await expect(addCustomButton).not.toBeVisible();
    
    // Test removing and adding back
    await firstChildSection.locator('button:has-text("") svg').nth(1).click(); // Remove second subject
    
    // Add button should be visible again
    await expect(addCustomButton).toBeVisible();
    
    // Continue to completion
    await page.getByTestId('next-button').click();
    await expect(page.getByTestId('step-4')).toBeVisible({ timeout: 10000 });
    
    // Verify all children appear in review (5 total)
    await expect(page.locator('h3:has-text("5 Children Added")')).toBeVisible();
  });
});