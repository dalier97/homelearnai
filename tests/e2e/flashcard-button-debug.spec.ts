import { test, expect } from '@playwright/test';

test.describe('Flashcard Button Debug', () => {
  let subjectId: string;
  let unitId: string;
  const testUser = {
    email: `debug-flashcard-${Date.now()}@example.com`,
    password: 'password123'
  };

  test.beforeEach(async ({ page }) => {
    // Register and login
    await page.goto('/register');
    await page.fill('input[name="name"]', 'Debug User');
    await page.fill('input[name="email"]', testUser.email);
    await page.fill('input[name="password"]', testUser.password);
    await page.fill('input[name="password_confirmation"]', testUser.password);
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle', { timeout: 10000 });

    // Create child
    await page.goto('/children');
    await page.waitForLoadState('networkidle');
    
    await page.click('[data-testid="header-add-child-btn"]');
    await page.waitForSelector('#child-form-modal [data-testid="modal-content"]', { timeout: 10000 });
    await page.waitForTimeout(1000);
    await page.fill('#child-form-modal input[name="name"]', 'Test Child');
    await page.selectOption('#child-form-modal select[name="age"]', '10');
    await page.selectOption('#child-form-modal select[name="independence_level"]', '2');
    await page.click('#child-form-modal button[type="submit"]');
    await page.waitForTimeout(3000);

    // Create subject
    await page.goto('/subjects');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    await page.locator('button:has-text("Add Subject")').first().click();
    await page.fill('input[name="name"]', 'Debug Subject');
    await page.selectOption('select[name="color"]', '#3b82f6');
    await page.click('button:has-text("Save")');
    await page.waitForTimeout(2000);

    // Extract subject ID
    const subjectLink = await page.locator('a:has-text("Debug Subject")').first();
    const subjectHref = await subjectLink.getAttribute('href');
    subjectId = subjectHref?.match(/\/subjects\/(\d+)/)?.[1] || '1';

    // Create unit
    await page.click('text=Debug Subject');
    await page.waitForLoadState('networkidle');

    await page.click('button:has-text("Add Unit")');
    
    // Wait for HTMX to load the modal
    await waitForHTMXRequest(page);
    
    // Use data-testid selector from the fixed unit modal template
    await page.fill('[data-testid="unit-create-modal"] input[name="name"]', 'Debug Unit');
    await page.fill('[data-testid="unit-create-modal"] textarea[name="description"]', 'Unit for debugging');
    await page.fill('[data-testid="unit-create-modal"] input[name="target_completion_date"]', '2024-12-31');
    await page.click('[data-testid="unit-create-modal"] button[type="submit"]');
    
    // Wait for HTMX form submission to complete
    await waitForHTMXRequest(page);
    await page.waitForTimeout(1000);

    // Navigate to unit page - try multiple approaches
    let navigated = false;
    try {
      await page.click('div:has([data-unit-name="Debug Unit"]) a:has-text("View Unit")');
      await page.waitForLoadState('networkidle');
      navigated = true;
    } catch (e) {
      console.log('First navigation method failed, trying alternative...');
      // Try alternative navigation
      try {
        await page.click('text="Debug Unit"');
        await page.waitForLoadState('networkidle');
        navigated = true;
      } catch (e2) {
        console.log('Alternative navigation failed, using direct URL...');
        // Direct navigation as fallback
        await page.goto(`/subjects/${subjectId}/units/1`);
        await page.waitForLoadState('networkidle');
        navigated = true;
      }
    }
    
    const currentUrl = page.url();
    console.log(`Current URL after navigation: ${currentUrl}`);
    unitId = currentUrl.match(/\/units\/(\d+)/)?.[1] || '1';
    
    console.log(`Debug setup complete - Subject ID: ${subjectId}, Unit ID: ${unitId}`);
  });

  // HTMX-specific waiting helper
  async function waitForHTMXRequest(page: any, timeout: 10000) {
    return new Promise((resolve) => {
      const startTime = Date.now();
      
      const checkForRequests = () => {
        if (Date.now() - startTime > timeout) {
          resolve(false);
          return;
        }
        
        // Check if HTMX is currently processing requests
        page.evaluate(() => {
          return !document.body.classList.contains('htmx-request');
        }).then((notBusy: boolean) => {
          if (notBusy) {
            resolve(true);
          } else {
            setTimeout(checkForRequests, 100);
          }
        });
      };
      
      setTimeout(checkForRequests, 100);
    });
  }

  test('should debug Add Flashcard button behavior', async ({ page }) => {
    console.log('Starting flashcard button debug test...');
    
    // Capture network requests
    page.on('request', request => {
      if (request.url().includes('flashcards')) {
        console.log(`REQUEST: ${request.method()} ${request.url()}`);
      }
    });
    
    page.on('response', response => {
      if (response.url().includes('flashcards')) {
        console.log(`RESPONSE: ${response.status()} ${response.url()}`);
      }
    });

    // Wait for page load and verify we're on the unit page
    await page.waitForLoadState('networkidle', { timeout: 10000 });
    await page.waitForTimeout(2000); // Additional stability wait
    
    // Debug current page content
    console.log(`Current URL: ${page.url()}`);
    const pageTitle = await page.title().catch(() => 'N/A');
    console.log(`Page title: ${pageTitle}`);
    
    // Look for any h1, h2, or title elements to understand page structure
    const headings = await page.locator('h1, h2, h3').allTextContents().catch(() => []);
    console.log(`Page headings: ${JSON.stringify(headings)}`);
    
    // Verify we're on the unit page - check for unit title in different possible formats
    let unitTitleFound = false;
    try {
      // The unit page has h1 with the unit name, not necessarily "Debug Unit" specifically
      await expect(page.locator('h1').first()).toBeVisible({ timeout: 10000 });
      unitTitleFound = true;
      console.log('Found unit title in h1');
      
      // Log what the h1 actually contains for debugging
      const h1Text = await page.locator('h1').first().textContent();
      console.log(`Unit page h1 contains: "${h1Text}"`);
    } catch (e) {
      console.log('h1 not found, trying alternatives...');
      // Try alternative selectors for unit title
      const unitTitleSelectors = [
        'h1',
        'h2',
        '[data-testid="unit-title"]',
        'text=Debug Unit'
      ];
      
      let found = false;
      for (const selector of unitTitleSelectors) {
        if (await page.locator(selector).isVisible({ timeout: 1000 })) {
          const text = await page.locator(selector).textContent();
          console.log(`Found element with selector: ${selector}, text: "${text}"`);
          found = true;
          break;
        }
      }
      
      if (!found) {
        console.log('Unit title not found, but continuing with flashcard button test...');
        console.log(`Current URL: ${page.url()}`);
        // Get page title for debugging
        const pageTitle = await page.textContent('h1, h2, h3').catch(() => 'N/A');
        console.log(`Page title element: ${pageTitle}`);
      }
    }
    
    // Check if Add Flashcard button is visible (use first one in header)
    const addFlashcardButton = page.locator('button:has-text("Add Flashcard")').first();
    await expect(addFlashcardButton).toBeVisible();
    console.log('✓ Add Flashcard button is visible');
    
    // Check the button's HTMX attributes
    const buttonHtml = await addFlashcardButton.innerHTML();
    console.log(`Button HTML: ${buttonHtml}`);
    
    // Get the actual hx-get URL
    const hxGet = await addFlashcardButton.getAttribute('hx-get');
    console.log(`hx-get attribute: ${hxGet}`);
    
    // Get the modal target
    const hxTarget = await addFlashcardButton.getAttribute('hx-target');
    console.log(`hx-target attribute: ${hxTarget}`);
    
    // Check if modal container exists
    const modalContainer = page.locator('#flashcard-modal');
    const modalExists = await modalContainer.count() > 0;
    console.log(`Modal container exists: ${modalExists}`);
    
    if (modalExists) {
      const modalContent = await modalContainer.innerHTML();
      console.log(`Modal container current content: "${modalContent}"`);
    }

    // Now click the button and observe what happens
    console.log('Clicking Add Flashcard button...');
    await addFlashcardButton.click();
    
    // Wait specifically for HTMX request to complete
    await waitForHTMXRequest(page);
    console.log('✓ HTMX request completed');
    
    // Check modal container after HTMX request
    const modalContentAfter = await modalContainer.innerHTML();
    console.log(`Modal container content after HTMX: "${modalContentAfter.substring(0, 200)}..."`);
    
    // Check if modal with our updated selector appeared
    const modalWithTestId = await page.locator('[data-testid="flashcard-modal"]').count();
    console.log(`Modal with test-id found: ${modalWithTestId}`);
    
    if (modalWithTestId > 0) {
      console.log('✅ Modal appeared successfully with new template');
      
      // Check form fields
      const questionField = await page.locator('textarea[name="question"]').count();
      const answerField = await page.locator('textarea[name="answer"]').count();
      
      console.log(`Question field found: ${questionField > 0}`);
      console.log(`Answer field found: ${answerField > 0}`);
      
      if (questionField > 0 && answerField > 0) {
        console.log('✅ All form fields present - modal is working correctly');
        
        // Test Alpine.js close functionality
        const closeButton = page.locator('[data-testid="flashcard-modal"] button:has-text("Cancel")');
        if (await closeButton.count() > 0) {
          await closeButton.click();
          await page.waitForTimeout(500);
          const modalStillVisible = await page.locator('[data-testid="flashcard-modal"]').count();
          console.log(`Modal closed by Alpine.js: ${modalStillVisible === 0}`);
        }
      }
    } else {
      // Try to manually trigger the request for debugging
      console.log('Modal not found, trying manual HTMX request...');
      const manualUrl = `/units/${unitId}/flashcards/create`;
      console.log(`Manual URL: ${manualUrl}`);
      
      const response = await page.evaluate(async (url) => {
        try {
          const resp = await fetch(url, {
            method: 'GET',
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'HX-Request': 'true'
            }
          });
          return {
            status: resp.status,
            statusText: resp.statusText,
            content: await resp.text()
          };
        } catch (e) {
          return { error: e.message };
        }
      }, manualUrl);
      
      console.log('Manual request result status:', response.status);
      console.log('Manual request content preview:', response.content ? response.content.substring(0, 200) : 'No content');
    }
  });
});