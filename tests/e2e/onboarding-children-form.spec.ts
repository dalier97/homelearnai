import { test, expect } from '@playwright/test';
import { OnboardingHelper } from './helpers/onboarding-helpers';
import { ModalHelper, ElementHelper } from './helpers/modal-helpers';
import { KidsModeHelper } from './helpers/kids-mode-helpers';

test.describe('Onboarding Children Form', () => {
    let onboardingHelper: OnboardingHelper;
    let modalHelper: ModalHelper;
    let elementHelper: ElementHelper;
    let kidsModeHelper: KidsModeHelper;

    test.beforeEach(async ({ page }) => {
        onboardingHelper = new OnboardingHelper(page);
        modalHelper = new ModalHelper(page);
        elementHelper = new ElementHelper(page);
        kidsModeHelper = new KidsModeHelper(page);
        
        // Ensure we're not in kids mode at start of each test
        await kidsModeHelper.resetKidsMode();
        
        // Start fresh for each test and wait for page to be ready
        await page.goto('/');
        await elementHelper.waitForPageReady();
        
        // Register and authenticate user, navigate to onboarding
        await onboardingHelper.registerAndStartOnboarding();
        
        // Navigate to step 2 (children form)
        await page.getByTestId('next-button').click();
        await expect(page.getByTestId('step-2')).toBeVisible();
    });

    test('displays initial empty child form with proper fields', async ({ page }) => {
        // Verify form is visible
        await expect(page.getByTestId('children-form')).toBeVisible();
        
        // Verify one child form is present
        await expect(page.getByTestId('child-form-0')).toBeVisible();
        
        // Verify all required fields are present
        await expect(page.getByTestId('child-name-0')).toBeVisible();
        await expect(page.getByTestId('child-age-0')).toBeVisible();
        await expect(page.getByTestId('child-independence-0')).toBeVisible();
        
        // Verify remove button is NOT visible for single child
        await expect(page.getByTestId('remove-child-0')).not.toBeVisible();
        
        // Verify add another child button is visible
        await expect(page.getByTestId('add-another-child')).toBeVisible();
    });

    test('can add and remove multiple children', async ({ page }) => {
        // Add a second child
        await page.getByTestId('add-another-child').click();
        
        // Verify two child forms are present
        await expect(page.getByTestId('child-form-0')).toBeVisible();
        await expect(page.getByTestId('child-form-1')).toBeVisible();
        
        // Verify remove buttons are visible for both children
        await expect(page.getByTestId('remove-child-0')).toBeVisible();
        await expect(page.getByTestId('remove-child-1')).toBeVisible();
        
        // Add a third child
        await page.getByTestId('add-another-child').click();
        await expect(page.getByTestId('child-form-2')).toBeVisible();
        
        // Remove the second child (index 1)
        await page.getByTestId('remove-child-1').click();
        
        // Verify we have only two children now
        await expect(page.getByTestId('child-form-0')).toBeVisible();
        await expect(page.getByTestId('child-form-1')).toBeVisible(); // This was formerly child-form-2
        await expect(page.getByTestId('child-form-2')).not.toBeVisible();
    });

    test('enforces maximum of 5 children', async ({ page }) => {
        // Add children up to the maximum (5 total - 1 already present = 4 more)
        for (let i = 0; i < 4; i++) {
            await page.getByTestId('add-another-child').click();
        }
        
        // Verify all 5 child forms are present
        for (let i = 0; i < 5; i++) {
            await expect(page.getByTestId(`child-form-${i}`)).toBeVisible();
        }
        
        // Verify add button is no longer visible
        await expect(page.getByTestId('add-another-child')).not.toBeVisible();
        
        // Verify maximum message is shown
        await expect(page.locator('text=Maximum of 5 children allowed')).toBeVisible();
    });

    test('validates required fields before allowing submission', async ({ page }) => {
        // Try to proceed without filling any fields
        await page.getByTestId('next-button').click();
        
        // Should show validation errors
        await expect(page.getByTestId('form-error')).toBeVisible();
        await expect(page.locator('text=Child name is required')).toBeVisible();
        await expect(page.locator('text=Child age is required')).toBeVisible();
        
        // Fill name but not age
        await page.getByTestId('child-name-0').fill('Test Child');
        await page.getByTestId('next-button').click();
        
        // Should still show age validation error
        await expect(page.locator('text=Child age is required')).toBeVisible();
        
        // Fill age as well
        await page.getByTestId('child-age-0').selectOption('8');
        
        // Now should not show validation errors for this child
        await expect(page.locator('text=Child name is required')).not.toBeVisible();
    });

    test('successfully submits single child with all fields', async ({ page }) => {
        // Fill out the child form
        await page.getByTestId('child-name-0').fill('Emma Thompson');
        await page.getByTestId('child-age-0').selectOption('10');
        await page.getByTestId('child-independence-0').selectOption('2');
        
        // Submit the form
        await page.getByTestId('next-button').click();
        
        // Should show success message
        await expect(page.getByTestId('form-success')).toBeVisible();
        
        // Should automatically proceed to step 3 after success
        await expect(page.getByTestId('step-3')).toBeVisible({ timeout: 3000 });
    });

    test('successfully submits multiple children', async ({ page }) => {
        // Add two more children (3 total)
        await page.getByTestId('add-another-child').click();
        await page.getByTestId('add-another-child').click();
        
        // Fill out first child
        await page.getByTestId('child-name-0').fill('Alice Johnson');
        await page.getByTestId('child-age-0').selectOption('12');
        await page.getByTestId('child-independence-0').selectOption('3');
        
        // Fill out second child
        await page.getByTestId('child-name-1').fill('Bob Johnson');
        await page.getByTestId('child-age-1').selectOption('8');
        await page.getByTestId('child-independence-1').selectOption('1');
        
        // Fill out third child
        await page.getByTestId('child-name-2').fill('Charlie Johnson');
        await page.getByTestId('child-age-2').selectOption('15');
        await page.getByTestId('child-independence-2').selectOption('4');
        
        // Submit the form
        await page.getByTestId('next-button').click();
        
        // Should show success message
        await expect(page.getByTestId('form-success')).toBeVisible();
        
        // Should automatically proceed to step 3 after success
        await expect(page.getByTestId('step-3')).toBeVisible({ timeout: 3000 });
    });

    test('shows loading state during submission', async ({ page }) => {
        // Fill out the form
        await page.getByTestId('child-name-0').fill('Test Child');
        await page.getByTestId('child-age-0').selectOption('7');
        
        // Submit and check for loading state
        await page.getByTestId('next-button').click();
        
        // Should show saving text (might be brief)
        await expect(page.locator('text=Saving...')).toBeVisible({ timeout: 1000 });
    });

    test('can navigate back from step 2 to step 1', async ({ page }) => {
        // We should be on step 2
        await expect(page.getByTestId('step-2')).toBeVisible();
        
        // Click previous button
        await page.getByTestId('previous-button').click();
        
        // Should be back on step 1
        await expect(page.getByTestId('step-1')).toBeVisible();
        
        // Navigate forward again
        await page.getByTestId('next-button').click();
        await expect(page.getByTestId('step-2')).toBeVisible();
    });

    test('preserves form data when navigating between steps', async ({ page }) => {
        // Fill out child data
        await page.getByTestId('child-name-0').fill('Preserved Child');
        await page.getByTestId('child-age-0').selectOption('11');
        await page.getByTestId('child-independence-0').selectOption('3');
        
        // Add a second child
        await page.getByTestId('add-another-child').click();
        await page.getByTestId('child-name-1').fill('Second Child');
        await page.getByTestId('child-age-1').selectOption('9');
        
        // Navigate back to step 1
        await page.getByTestId('previous-button').click();
        await expect(page.getByTestId('step-1')).toBeVisible();
        
        // Navigate back to step 2
        await page.getByTestId('next-button').click();
        await expect(page.getByTestId('step-2')).toBeVisible();
        
        // Verify data is still there
        await expect(page.getByTestId('child-name-0')).toHaveValue('Preserved Child');
        await expect(page.getByTestId('child-age-0')).toHaveValue('11');
        await expect(page.getByTestId('child-independence-0')).toHaveValue('3');
        
        await expect(page.getByTestId('child-name-1')).toHaveValue('Second Child');
        await expect(page.getByTestId('child-age-1')).toHaveValue('9');
    });

    test('handles server validation errors gracefully', async ({ page }) => {
        // Mock server error
        await page.route('/onboarding/children', route => {
            route.fulfill({
                status: 422,
                contentType: 'application/json',
                body: JSON.stringify({
                    error: 'Server validation error'
                })
            });
        });
        
        // Fill out form
        await page.getByTestId('child-name-0').fill('Test Child');
        await page.getByTestId('child-age-0').selectOption('8');
        
        // Submit form
        await page.getByTestId('next-button').click();
        
        // Should show server error message
        await expect(page.getByTestId('form-error')).toBeVisible();
        await expect(page.locator('text=Server validation error')).toBeVisible();
    });

    test('disables next button when no valid children are present', async ({ page }) => {
        // Next button should be disabled with empty form
        await expect(page.getByTestId('next-button')).toBeDisabled();
        
        // Fill only name (not age)
        await page.getByTestId('child-name-0').fill('Test');
        
        // Should still be disabled
        await expect(page.getByTestId('next-button')).toBeDisabled();
        
        // Fill age as well
        await page.getByTestId('child-age-0').selectOption('8');
        
        // Now should be enabled
        await expect(page.getByTestId('next-button')).not.toBeDisabled();
    });
});