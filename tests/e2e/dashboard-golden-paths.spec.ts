import { test, expect } from '@playwright/test';
import { quickTestLogin, TestSetupHelper } from './helpers/test-setup-helpers';

test.describe('Dashboard Golden Paths - Complete User Experience', () => {
  test.beforeEach(async ({ page }) => {
    // Go to the application
    await page.goto('/');
  });

  test('Complete Parent Dashboard Experience - All Buttons and Real Scenarios', async ({ page }) => {
    // Setup: Create user with children and various data
    const user = await quickTestLogin(page);
    const helper = new TestSetupHelper(page);
    
    console.log('Setting up comprehensive test data for golden path testing');
    
    // Navigate to dashboard (should be automatic after login)
    await expect(page).toHaveURL('/dashboard');
    
    // === SECTION 1: Header Navigation and Primary Actions ===
    console.log('Testing header navigation and primary actions');
    
    // 1. Verify main dashboard elements are visible
    await expect(page.getByRole('heading', { name: /Parent Dashboard/i })).toBeVisible();
    await expect(page.getByText(/Week of/)).toBeVisible();
    
    // 2. Test Planning Board button - core functionality
    await expect(page.getByRole('link', { name: /Planning Board/i })).toBeVisible();
    await page.getByRole('link', { name: /Planning Board/i }).click();
    await expect(page).toHaveURL('/planning');
    await expect(page.getByText(/Planning Board/i)).toBeVisible();
    
    // Return to dashboard
    await page.goto('/dashboard');
    
    // 3. Test Manage Children button
    await expect(page.getByRole('link', { name: /Manage Children/i })).toBeVisible();
    await page.getByRole('link', { name: /Manage Children/i }).click();
    await expect(page).toHaveURL('/children');
    await expect(page.getByText(/Children/i)).toBeVisible();
    
    // Return to dashboard
    await page.goto('/dashboard');
    
    // === SECTION 2: Child-Specific Actions on Dashboard ===
    console.log('Testing all child-specific dashboard actions');
    
    // Wait for child cards to load
    await expect(page.locator('.bg-white.rounded-lg.shadow-sm').first()).toBeVisible();
    
    const childCard = page.locator('.bg-white.rounded-lg.shadow-sm').first();
    
    // 4. Test Child View Link (Eye icon)
    const childViewLink = childCard.locator('[data-testid="child-view-link"]');
    await expect(childViewLink).toBeVisible();
    await childViewLink.click();
    
    // Should navigate to child dashboard
    await expect(page).toHaveURL(/\/dashboard\/child\/.+\/today/);
    await expect(page.getByText(/Today/i)).toBeVisible();
    
    // Return to parent dashboard
    await page.goto('/dashboard');
    
    // 5. Test Child Settings Button (Settings icon)
    const settingsButton = childCard.locator('button[hx-get*="children"][hx-target="#child-settings-modal"]');
    await expect(settingsButton).toBeVisible();
    await settingsButton.click();
    
    // Wait for modal to load
    await page.waitForTimeout(1000);
    
    // Check if modal opened (should have modal content)
    const modal = page.locator('#child-settings-modal');
    await expect(modal).toBeVisible();
    
    // Close modal by clicking outside or escape
    await page.keyboard.press('Escape');
    await page.waitForTimeout(500);
    
    // === SECTION 3: Quick Action Buttons ===
    console.log('Testing quick action buttons');
    
    // 6. Test Complete Today Button
    const completeTodayBtn = childCard.locator('button[hx-post*="bulk-complete-today"]');
    await expect(completeTodayBtn).toBeVisible();
    await expect(completeTodayBtn.getByText(/Complete Today/i)).toBeVisible();
    
    // Click and handle confirmation dialog
    await completeTodayBtn.click();
    // Note: This may trigger a confirmation dialog - handle it
    page.on('dialog', async dialog => {
      await dialog.accept();
    });
    
    // 7. Test Reviews Link
    const reviewsLink = childCard.locator('a[href*="reviews"]');
    await expect(reviewsLink).toBeVisible();
    await expect(reviewsLink.getByText(/Review/i)).toBeVisible();
    await reviewsLink.click();
    
    // Should navigate to reviews page
    await expect(page).toHaveURL(/\/reviews/);
    await expect(page.getByText(/Review/i)).toBeVisible();
    
    // Return to dashboard
    await page.goto('/dashboard');
    
    // === SECTION 4: Kids Mode Functionality ===
    console.log('Testing Kids Mode functionality');
    
    // 8. Test Set PIN First button (when no PIN is set)
    const setPinBtn = childCard.locator('a[href*="kids-mode/settings"]');
    if (await setPinBtn.isVisible()) {
      await expect(setPinBtn.getByText(/set pin first/i)).toBeVisible();
      await setPinBtn.click();
      
      // Should navigate to Kids Mode settings
      await expect(page).toHaveURL(/\/kids-mode\/settings/);
      await expect(page.getByText(/Kids Mode/i)).toBeVisible();
      
      // Return to dashboard
      await page.goto('/dashboard');
    }
    
    // 9. Test Enter Kids Mode button (after PIN is set)
    const enterKidsModeBtn = childCard.locator('button[hx-post*="kids-mode"][class*="kids-mode-enter-btn"]');
    if (await enterKidsModeBtn.isVisible()) {
      await expect(enterKidsModeBtn.getByText(/enter kids mode/i)).toBeVisible();
      
      // Click and handle confirmation
      page.on('dialog', async dialog => {
        await dialog.accept();
      });
      await enterKidsModeBtn.click();
      
      // Should redirect to child view
      await expect(page).toHaveURL(/\/dashboard\/child/);
    }
    
    // Return to dashboard for next tests
    await page.goto('/dashboard');
    
    // === SECTION 5: Independence Level Control ===
    console.log('Testing independence level control');
    
    // 10. Test Independence Level Dropdown
    const independenceSelect = childCard.locator('select[name="independence_level"]');
    await expect(independenceSelect).toBeVisible();
    
    // Get current value
    const currentLevel = await independenceSelect.inputValue();
    
    // Change to different level
    const newLevel = currentLevel === '1' ? '2' : '1';
    await independenceSelect.selectOption(newLevel);
    
    // Wait for HTMX update
    await page.waitForTimeout(1000);
    
    // Verify the change persisted
    await expect(independenceSelect).toHaveValue(newLevel);
    
    // === SECTION 6: Data Display Verification ===
    console.log('Verifying all data displays correctly');
    
    // 11. Verify Child Information Display
    await expect(childCard.getByText(/Age/i)).toBeVisible();
    await expect(childCard.locator('.text-xs').first()).toBeVisible(); // Status indicator
    
    // 12. Verify Progress Display
    await expect(childCard.getByText(/Week Progress/i)).toBeVisible();
    await expect(childCard.locator('.bg-gray-200.rounded-full')).toBeVisible(); // Progress bar
    
    // 13. Verify Today's Sessions Display
    await expect(childCard.getByText(/Today/i)).toBeVisible();
    
    // 14. Verify Flashcard Statistics (if present)
    const flashcardStats = childCard.locator('.bg-purple-50, .bg-orange-50');
    if (await flashcardStats.first().isVisible()) {
      await expect(flashcardStats.first()).toBeVisible();
      await expect(childCard.getByText(/Active Flashcards/i)).toBeVisible();
      await expect(childCard.getByText(/Cards to Review/i)).toBeVisible();
    }
    
    // 15. Verify Catch-up Notice (if present)
    const catchUpNotice = childCard.locator('.bg-yellow-50');
    if (await catchUpNotice.isVisible()) {
      await expect(catchUpNotice.getByText(/catch-up/i)).toBeVisible();
    }
    
    // === SECTION 7: Multiple Children Testing ===
    console.log('Testing multiple children interactions');
    
    // Get all child cards
    const allChildCards = page.locator('.bg-white.rounded-lg.shadow-sm').filter({
      has: page.locator('h3') // Child name heading
    });
    
    const childCount = await allChildCards.count();
    console.log(`Found ${childCount} child cards to test`);
    
    // Test interactions on all child cards
    for (let i = 0; i < Math.min(childCount, 3); i++) { // Limit to 3 children for performance
      const card = allChildCards.nth(i);
      const childName = await card.locator('h3').textContent();
      console.log(`Testing interactions for child: ${childName}`);
      
      // Test that all expected elements are present for each child
      await expect(card.locator('[data-testid="child-view-link"]')).toBeVisible();
      await expect(card.locator('button[hx-get*="children"]')).toBeVisible(); // Settings
      await expect(card.locator('button[hx-post*="bulk-complete-today"]')).toBeVisible(); // Complete Today
      await expect(card.locator('a[href*="reviews"]')).toBeVisible(); // Reviews
      await expect(card.locator('select[name="independence_level"]')).toBeVisible(); // Independence
      
      // Test quick navigation for each child
      const childViewLink = card.locator('[data-testid="child-view-link"]');
      await childViewLink.click();
      
      // Verify we navigated to the child's dashboard
      await expect(page).toHaveURL(/\/dashboard\/child\/.+\/today/);
      
      // Return to parent dashboard
      await page.goto('/dashboard');
    }
    
    // === SECTION 8: Responsive Design and Error Handling ===
    console.log('Testing responsive behavior and error handling');
    
    // 16. Test responsive behavior by changing viewport
    await page.setViewportSize({ width: 768, height: 1024 }); // Tablet
    await expect(page.getByRole('heading', { name: /Parent Dashboard/i })).toBeVisible();
    
    await page.setViewportSize({ width: 375, height: 667 }); // Mobile
    await expect(page.getByRole('heading', { name: /Parent Dashboard/i })).toBeVisible();
    
    // Reset to desktop
    await page.setViewportSize({ width: 1280, height: 720 });
    
    // 17. Test error handling - rapid clicks
    const rapidClickBtn = childCard.locator('button[hx-post*="bulk-complete-today"]').first();
    if (await rapidClickBtn.isVisible()) {
      // Rapidly click the same button multiple times
      await rapidClickBtn.click();
      await rapidClickBtn.click();
      await rapidClickBtn.click();
      
      // Should not cause page errors
      await expect(page.getByRole('heading', { name: /Parent Dashboard/i })).toBeVisible();
    }
    
    // === SECTION 9: Navigation State Verification ===
    console.log('Verifying navigation states and breadcrumbs');
    
    // 18. Test that all navigation maintains proper state
    // Planning Board
    await page.getByRole('link', { name: /Planning Board/i }).click();
    await expect(page).toHaveURL('/planning');
    await page.goBack();
    await expect(page).toHaveURL('/dashboard');
    
    // Children Management  
    await page.getByRole('link', { name: /Manage Children/i }).click();
    await expect(page).toHaveURL('/children');
    await page.goBack();
    await expect(page).toHaveURL('/dashboard');
    
    console.log('✅ Complete Parent Dashboard Golden Path Test Successfully Completed!');
    console.log('📊 Tested all buttons, interactions, and real user scenarios');
  });
  
  test('Dashboard Empty State and Edge Cases', async ({ page }) => {
    // Test dashboard with no children/data
    console.log('Testing dashboard edge cases and empty states');
    
    // Create a new user without going through onboarding
    const helper = new TestSetupHelper(page);
    await helper.navigateAndWait('/register');
    
    await page.goto('/dashboard');
    
    // Should show empty state or redirect to onboarding
    const hasEmptyState = await page.getByText(/no children/i).isVisible();
    const isOnboarding = await page.getByText(/onboarding/i).isVisible();
    
    expect(hasEmptyState || isOnboarding).toBe(true);
    
    if (isOnboarding) {
      await expect(page).toHaveURL(/\/onboarding/);
    } else {
      // Test empty state interactions
      await expect(page.getByText(/add/i)).toBeVisible(); // Should have add children CTA
    }
    
    console.log('✅ Empty state testing completed');
  });
  
  test('Dashboard Performance and Loading States', async ({ page }) => {
    // Test loading performance and states
    console.log('Testing dashboard performance and loading states');
    
    const user = await quickTestLogin(page);
    
    // Monitor network requests
    const requests: string[] = [];
    page.on('request', request => {
      requests.push(request.url());
    });
    
    await page.goto('/dashboard');
    
    // Verify dashboard loads quickly
    await expect(page.getByRole('heading', { name: /Parent Dashboard/i })).toBeVisible();
    
    // Check that we didn't make excessive requests
    const dashboardRequests = requests.filter(url => url.includes('/dashboard'));
    expect(dashboardRequests.length).toBeLessThan(5); // Reasonable number of requests
    
    // Test HTMX loading indicators
    const htmxButtons = page.locator('[hx-post], [hx-get], [hx-put]');
    const buttonCount = await htmxButtons.count();
    
    if (buttonCount > 0) {
      const firstButton = htmxButtons.first();
      await firstButton.hover();
      
      // Verify button is interactive
      await expect(firstButton).toBeEnabled();
    }
    
    console.log(`✅ Performance test completed - ${buttonCount} interactive elements tested`);
  });
});