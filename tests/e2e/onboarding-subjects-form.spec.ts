import { test, expect } from '@playwright/test';

test.describe('Onboarding Subjects Form', () => {
  test.beforeEach(async ({ page }) => {
    // Register a new test user and navigate to onboarding
    await page.goto('/register');
    
    const email = `test${Date.now()}@example.com`;
    const password = 'password123';
    
    await page.fill('#name', `Test User ${Date.now()}`);
    await page.fill('#email', email);
    await page.fill('#password', password);
    await page.fill('#password_confirmation', password);
    await page.click('button[type="submit"]');
    
    // Should be redirected to onboarding
    await page.waitForURL('/onboarding');
    
    // Complete Step 1 (Welcome)
    await expect(page.locator('[data-testid="step-1"]')).toBeVisible();
    await page.click('[data-testid="next-button"]');
    
    // Complete Step 2 (Children) - Add one child
    await expect(page.locator('[data-testid="step-2"]')).toBeVisible();
    await page.fill('[data-testid="child-name-0"]', 'Test Child');
    await page.selectOption('[data-testid="child-age-0"]', '8');
    await page.selectOption('[data-testid="child-independence-0"]', '2');
    
    // Submit children and wait for step 3
    await page.click('[data-testid="next-button"]');
    await expect(page.locator('[data-testid="step-3"]')).toBeVisible();
  });

  test('should display subjects form for each child from Step 2', async ({ page }) => {
    await expect(page.locator('[data-testid="subjects-form"]')).toBeVisible();
    await expect(page.locator('[data-testid="child-subjects-0"]')).toBeVisible();
    
    // Check that child information is displayed
    await expect(page.locator('text=Test Child')).toBeVisible();
    await expect(page.locator('text=8 years old')).toBeVisible();
  });

  test('should auto-select grade level based on age', async ({ page }) => {
    // 8-year-old should get Elementary (K-5)
    await expect(page.locator('text=Elementary (K-5)')).toBeVisible();
    
    // Should see elementary subjects pre-selected
    const recommendedSection = page.locator('text=Recommended Subjects').locator('..');
    await expect(recommendedSection.locator('text=Reading/Language Arts')).toBeVisible();
    await expect(recommendedSection.locator('text=Mathematics')).toBeVisible();
    await expect(recommendedSection.locator('text=Science')).toBeVisible();
  });

  test('should toggle subject selection on/off', async ({ page }) => {
    // Find Mathematics checkbox (should be pre-selected)
    const mathCheckbox = page.locator('input[type="checkbox"][value*="Mathematics"]');
    await expect(mathCheckbox).toBeChecked();
    
    // Uncheck Mathematics
    await mathCheckbox.uncheck();
    await expect(mathCheckbox).not.toBeChecked();
    
    // Check it again
    await mathCheckbox.check();
    await expect(mathCheckbox).toBeChecked();
  });

  test('should add custom subjects', async ({ page }) => {
    // Check that we start with one empty custom subject field
    const customInputs = page.locator('input[name*="custom"]');
    await expect(customInputs).toHaveCount(1);
    
    // Add a custom subject
    await customInputs.first().fill('Cooking');
    
    // Click Add Custom Subject button
    await page.click('text=Add Custom Subject');
    
    // Should now have 2 custom subject fields
    await expect(page.locator('input[name*="custom"]')).toHaveCount(2);
    
    // Fill the second one
    await page.locator('input[name*="custom"]').nth(1).fill('Gardening');
    
    // Add one more
    await page.click('text=Add Custom Subject');
    await expect(page.locator('input[name*="custom"]')).toHaveCount(3);
    
    // The button should be hidden now (max 3)
    await expect(page.locator('text=Add Custom Subject')).not.toBeVisible();
  });

  test('should remove custom subjects', async ({ page }) => {
    // Add two custom subjects first
    await page.locator('input[name*="custom"]').first().fill('Cooking');
    await page.click('text=Add Custom Subject');
    await page.locator('input[name*="custom"]').nth(1).fill('Gardening');
    
    await expect(page.locator('input[name*="custom"]')).toHaveCount(2);
    
    // Remove the second one
    await page.locator('button:has-text("") svg').nth(1).click(); // Delete button for second field
    
    await expect(page.locator('input[name*="custom"]')).toHaveCount(1);
    await expect(page.locator('input[name*="custom"]').first()).toHaveValue('Cooking');
  });

  test('should handle skipping subjects', async ({ page }) => {
    // Click skip subjects checkbox
    const skipCheckbox = page.locator('input[name*="skip"]');
    await skipCheckbox.check();
    
    // The Next button should be enabled even with no subjects selected
    const nextButton = page.locator('[data-testid="next-button"]');
    await expect(nextButton).not.toBeDisabled();
  });

  test('should successfully submit subjects and navigate to completion', async ({ page }) => {
    // Keep the default pre-selected subjects
    // Add one custom subject
    await page.locator('input[name*="custom"]').first().fill('Test Subject');
    
    // Submit the form
    await page.click('[data-testid="next-button"]');
    
    // Should see loading state
    await expect(page.locator('text=Saving...')).toBeVisible();
    
    // Should show success message
    await expect(page.locator('[data-testid="subjects-form-success"]')).toBeVisible();
    
    // Should eventually redirect to dashboard
    await page.waitForURL('/dashboard');
    await expect(page).toHaveURL('/dashboard');
  });

  test('should verify subjects are created in database', async ({ page }) => {
    // Keep default subjects and submit
    await page.click('[data-testid="next-button"]');
    
    // Wait for completion
    await page.waitForURL('/dashboard');
    
    // Navigate to subjects page to verify they were created
    await page.goto('/subjects');
    
    // Should see the created subjects
    await expect(page.locator('text=Reading/Language Arts')).toBeVisible();
    await expect(page.locator('text=Mathematics')).toBeVisible();
    await expect(page.locator('text=Science')).toBeVisible();
  });

  test('should work with multiple children of different ages', async ({ page }) => {
    // Go back to add another child
    await page.click('[data-testid="previous-button"]'); // Go back to Step 2
    
    // Add another child
    await page.click('[data-testid="add-another-child"]');
    await page.fill('[data-testid="child-name-1"]', 'Older Child');
    await page.selectOption('[data-testid="child-age-1"]', '15');
    await page.selectOption('[data-testid="child-independence-1"]', '3');
    
    // Go to subjects step
    await page.click('[data-testid="next-button"]');
    await expect(page.locator('[data-testid="step-3"]')).toBeVisible();
    
    // Should see both children
    await expect(page.locator('text=Test Child')).toBeVisible();
    await expect(page.locator('text=Older Child')).toBeVisible();
    
    // The 15-year-old should have High School subjects
    await expect(page.locator('text=High School (9-12)')).toBeVisible();
    
    // Both children should have their own subject sections
    await expect(page.locator('[data-testid="child-subjects-0"]')).toBeVisible();
    await expect(page.locator('[data-testid="child-subjects-1"]')).toBeVisible();
    
    // High school child should have different subjects
    const higherMathCheckbox = page.locator('input[type="checkbox"][value*="Algebra"]');
    await expect(higherMathCheckbox).toBeVisible();
  });

  test('should handle form validation', async ({ page }) => {
    // Uncheck all recommended subjects
    const checkboxes = page.locator('input[type="checkbox"]:not([name*="skip"])');
    const count = await checkboxes.count();
    
    for (let i = 0; i < count; i++) {
      await checkboxes.nth(i).uncheck();
    }
    
    // Try to submit without any subjects or skip
    const nextButton = page.locator('[data-testid="next-button"]');
    await expect(nextButton).toBeDisabled();
    
    // Check at least one subject
    await page.locator('input[type="checkbox"][value*="Mathematics"]').check();
    await expect(nextButton).not.toBeDisabled();
  });

  test('should expand/collapse child sections', async ({ page }) => {
    // Add a second child to test expansion
    await page.click('[data-testid="previous-button"]');
    await page.click('[data-testid="add-another-child"]');
    await page.fill('[data-testid="child-name-1"]', 'Second Child');
    await page.selectOption('[data-testid="child-age-1"]', '12');
    await page.click('[data-testid="next-button"]');
    
    // First child should be expanded by default
    await expect(page.locator('[data-testid="child-subjects-0"]')).toBeVisible();
    
    // Second child should be collapsed by default
    await expect(page.locator('[data-testid="child-subjects-1"]')).not.toBeVisible();
    
    // Click to expand second child
    await page.locator('button:has-text("") svg').first().click(); // Collapse/expand button
    
    // Now second child should be visible
    await expect(page.locator('[data-testid="child-subjects-1"]')).toBeVisible();
  });
});