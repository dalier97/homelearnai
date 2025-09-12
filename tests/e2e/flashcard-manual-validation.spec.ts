import { test, expect } from '@playwright/test';

test.describe('Manual Flashcard Validation', () => {
  let testUser: { name: string; email: string; password: string };
  
  test.beforeEach(async ({ page }) => {
    // Create a unique test user for this session
    testUser = {
      name: 'Manual Test Parent',
      email: `manual-test-${Date.now()}@example.com`,
      password: 'testpassword123'
    };

    // Register and login
    await page.goto('/register');
    await page.fill('input[name="name"]', testUser.name);
    await page.fill('input[name="email"]', testUser.email);
    await page.fill('input[name="password"]', testUser.password);
    await page.fill('input[name="password_confirmation"]', testUser.password);
    await page.click('button[type="submit"]');
    
    // Wait for redirect
    await page.waitForLoadState('networkidle', { timeout: 10000 });
    
    // If we're still on register page, try logging in
    if (page.url().includes('/register')) {
      await page.goto('/login');
      await page.fill('input[name="email"]', testUser.email);
      await page.fill('input[name="password"]', testUser.password);
      await page.click('button[type="submit"]');
    }
    
    await page.waitForLoadState('networkidle', { timeout: 10000 });
  });
  
  test('step 1: validate flashcard section appears on unit screen', async ({ page }) => {
    // Create minimal test data by using existing subjects/units if available
    await page.goto('/subjects');
    await page.waitForLoadState('networkidle');
    
    // Check if we have any subjects
    const hasSubjects = await page.locator('.subject-card, .subject-item, a:has-text("Math"), a:has-text("English")').count() > 0;
    
    if (!hasSubjects) {
      // Create a subject
      await page.locator('button:has-text("Add Subject")').first().click();
      await page.fill('input[name="name"]', 'Manual Test Subject');
      await page.selectOption('select[name="color"]', '#3b82f6');
      await page.click('button:has-text("Save")');
      await page.waitForTimeout(1000);
    }
    
    // Click on the first subject
    const firstSubject = page.locator('a:has-text("Manual Test Subject"), .subject-card a, .subject-item a').first();
    await firstSubject.click();
    await page.waitForLoadState('networkidle');
    
    // Check if we have any units
    const hasUnits = await page.locator('[data-unit-name], .unit-card, a:has-text("View Unit")').count() > 0;
    
    if (!hasUnits) {
      // Create a unit
      await page.locator('button:has-text("Add Unit")').first().click();
      await page.waitForSelector('[data-testid="unit-create-modal"], #unit-create-modal, form', { timeout: 10000 });
      
      await page.fill('input[name="name"]', 'Manual Test Unit');
      await page.fill('textarea[name="description"]', 'Unit for manual testing');
      await page.fill('input[name="target_completion_date"]', '2024-12-31');
      await page.click('button:has-text("Save Unit")');
      await page.waitForTimeout(2000);
    }
    
    // Navigate to unit page
    const viewUnitLink = page.locator('a:has-text("View Unit"), a:has-text("Manual Test Unit")').first();
    await viewUnitLink.click();
    await page.waitForLoadState('networkidle');
    
    // VALIDATION 1: Check if Flashcards section is visible
    await expect(page.locator('h2:has-text("Flashcards")')).toBeVisible();
    console.log('✅ VALIDATION 1 PASSED: Flashcards section is visible on Unit screen');
    
    // VALIDATION 2: Check if flashcard count is shown
    await expect(page.locator('#flashcard-count, text=(0)')).toBeVisible();
    console.log('✅ VALIDATION 2 PASSED: Flashcard count is displayed');
    
    // VALIDATION 3: Check if Add Flashcard button exists
    await expect(page.locator('button:has-text("Add Flashcard")')).toBeVisible();
    console.log('✅ VALIDATION 3 PASSED: Add Flashcard button is visible');
  });

  test('step 2: debug modal opening behavior', async ({ page }) => {
    // Navigate to a unit page (simplified setup)
    await page.goto('/subjects');
    await page.waitForLoadState('networkidle');
    
    // Try to find any existing subject/unit, or create minimal ones
    let unitUrl = '';
    try {
      const existingSubjectLink = await page.locator('a[href*="/subjects/"]').first();
      if (await existingSubjectLink.count() > 0) {
        await existingSubjectLink.click();
        await page.waitForLoadState('networkidle');
        
        const existingUnitLink = await page.locator('a:has-text("View Unit")').first();
        if (await existingUnitLink.count() > 0) {
          await existingUnitLink.click();
          await page.waitForLoadState('networkidle');
          unitUrl = page.url();
        }
      }
    } catch (e) {
      console.log('Could not find existing data, creating minimal test data');
    }
    
    if (!unitUrl) {
      console.log('⚠️ No units found, validation limited');
      return;
    }
    
    // Debug the modal opening behavior
    console.log('Current URL:', unitUrl);
    
    // Check if flashcard modal target exists
    await expect(page.locator('#flashcard-modal')).toBeVisible();
    console.log('✅ Flashcard modal target element exists');
    
    // Check if Add Flashcard button has correct HTMX attributes
    const addButton = page.locator('button.bg-green-600:has-text("Add Flashcard")');
    await expect(addButton).toBeVisible();
    
    const hxGet = await addButton.getAttribute('hx-get');
    const hxTarget = await addButton.getAttribute('hx-target');
    const hxSwap = await addButton.getAttribute('hx-swap');
    
    console.log('HTMX attributes:', {
      'hx-get': hxGet,
      'hx-target': hxTarget, 
      'hx-swap': hxSwap
    });
    
    expect(hxGet).toMatch(/\/units\/\d+\/flashcards\/create/);
    expect(hxTarget).toBe('#flashcard-modal');
    expect(hxSwap).toBe('innerHTML');
    console.log('✅ HTMX attributes are correct');
    
    // Try clicking and wait for any response
    console.log('Attempting to click Add Flashcard button...');
    
    // Listen for console errors
    const consoleMessages: string[] = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        consoleMessages.push(`Console Error: ${msg.text()}`);
      }
    });
    
    // Listen for network requests
    const networkRequests: string[] = [];
    page.on('request', (request) => {
      if (request.url().includes('flashcards')) {
        networkRequests.push(`Request: ${request.method()} ${request.url()}`);
      }
    });
    
    page.on('response', (response) => {
      if (response.url().includes('flashcards')) {
        networkRequests.push(`Response: ${response.status()} ${response.url()}`);
      }
    });
    
    // Click the button and wait
    await addButton.click();
    await page.waitForTimeout(3000); // Wait 3 seconds for any response
    
    // Check results
    console.log('Console messages:', consoleMessages);
    console.log('Network requests:', networkRequests);
    
    // Check if modal content loaded
    const modalContent = await page.locator('#flashcard-modal').innerHTML();
    console.log('Modal content length:', modalContent.length);
    
    if (modalContent.trim().length > 0) {
      console.log('✅ Modal content loaded successfully');
      // Check if we can see the modal overlay
      if (await page.locator('#flashcard-modal-overlay').count() > 0) {
        console.log('✅ Modal overlay is present');
      } else {
        console.log('⚠️ Modal content loaded but overlay not visible');
        console.log('Modal content preview:', modalContent.substring(0, 200));
      }
    } else {
      console.log('❌ Modal content is empty - HTMX request may have failed');
      if (networkRequests.length === 0) {
        console.log('❌ No network requests detected - HTMX may not be loaded or working');
      }
    }
  });

  test('step 3: test if HTMX is loaded', async ({ page }) => {
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');
    
    // Check if HTMX is available
    const htmxLoaded = await page.evaluate(() => {
      return typeof (window as any).htmx !== 'undefined';
    });
    
    if (htmxLoaded) {
      console.log('✅ HTMX is loaded and available');
      
      // Check HTMX version
      const htmxVersion = await page.evaluate(() => {
        return (window as any).htmx?.version || 'unknown';
      });
      console.log('HTMX version:', htmxVersion);
    } else {
      console.log('❌ HTMX is not loaded - this explains why modals are not working');
    }
  });
});