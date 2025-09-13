import { test, expect } from '@playwright/test';

test.describe('Onboarding Review and Completion', () => {
    test.beforeEach(async ({ page }) => {
        // Try to register a test user, but handle database errors gracefully
        await page.goto('/register');
        const timestamp = Date.now();
        const testEmail = `test-onboarding-${timestamp}@example.com`;
        const testName = `Test Parent ${timestamp}`;
        
        await page.fill('input[name="name"]', testName);
        await page.fill('input[name="email"]', testEmail);
        await page.fill('input[name="password"]', 'password123');
        await page.fill('input[name="password_confirmation"]', 'password123');
        await page.click('button[type="submit"]');
        
        // Wait a bit for the registration attempt
        await page.waitForTimeout(2000);
        
        // Check if we got redirected to onboarding or stayed on register with error
        const currentUrl = page.url();
        if (currentUrl.includes('/register')) {
            // Registration failed, try to login with a known test user
            await page.goto('/login');
            await page.fill('input[name="email"]', 'test@example.com');
            await page.fill('input[name="password"]', 'password123');
            await page.click('button[type="submit"]');
            await page.waitForTimeout(2000);
        }
        
        // If we're still not authenticated, navigate directly to onboarding
        // (assuming middleware allows access for testing purposes)
        const finalUrl = page.url();
        if (!finalUrl.includes('/onboarding') && !finalUrl.includes('/dashboard')) {
            await page.goto('/onboarding');
        }
        
        // Wait for onboarding page to load
        await page.waitForURL('/onboarding', { timeout: 10000 });
        await expect(page.getByTestId('step-1')).toBeVisible();
    });

    test('should complete full onboarding wizard and show review step', async ({ page }) => {
        // Step 1: Welcome - Click Next
        await expect(page.locator('[data-testid="step-1"]')).toBeVisible();
        await page.click('[data-testid="next-button"]');
        
        // Step 2: Add a child
        await expect(page.locator('[data-testid="step-2"]')).toBeVisible();
        
        // Fill in child information
        await page.fill('[data-testid="child-name-0"]', 'Emma');
        await page.selectOption('[data-testid="child-grade-0"]', '8');
        await page.selectOption('[data-testid="child-independence-0"]', '2');
        
        // Submit children form
        await page.click('[data-testid="next-button"]');
        
        // Wait for success message and transition to step 3
        await expect(page.locator('[data-testid="form-success"]')).toBeVisible();
        await expect(page.locator('[data-testid="step-3"]')).toBeVisible({ timeout: 10000 });
        
        // Step 3: Select subjects
        // Wait for child subjects section to be visible
        await expect(page.locator('[data-testid="child-subjects-0"]')).toBeVisible();
        
        // Keep some default subjects (they should be pre-selected)
        // Verify Reading/Language Arts and Mathematics are selected by default
        const mathCheckbox = page.locator('input[value="Mathematics"]');
        const readingCheckbox = page.locator('input[value="Reading/Language Arts"]');
        await expect(mathCheckbox).toBeChecked();
        await expect(readingCheckbox).toBeChecked();
        
        // Add a custom subject
        await page.fill('input[placeholder*="Custom subject 1"]', 'Piano Lessons');
        
        // Submit subjects form
        await page.click('[data-testid="next-button"]');
        
        // Wait for success message and transition to step 4
        await expect(page.locator('[data-testid="subjects-form-success"]')).toBeVisible();
        await expect(page.locator('[data-testid="step-4"]')).toBeVisible({ timeout: 10000 });
        
        // Step 4: Review and Completion
        await expect(page.locator('h2:has-text("🎉 Your Homeschool is Ready!")')).toBeVisible();
        
        // Verify children summary shows correct information
        await expect(page.locator('h3:has-text("1 Child Added")')).toBeVisible();
        await expect(page.getByTestId('step-4').locator('p:has-text("Emma")')).toBeVisible();
        await expect(page.getByTestId('step-4').locator('text=8 years old')).toBeVisible();
        await expect(page.getByTestId('step-4').locator('text=Basic')).toBeVisible();
        
        // Verify subjects summary shows created subjects (should be at least 3: Math, Reading, Piano)
        await expect(page.locator('h3:has-text("Subjects Created")')).toBeVisible();
        await expect(page.getByTestId('step-4').getByText('Mathematics')).toBeVisible();
        await expect(page.getByTestId('step-4').getByText('Reading/Language Arts')).toBeVisible();
        await expect(page.getByTestId('step-4').getByText('Piano Lessons')).toBeVisible();
        
        // Verify "What's Next" section is visible
        await expect(page.locator('h3:has-text("What\'s Next?")')).toBeVisible();
        await expect(page.locator('text=Add units and topics to each subject')).toBeVisible();
        await expect(page.locator('text=Set up your weekly schedule in the Planning Board')).toBeVisible();
        
        // Verify navigation buttons
        await expect(page.locator('[data-testid="review-back-button"]')).toBeVisible();
        await expect(page.locator('[data-testid="complete-onboarding-button"]')).toBeVisible();
    });

    test('should allow back navigation from review step to edit children', async ({ page }) => {
        // Complete first 3 steps quickly
        await page.click('[data-testid="next-button"]'); // Step 1 -> 2
        
        // Add child
        await page.fill('[data-testid="child-name-0"]', 'John');
        await page.selectOption('[data-testid="child-grade-0"]', '10');
        await page.click('[data-testid="next-button"]'); // Step 2 -> 3
        
        // Wait for step 3 and submit with default subjects
        await expect(page.locator('[data-testid="step-3"]')).toBeVisible({ timeout: 10000 });
        await page.click('[data-testid="next-button"]'); // Step 3 -> 4
        
        // Wait for review step
        await expect(page.locator('[data-testid="step-4"]')).toBeVisible({ timeout: 10000 });
        
        // Click back button
        await page.click('[data-testid="review-back-button"]');
        
        // Should go back to step 3
        await expect(page.locator('[data-testid="step-3"]')).toBeVisible();
        
        // Click previous again to get to step 2
        await page.click('[data-testid="previous-button"]');
        await expect(page.locator('[data-testid="step-2"]')).toBeVisible();
        
        // Edit the child's name
        await page.fill('[data-testid="child-name-0"]', 'Johnny Modified');
        
        // Navigate back to review
        await page.click('[data-testid="next-button"]'); // Step 2 -> 3
        await expect(page.locator('[data-testid="step-3"]')).toBeVisible({ timeout: 10000 });
        await page.click('[data-testid="next-button"]'); // Step 3 -> 4
        
        // Verify the modified name appears in review
        await expect(page.locator('[data-testid="step-4"]')).toBeVisible({ timeout: 10000 });
        await expect(page.getByTestId('step-4').locator('p:has-text("Johnny Modified")')).toBeVisible();
    });

    test('should complete onboarding and redirect to dashboard', async ({ page }) => {
        // Quickly complete first 3 steps with minimal data
        await page.click('[data-testid="next-button"]'); // Step 1 -> 2
        
        // Add child
        await page.fill('[data-testid="child-name-0"]', 'Test Child');
        await page.selectOption('[data-testid="child-grade-0"]', '7');
        await page.click('[data-testid="next-button"]'); // Step 2 -> 3
        
        // Wait for step 3 and submit with default subjects
        await expect(page.locator('[data-testid="step-3"]')).toBeVisible({ timeout: 10000 });
        await page.click('[data-testid="next-button"]'); // Step 3 -> 4
        
        // Wait for review step
        await expect(page.locator('[data-testid="step-4"]')).toBeVisible({ timeout: 10000 });
        
        // Click complete setup button
        await page.click('[data-testid="complete-onboarding-button"]');
        
        // Verify button shows loading state
        await expect(page.locator('text=Completing Setup...')).toBeVisible();
        
        // Wait for success toast message
        await expect(page.locator('text=🎉 Setup complete! Welcome to your homeschool hub!')).toBeVisible({ timeout: 10000 });
        
        // Should redirect to dashboard
        await expect(page).toHaveURL(/.*dashboard/, { timeout: 10000 });
        
        // Verify dashboard loads and shows the created child
        await expect(page.getByRole('heading', { name: 'Test Child' })).toBeVisible({ timeout: 10000 });
    });

    test('should not show onboarding again after completion', async ({ page }) => {
        // Complete onboarding
        await page.click('[data-testid="next-button"]'); // Step 1 -> 2
        
        await page.fill('[data-testid="child-name-0"]', 'Final Test Child');
        await page.selectOption('[data-testid="child-grade-0"]', '6');
        await page.click('[data-testid="next-button"]'); // Step 2 -> 3
        
        await expect(page.locator('[data-testid="step-3"]')).toBeVisible({ timeout: 10000 });
        await page.click('[data-testid="next-button"]'); // Step 3 -> 4
        
        await expect(page.locator('[data-testid="step-4"]')).toBeVisible({ timeout: 10000 });
        await page.click('[data-testid="complete-onboarding-button"]');
        
        // Wait for redirect to dashboard
        await expect(page).toHaveURL(/.*dashboard/, { timeout: 10000 });
        
        // Try to navigate to onboarding directly
        await page.goto('/onboarding');
        
        // Should redirect to dashboard instead of showing onboarding
        await expect(page).toHaveURL(/.*dashboard/);
    });

    test('should handle multiple children in review summary', async ({ page }) => {
        // Step 1: Welcome
        await page.click('[data-testid="next-button"]');
        
        // Step 2: Add multiple children
        await page.fill('[data-testid="child-name-0"]', 'Alice');
        await page.selectOption('[data-testid="child-grade-0"]', '9');
        await page.selectOption('[data-testid="child-independence-0"]', '3');
        
        // Add second child
        await page.click('[data-testid="add-another-child"]');
        await page.fill('[data-testid="child-name-1"]', 'Bob');
        await page.selectOption('[data-testid="child-grade-1"]', '12');
        await page.selectOption('[data-testid="child-independence-1"]', '4');
        
        await page.click('[data-testid="next-button"]'); // Step 2 -> 3
        
        // Step 3: Set up subjects for both children
        await expect(page.locator('[data-testid="step-3"]')).toBeVisible({ timeout: 10000 });
        
        // Both children should be visible with different recommended subjects
        await expect(page.getByTestId('subjects-form').locator('[data-child-name="Alice"]')).toBeVisible();
        await expect(page.getByTestId('subjects-form').locator('[data-child-name="Bob"]')).toBeVisible();
        
        await page.click('[data-testid="next-button"]'); // Step 3 -> 4
        
        // Step 4: Verify review shows both children
        await expect(page.locator('[data-testid="step-4"]')).toBeVisible({ timeout: 10000 });
        
        // Should show "2 Children Added"
        await expect(page.locator('h3:has-text("2 Children Added")')).toBeVisible();
        
        // Both children should be listed
        await expect(page.getByTestId('step-4').locator('p:has-text("Alice")')).toBeVisible();
        await expect(page.getByTestId('step-4').locator('text=9 years old')).toBeVisible();
        await expect(page.getByTestId('step-4').locator('text=Intermediate')).toBeVisible();
        
        await expect(page.getByTestId('step-4').locator('p:has-text("Bob")')).toBeVisible();
        await expect(page.getByTestId('step-4').locator('text=12 years old')).toBeVisible();
        await expect(page.getByTestId('step-4').locator('text=Advanced')).toBeVisible();
        
        // Should show subjects for both children
        await expect(page.getByRole('heading', { name: 'Alice\'s Subjects' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Bob\'s Subjects' })).toBeVisible();
    });

    test('should show correct progress indicator states', async ({ page }) => {
        // Initially step 1 should be active, others inactive
        await expect(page.getByTestId('wizard-progress').locator('.bg-blue-600').first()).toBeVisible();
        await expect(page.getByTestId('wizard-progress').locator('.border-gray-300').first()).toBeVisible();
        
        // Move to step 2
        await page.click('[data-testid="next-button"]');
        
        // Steps 1-2 should be active, verify by counting active steps
        const activeSteps = page.getByTestId('wizard-progress').locator('.bg-blue-600');
        await expect(activeSteps).toHaveCount(2);
        
        // Complete child setup
        await page.fill('[data-testid="child-name-0"]', 'Progress Test');
        await page.selectOption('[data-testid="child-grade-0"]', '5');
        await page.click('[data-testid="next-button"]');
        
        // Move to step 3 - all previous steps should be active
        await expect(page.locator('[data-testid="step-3"]')).toBeVisible({ timeout: 10000 });
        
        // Move to step 4 (review)
        await page.click('[data-testid="next-button"]');
        await expect(page.locator('[data-testid="step-4"]')).toBeVisible({ timeout: 10000 });
        
        // All 4 steps should now be active/completed
        const allActiveSteps = page.getByTestId('wizard-progress').locator('.bg-blue-600');
        await expect(allActiveSteps).toHaveCount(4);
    });
});