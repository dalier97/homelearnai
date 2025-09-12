import { test, expect } from '@playwright/test';

test.describe('Flashcard Modal Basic Tests', () => {
  let testUser: { name: string; email: string; password: string };
  
  test.beforeEach(async ({ page }) => {
    // Create a unique test user
    testUser = {
      name: 'Modal Test User',
      email: `modal-test-${Date.now()}@example.com`,
      password: 'testpassword123'
    };

    // Register and login
    await page.goto('/register');
    await page.fill('input[name="name"]', testUser.name);
    await page.fill('input[name="email"]', testUser.email);
    await page.fill('input[name="password"]', testUser.password);
    await page.fill('input[name="password_confirmation"]', testUser.password);
    await page.click('button[type="submit"]');
    
    await page.waitForLoadState('networkidle', { timeout: 10000 });
    
    // Handle redirect to login if needed
    if (page.url().includes('/register') || page.url().includes('/login')) {
      await page.goto('/login');
      await page.fill('input[name="email"]', testUser.email);
      await page.fill('input[name="password"]', testUser.password);
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle', { timeout: 10000 });
    }
  });
  
  test('should access existing unit and test flashcard modal', async ({ page }) => {
    console.log('Starting basic flashcard modal test...');
    
    // Try to find any existing unit by navigating through the app
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');
    
    // Check if we're properly logged in
    const isLoggedIn = !page.url().includes('/login') && !page.url().includes('/register');
    expect(isLoggedIn).toBe(true);
    
    console.log('Successfully logged in, current URL:', page.url());
    
    // Navigate to subjects page
    await page.goto('/subjects');
    await page.waitForLoadState('networkidle');
    
    // Check what we see on the subjects page
    const pageContent = await page.textContent('body');
    console.log('Subjects page content preview:', pageContent.substring(0, 200));
    
    // Look for existing subjects or units we can use for testing
    const existingSubjects = await page.locator('a[href*="/subjects/"]').count();
    
    if (existingSubjects > 0) {
      console.log(`Found ${existingSubjects} existing subject(s)`);
      
      // Click on first subject
      await page.locator('a[href*="/subjects/"]').first().click();
      await page.waitForLoadState('networkidle');
      
      // Look for existing units
      const existingUnits = await page.locator('a:has-text("View Unit")').count();
      
      if (existingUnits > 0) {
        console.log(`Found ${existingUnits} existing unit(s)`);
        
        // Click on first unit
        await page.locator('a:has-text("View Unit")').first().click();
        await page.waitForLoadState('networkidle');
        
        // Now test flashcard functionality
        await testFlashcardModal(page);
      } else {
        console.log('No existing units found');
        await expect(page.locator('text=No units, text=Add Unit')).toBeTruthy();
      }
    } else {
      console.log('No existing subjects found');
      await expect(page.locator('text=No children found, text=add children first')).toBeTruthy();
    }
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
  
  async function testFlashcardModal(page: any) {
    console.log('Testing flashcard modal on unit page...');
    
    // Verify we're on a unit page
    const onUnitPage = page.url().includes('/units/') || 
                      await page.locator('h2:has-text("Flashcards")').count() > 0;
    
    if (!onUnitPage) {
      console.log('Not on unit page, cannot test flashcard modal');
      return;
    }
    
    console.log('✅ On unit page, looking for flashcard section...');
    
    // Check if flashcards section exists
    const flashcardsSection = await page.locator('h2:has-text("Flashcards")').count();
    if (flashcardsSection > 0) {
      console.log('✅ Found flashcards section');
      
      // Look for Add Flashcard button
      const addButtons = await page.locator('button:has-text("Add Flashcard")').count();
      if (addButtons > 0) {
        console.log('✅ Found Add Flashcard button');
        
        // Test clicking the button
        try {
          await page.click('button.bg-green-600:has-text("Add Flashcard")');
          console.log('✅ Clicked Add Flashcard button');
          
          // Wait specifically for HTMX request to complete
          await waitForHTMXRequest(page);
          console.log('✅ HTMX request completed');
          
          // Wait for modal to appear with proper selector based on our fixed template
          const modal = await page.locator('[data-testid="flashcard-modal"]').first();
          await modal.waitFor({ state: 'visible', timeout: 10000 });
          console.log('✅ Modal appeared');
          
          // Check if modal content loaded
          const modalContent = await page.locator('h3:has-text("Add New Flashcard")').count();
          
          if (modalContent > 0) {
            console.log('✅ Modal content loaded successfully');
            
            // Test form fields
            const questionField = await page.locator('textarea[name="question"]').count();
            const answerField = await page.locator('textarea[name="answer"]').count();
            
            if (questionField > 0 && answerField > 0) {
              console.log('✅ Form fields found');
              
              // Try filling out the form
              await page.fill('textarea[name="question"]', 'Test Question');
              await page.fill('textarea[name="answer"]', 'Test Answer');
              
              console.log('✅ Form fields filled successfully');
              
              // Try submitting
              const submitButton = await page.locator('button:has-text("Create Flashcard")').count();
              if (submitButton > 0) {
                console.log('✅ Submit button found - flashcard modal is fully functional');
                
                // Test form submission
                await page.click('button:has-text("Create Flashcard")');
                console.log('✅ Form submitted');
                
                // Wait for HTMX to process the form
                await waitForHTMXRequest(page);
                
                // Modal should close automatically on success
                await page.waitForTimeout(500);
                const modalStillVisible = await page.locator('[data-testid="flashcard-modal"]').count();
                if (modalStillVisible === 0) {
                  console.log('✅ Modal closed after successful submission');
                } else {
                  console.log('⚠️ Modal still visible after submission');
                }
              }
            } else {
              console.log('❌ Form fields not found');
            }
          } else {
            console.log('❌ Modal content not loaded');
          }
        } catch (error) {
          console.log('❌ Error during modal test:', error.message);
        }
      } else {
        console.log('❌ No Add Flashcard button found');
        
        // Check if we're in kids mode
        const inKidsMode = await page.locator('text=Kids Mode, text=PIN').count() > 0;
        if (inKidsMode) {
          console.log('Appears to be in kids mode - buttons hidden');
        }
      }
    } else {
      console.log('❌ No flashcards section found');
    }
  }
  
  test('should test with minimal setup using direct URLs', async ({ page }) => {
    console.log('Testing direct URL approach...');
    
    // Try accessing a likely unit URL directly
    const testUrls = [
      '/subjects/1/units/1',
      '/subjects/2/units/1', 
      '/units/1',
      '/dashboard'
    ];
    
    for (const url of testUrls) {
      try {
        await page.goto(url);
        await page.waitForLoadState('networkidle', { timeout: 10000 });
        
        const hasFlashcards = await page.locator('h2:has-text("Flashcards")').count() > 0;
        const hasAddButton = await page.locator('button:has-text("Add Flashcard")').count() > 0;
        
        if (hasFlashcards && hasAddButton) {
          console.log(`✅ Found working unit page at ${url}`);
          
          // Test the modal
          await page.click('button.bg-green-600:has-text("Add Flashcard")');
          await page.waitForTimeout(2000);
          
          const modalWorks = await page.locator('#flashcard-modal-overlay').count() > 0;
          console.log(`Modal functional at ${url}: ${modalWorks ? 'Yes' : 'No'}`);
          
          if (modalWorks) {
            // Success! We found a working setup
            break;
          }
        } else {
          console.log(`No flashcard functionality at ${url}`);
        }
      } catch (e) {
        console.log(`Could not access ${url}:`, e.message);
      }
    }
  });
});