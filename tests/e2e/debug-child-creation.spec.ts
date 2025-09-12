import { test, expect } from '@playwright/test';
import { ModalHelper, ElementHelper } from './helpers/modal-helpers';

test.describe('Debug Child Creation for Import Tests', () => {
  let testUser: { name: string; email: string; password: string };
  
  test.beforeEach(async ({ page }) => {
    // Create a unique test user for this debug session
    testUser = {
      name: 'Debug Test User',
      email: `debug-test-${Date.now()}@example.com`,
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
    
    if (page.url().includes('/register') || page.url().includes('/login')) {
      await page.goto('/login');
      await page.fill('input[name="email"]', testUser.email);
      await page.fill('input[name="password"]', testUser.password);
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle', { timeout: 10000 });
    }
  });

  test('should create child and verify it appears on subjects page', async ({ page }) => {
    console.log('=== DEBUGGING CHILD CREATION ===');
    
    // Step 1: Create child
    console.log('Step 1: Creating child...');
    await page.goto('/children');
    await page.waitForLoadState('networkidle');
    
    // Check if we're on children page
    console.log('Current URL after going to /children:', page.url());
    
    // Look for the Add Child button
    const addChildBtn = page.locator('[data-testid="header-add-child-btn"]');
    const btnExists = await addChildBtn.count();
    console.log('Add Child button exists:', btnExists > 0);
    
    if (btnExists === 0) {
      console.log('ERROR: Add Child button not found! Page content:');
      const pageContent = await page.content();
      console.log(pageContent.slice(0, 1000) + '...');
      throw new Error('Add Child button not found');
    }
    
    // Click the Add Child button
    await addChildBtn.click();
    console.log('Clicked Add Child button');
    
    // Wait for modal to appear - use the working pattern from flashcard-full-validation
    await page.waitForSelector('[data-testid="child-modal-overlay"]', { state: 'visible', timeout: 10000 });
    console.log('Modal overlay appeared');
    await page.waitForTimeout(500); // Give Alpine.js time to fully initialize
    
    // Fill the form using the working pattern
    await page.fill('[data-testid="child-modal-overlay"] input[name="name"]', 'Debug Test Child');
    console.log('Filled child name');
    
    await page.selectOption('[data-testid="child-modal-overlay"] select[name="age"]', '12');
    console.log('Selected age');
    
    await page.selectOption('[data-testid="child-modal-overlay"] select[name="independence_level"]', '2');
    console.log('Selected independence level');
    
    // Submit the form
    await page.click('[data-testid="child-modal-overlay"] button[type="submit"]');
    console.log('Submitted child form');
    
    await page.waitForTimeout(2000);
    console.log('Waited after form submission');
    
    // Check if we're back on children page and child was created
    const currentUrl = page.url();
    console.log('Current URL after form submission:', currentUrl);
    
    // Look for the child in the children list
    const childExists = await page.locator('text=Debug Test Child').count() > 0;
    console.log('Child found in children list:', childExists);
    
    // Step 2: Go to subjects page and check
    console.log('Step 2: Checking subjects page...');
    await page.goto('/subjects');
    await page.waitForLoadState('networkidle');
    
    const subjectsUrl = page.url();
    console.log('Current URL on subjects page:', subjectsUrl);
    
    // Check if "No children found" message appears
    const noChildrenMsg = await page.locator('text=No children found').count();
    console.log('No children found message count:', noChildrenMsg);
    
    // Check if child selector exists
    const childSelector = await page.locator('#child-selector').count();
    console.log('Child selector exists:', childSelector > 0);
    
    if (childSelector > 0) {
      // Check options in the child selector
      const options = await page.locator('#child-selector option').count();
      console.log('Child selector options count:', options);
      
      // Log all options
      for (let i = 0; i < options; i++) {
        const optionText = await page.locator('#child-selector option').nth(i).innerText();
        const optionValue = await page.locator('#child-selector option').nth(i).getAttribute('value');
        console.log(`Option ${i}: value="${optionValue}", text="${optionText}"`);
      }
    }
    
    // If no children found, let's check what the controller is returning
    if (noChildrenMsg > 0) {
      console.log('ERROR: Child was created but not showing on subjects page');
      
      // Check session/auth by going back to children page
      await page.goto('/children');
      await page.waitForLoadState('networkidle');
      
      const childExistsOnChildrenPage = await page.locator('text=Debug Test Child').count() > 0;
      console.log('Child exists when going back to children page:', childExistsOnChildrenPage);
      
      if (!childExistsOnChildrenPage) {
        console.log('ERROR: Child creation failed or session lost');
      } else {
        console.log('ERROR: Child exists but not showing on subjects page - possible auth/relationship issue');
      }
    } else {
      console.log('SUCCESS: Child appears to be available on subjects page');
      
      // Try to select the child and see if Add Subject button appears
      if (childSelector > 0) {
        const firstChildOption = page.locator('#child-selector option').nth(1); // Skip "Select a child" option
        const childId = await firstChildOption.getAttribute('value');
        if (childId) {
          await page.selectOption('#child-selector', childId);
          await page.waitForTimeout(2000); // Wait for HTMX
          
          const addSubjectBtn = await page.locator('button:has-text("Add Subject")').first().count();
          console.log('Add Subject button appeared after child selection:', addSubjectBtn > 0);
        }
      }
    }
    
    console.log('=== DEBUG COMPLETE ===');
    
    // Make the test pass/fail based on whether we found the issue
    expect(noChildrenMsg).toBe(0); // Should be 0 if child creation worked
  });
});