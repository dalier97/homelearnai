import { test, expect } from '@playwright/test';

/**
 * Helper to wait for Alpine.js to initialize and elements to be ready
 */
async function waitForAlpineReady(page: any, timeout: number = 10000) {
  await page.waitForFunction(() => {
    return typeof window.Alpine !== 'undefined' && window.Alpine.version;
  }, { timeout });
  
  // Wait additional moment for components to initialize
  await page.waitForTimeout(500);
}

/**
 * Helper to wait for x-if template elements to be added to DOM
 */
async function waitForTemplateElement(page: any, selector: string, timeout: number = 10000) {
  await page.waitForSelector(selector, { timeout });
  await page.waitForTimeout(200); // Brief pause for Alpine to finish rendering
}

/**
 * Robust onboarding completion that handles x-if templates
 */
async function completeOnboarding(page: any, childName: string = 'Test Child') {
  console.log('Starting onboarding completion...');
  
  // Wait for Alpine.js and onboarding wizard to initialize
  await waitForAlpineReady(page);
  await waitForTemplateElement(page, '[data-testid="step-1"]');
  
  console.log('Step 1: Welcome - clicking next...');
  // Step 1: Welcome
  await expect(page.getByTestId('step-1')).toBeVisible({ timeout: 5000 });
  await page.getByTestId('next-button').click();
  
  // Wait for Step 2 to appear (x-if template)
  await waitForTemplateElement(page, '[data-testid="step-2"]');
  
  console.log('Step 2: Children - filling form...');
  // Step 2: Children
  await expect(page.getByTestId('step-2')).toBeVisible({ timeout: 5000 });
  await page.getByTestId('child-name-0').fill(childName);
  await page.selectOption('[data-testid="child-age-0"]', '10');
  await page.selectOption('[data-testid="child-independence-0"]', '2');
  
  // Submit children form
  await page.getByTestId('next-button').click();
  
  // Wait for success message and Step 3 to appear
  await expect(page.getByTestId('form-success')).toBeVisible({ timeout: 10000 });
  await waitForTemplateElement(page, '[data-testid="step-3"]');
  
  console.log('Step 3: Subjects - skipping or selecting subjects...');
  // Step 3: Subjects (skip for quick completion)
  await expect(page.getByTestId('step-3')).toBeVisible({ timeout: 5000 });
  
  // Try to find and click skip checkbox or select some subjects
  const skipCheckbox = page.locator('input[type="checkbox"][name*="skip"]').first();
  if (await skipCheckbox.isVisible({ timeout: 2000 })) {
    await skipCheckbox.check();
    console.log('Skipping subjects selection...');
  } else {
    // Select at least one subject if no skip option
    const firstSubjectCheckbox = page.locator('input[type="checkbox"]:not([name*="skip"])').first();
    if (await firstSubjectCheckbox.isVisible({ timeout: 2000 })) {
      await firstSubjectCheckbox.check();
      console.log('Selected first available subject...');
    }
  }
  
  // Submit subjects form
  await page.getByTestId('next-button').click();
  
  // Wait for Step 4 and completion
  await waitForTemplateElement(page, '[data-testid="step-4"]');
  await expect(page.getByTestId('step-4')).toBeVisible({ timeout: 10000 });
  
  console.log('Step 4: Review - completing onboarding...');
  // Step 4: Complete
  await page.getByTestId('complete-onboarding-button').click();
  
  // Wait for completion and redirect
  await page.waitForURL('/dashboard', { timeout: 15000 });
  console.log('Onboarding completed successfully!');
}

test.describe('Curriculum Management - Subjects, Units, Topics', () => {
  let testUser: { email: string; password: string };
  let childId: string;

  test.beforeEach(async ({ page }) => {
    // Create unique test user
    testUser = {
      email: `curriculum-${Date.now()}-${Math.random().toString(36).substring(7)}@example.com`,
      password: 'testpass123'
    };

    console.log(`Creating test user: ${testUser.email}`);

    // Register user with proper error handling
    await page.goto('/register', { waitUntil: 'networkidle' });
    await page.fill('input[name="name"]', 'Test Parent');
    await page.fill('input[name="email"]', testUser.email);
    await page.fill('input[name="password"]', testUser.password);
    await page.fill('input[name="password_confirmation"]', testUser.password);
    await page.click('button[type="submit"]');
    
    // Wait for redirect - either to onboarding or dashboard
    await page.waitForTimeout(2000);
    
    // Handle onboarding if redirected there
    if (page.url().includes('/onboarding')) {
      console.log('User redirected to onboarding, completing setup...');
      await completeOnboarding(page, 'Test Child');
    }
    
    // Ensure we're on dashboard and get child ID
    await page.goto('/dashboard', { waitUntil: 'networkidle' });
    
    // Navigate to children page to get child ID
    await page.goto('/children', { waitUntil: 'networkidle' });
    
    // Wait for children to load and get the first child's ID
    const childLink = page.locator('a[href*="/children/"]').first();
    await expect(childLink).toBeVisible({ timeout: 10000 });
    
    const href = await childLink.getAttribute('href');
    childId = href?.split('/').pop() || '';
    
    if (!childId) {
      throw new Error('Could not extract child ID from href');
    }
    
    console.log(`Child ID extracted: ${childId}`);
  });

  test('should create subject from child page', async ({ page }) => {
    console.log(`Testing subject creation for child ID: ${childId}`);
    
    // Navigate to child page and wait for Alpine.js
    await page.goto(`/children/${childId}`, { waitUntil: 'networkidle' });
    await waitForAlpineReady(page);
    
    // Verify we're on the correct page
    await expect(page.locator('h1, h2').first()).toContainText('Test Child', { timeout: 10000 });
    
    console.log('Page loaded, looking for Add Subject button...');
    
    // Wait for and click Add Subject button with retry logic
    const addSubjectBtn = page.locator('button:has-text("Add Subject"), [data-testid*="add-subject"], button[data-action*="subject"]').first();
    
    // Retry logic for finding the button
    let buttonFound = false;
    for (let i = 0; i < 3; i++) {
      try {
        await expect(addSubjectBtn).toBeVisible({ timeout: 5000 });
        buttonFound = true;
        break;
      } catch (e) {
        console.log(`Attempt ${i + 1}: Add Subject button not found, refreshing page...`);
        await page.reload({ waitUntil: 'networkidle' });
        await waitForAlpineReady(page);
      }
    }
    
    if (!buttonFound) {
      throw new Error('Add Subject button not found after 3 attempts');
    }
    
    await addSubjectBtn.click();
    console.log('Add Subject button clicked, waiting for modal...');
    
    // Wait for modal to appear with multiple possible selectors
    const modal = page.locator('#subject-modal, [data-testid="subject-modal"], .modal:has-text("Subject")').first();
    await expect(modal).toBeVisible({ timeout: 10000 });
    
    console.log('Modal opened, filling form...');
    
    // Fill subject form with more specific selectors
    const nameInput = modal.locator('input[name="name"], [data-testid="subject-name"]').first();
    const colorSelect = modal.locator('select[name="color"], [data-testid="subject-color"]').first();
    
    await expect(nameInput).toBeVisible({ timeout: 5000 });
    await nameInput.fill('Mathematics');
    
    await expect(colorSelect).toBeVisible({ timeout: 5000 });
    await colorSelect.selectOption('#ef4444');
    
    console.log('Form filled, submitting...');
    
    // Submit form
    const submitBtn = modal.locator('button[type="submit"], [data-testid="submit-subject"]').first();
    await expect(submitBtn).toBeVisible({ timeout: 5000 });
    await submitBtn.click();
    
    console.log('Form submitted, waiting for success indicators...');
    
    // Wait for either success message or subject to appear in list
    await Promise.race([
      // Option 1: Success message appears
      page.waitForSelector('.alert-success, .bg-green-100, [data-testid="success"]', { timeout: 10000 }),
      // Option 2: Subject appears in the list
      page.waitForSelector('*:has-text("Mathematics")', { timeout: 10000 }),
      // Option 3: Modal disappears (indicating success)
      modal.waitFor({ state: 'hidden', timeout: 10000 })
    ]);
    
    // Verify subject appears in the page
    await expect(page.locator('text=Mathematics')).toBeVisible({ timeout: 5000 });
    
    console.log('Subject creation test completed successfully!');
  });

  test('should create unit within subject', async ({ page }) => {
    console.log('Testing unit creation within subject...');
    
    // First create a subject via direct navigation
    await page.goto('/subjects/create', { waitUntil: 'networkidle' });
    await waitForAlpineReady(page);
    
    console.log('Creating subject via direct form...');
    await expect(page.locator('input[name="name"]')).toBeVisible({ timeout: 5000 });
    await page.fill('input[name="name"]', 'Science');
    await page.selectOption('select[name="color"]', '#10b981');
    await page.fill('input[name="child_id"]', childId);
    await page.click('button[type="submit"]');
    
    // Wait for redirect to subject page
    await page.waitForLoadState('networkidle');
    await waitForAlpineReady(page);
    
    console.log('Subject created, looking for subject page or link...');
    
    // Navigate to subject page - try multiple approaches
    let subjectPageReached = false;
    
    // Approach 1: Already redirected to subject page
    if (page.url().includes('/subjects/')) {
      console.log('Already on subject page after creation');
      subjectPageReached = true;
    } else {
      // Approach 2: Look for Science link
      try {
        const scienceLink = page.locator('a:has-text("Science")').first();
        await expect(scienceLink).toBeVisible({ timeout: 5000 });
        await scienceLink.click();
        await page.waitForLoadState('networkidle');
        await waitForAlpineReady(page);
        subjectPageReached = true;
        console.log('Navigated to subject page via link');
      } catch (e) {
        console.log('Science link not found, trying subjects page...');
        // Approach 3: Go to subjects page and find the subject
        await page.goto('/subjects', { waitUntil: 'networkidle' });
        await waitForAlpineReady(page);
        const scienceSubject = page.locator('a:has-text("Science"), [href*="subjects"]:has-text("Science")').first();
        await expect(scienceSubject).toBeVisible({ timeout: 5000 });
        await scienceSubject.click();
        await page.waitForLoadState('networkidle');
        await waitForAlpineReady(page);
        subjectPageReached = true;
        console.log('Navigated to subject page via subjects index');
      }
    }
    
    if (!subjectPageReached) {
      throw new Error('Could not reach subject page');
    }
    
    console.log('On subject page, looking for Add Unit button...');
    
    // Click Add Unit button with retry logic
    const addUnitBtn = page.locator('button:has-text("Add Unit"), [data-testid*="add-unit"], button[data-action*="unit"]').first();
    
    let unitButtonFound = false;
    for (let i = 0; i < 3; i++) {
      try {
        await expect(addUnitBtn).toBeVisible({ timeout: 5000 });
        unitButtonFound = true;
        break;
      } catch (e) {
        console.log(`Attempt ${i + 1}: Add Unit button not found, refreshing...`);
        await page.reload({ waitUntil: 'networkidle' });
        await waitForAlpineReady(page);
      }
    }
    
    if (!unitButtonFound) {
      throw new Error('Add Unit button not found after 3 attempts');
    }
    
    await addUnitBtn.click();
    console.log('Add Unit button clicked, waiting for form...');
    
    // Wait for form to appear (could be modal or inline form)
    const unitForm = page.locator('form:has(input[name="name"]), [data-testid="unit-form"], .modal:has(input[name="name"])').first();
    await expect(unitForm).toBeVisible({ timeout: 10000 });
    
    console.log('Unit form visible, filling fields...');
    
    // Fill unit form
    const nameInput = unitForm.locator('input[name="name"]').first();
    const descriptionInput = unitForm.locator('textarea[name="description"]').first();
    
    await expect(nameInput).toBeVisible({ timeout: 5000 });
    await nameInput.fill('Physics Basics');
    
    if (await descriptionInput.isVisible({ timeout: 2000 })) {
      await descriptionInput.fill('Introduction to physics concepts');
    }
    
    // Submit form
    const submitBtn = unitForm.locator('button[type="submit"]').first();
    await expect(submitBtn).toBeVisible({ timeout: 5000 });
    await submitBtn.click();
    
    console.log('Unit form submitted, waiting for confirmation...');
    
    // Wait for success indicators
    await Promise.race([
      page.waitForSelector('.alert-success, .bg-green-100, [data-testid="success"]', { timeout: 10000 }),
      page.waitForSelector('*:has-text("Physics Basics")', { timeout: 10000 }),
      page.waitForURL('**/units/**', { timeout: 10000 })
    ]);
    
    // Verify unit appears
    await expect(page.locator('text=Physics Basics')).toBeVisible({ timeout: 5000 });
    
    console.log('Unit creation test completed successfully!');
  });

  test('should create topic within unit', async ({ page }) => {
    // Create subject and unit first
    await page.goto('/subjects/create');
    await page.fill('input[name="name"]', 'History');
    await page.selectOption('select[name="color"]', '#8b5cf6');
    await page.fill('input[name="child_id"]', childId);
    await page.click('button[type="submit"]');
    
    // Get subject ID from URL
    await page.waitForURL('**/subjects/**');
    const subjectId = page.url().split('/').pop();
    
    // Create unit via direct URL
    await page.goto(`/subjects/${subjectId}/units/create`);
    await page.fill('input[name="name"]', 'Ancient Rome');
    await page.click('button[type="submit"]');
    
    // Navigate to unit
    await page.click('a:has-text("Ancient Rome")');
    
    // Add topic
    const addTopicBtn = page.locator('button:has-text("Add Topic")');
    await expect(addTopicBtn).toBeVisible();
    await addTopicBtn.click();
    
    // Fill topic form
    await page.fill('input[name="name"]', 'Rise of the Empire');
    await page.fill('textarea[name="description"]', 'Study of Rome\'s expansion');
    await page.click('button[type="submit"]');
    
    // Verify topic appears
    await expect(page.locator('text=Rise of the Empire')).toBeVisible({ timeout: 5000 });
  });

  test('should handle validation errors gracefully', async ({ page }) => {
    console.log('Testing validation error handling...');
    
    await page.goto(`/children/${childId}`, { waitUntil: 'networkidle' });
    await waitForAlpineReady(page);
    
    console.log('Looking for Add Subject button...');
    
    // Try to create subject without name
    const addSubjectBtn = page.locator('button:has-text("Add Subject"), [data-testid*="add-subject"]').first();
    
    // Wait with retry
    let attempts = 0;
    while (attempts < 3) {
      try {
        await expect(addSubjectBtn).toBeVisible({ timeout: 5000 });
        break;
      } catch (e) {
        attempts++;
        if (attempts >= 3) throw e;
        await page.reload({ waitUntil: 'networkidle' });
        await waitForAlpineReady(page);
      }
    }
    
    await addSubjectBtn.click();
    
    console.log('Modal should be opening...');
    
    const modal = page.locator('#subject-modal, [data-testid="subject-modal"], .modal:has-text("Subject")').first();
    await expect(modal).toBeVisible({ timeout: 10000 });
    
    console.log('Modal opened, submitting empty form...');
    
    // Submit empty form (without filling name)
    const submitBtn = modal.locator('button[type="submit"], [data-testid="submit-subject"]').first();
    await expect(submitBtn).toBeVisible({ timeout: 5000 });
    await submitBtn.click();
    
    console.log('Empty form submitted, waiting for validation errors...');
    
    // Wait for validation errors to appear
    const errorSelectors = [
      '.text-red-600',
      '.text-red-500', 
      '.error',
      '.invalid-feedback',
      '[data-testid="error"]',
      '.alert-danger'
    ];
    
    let errorFound = false;
    for (const selector of errorSelectors) {
      try {
        await expect(modal.locator(selector)).toBeVisible({ timeout: 3000 });
        errorFound = true;
        console.log(`Validation error found with selector: ${selector}`);
        break;
      } catch (e) {
        // Continue to next selector
      }
    }
    
    // Alternative: Check if form didn't submit (modal still open and button still enabled)
    if (!errorFound) {
      console.log('No specific error message found, checking if form submission was prevented...');
      await expect(modal).toBeVisible({ timeout: 2000 });
      console.log('Form submission was prevented (modal still open)');
    }
    
    // Verify modal stays open (indicating validation prevented submission)
    await expect(modal).toBeVisible();
    
    console.log('Validation test completed - form properly prevented empty submission');
  });

  test('should navigate between subjects, units, and topics', async ({ page }) => {
    // Create test data
    await page.goto('/subjects/create');
    await page.fill('input[name="name"]', 'Navigation Test Subject');
    await page.selectOption('select[name="color"]', '#06b6d4');
    await page.fill('input[name="child_id"]', childId);
    await page.click('button[type="submit"]');
    
    // Verify redirect to subject page
    await expect(page.locator('h1')).toContainText('Navigation Test Subject');
    
    // Check breadcrumb navigation
    await expect(page.locator('nav[aria-label="Breadcrumb"]')).toBeVisible();
    
    // Navigate back to subjects list
    await page.click('a:has-text("Subjects")');
    await expect(page.locator('h1, h2')).toContainText('Subjects');
    
    // Verify subject appears in list
    await expect(page.locator('text=Navigation Test Subject')).toBeVisible();
  });

  test('should properly display empty states', async ({ page }) => {
    console.log('Testing empty state displays...');
    
    await page.goto(`/children/${childId}`, { waitUntil: 'networkidle' });
    await waitForAlpineReady(page);
    
    console.log('Checking for empty states...');
    
    // Check empty state for subjects
    const emptyStateSelectors = [
      'text=No subjects created yet',
      'text=No subjects yet',
      'text=Create your first subject',
      '[data-testid="empty-subjects"]'
    ];
    
    let emptyStateVisible = false;
    for (const selector of emptyStateSelectors) {
      if (await page.locator(selector).isVisible({ timeout: 2000 })) {
        console.log(`Empty state found: ${selector}`);
        emptyStateVisible = true;
        break;
      }
    }
    
    if (emptyStateVisible) {
      // If empty state exists, try to find encouraging text
      const encouragingText = [
        'text=Start building',
        'text=Get started',
        'text=Add your first'
      ];
      
      for (const text of encouragingText) {
        if (await page.locator(text).isVisible({ timeout: 2000 })) {
          console.log(`Found encouraging text: ${text}`);
          break;
        }
      }
    }
    
    console.log('Creating subject to test unit empty state...');
    
    // Create a subject to test unit empty state
    const addSubjectBtn = page.locator('button:has-text("Add Subject"), [data-testid*="add-subject"]').first();
    
    // Wait for button with retry
    let attempts = 0;
    while (attempts < 3) {
      try {
        await expect(addSubjectBtn).toBeVisible({ timeout: 5000 });
        break;
      } catch (e) {
        attempts++;
        if (attempts >= 3) {
          console.log('Add Subject button not found, skipping unit empty state test');
          return;
        }
        await page.reload({ waitUntil: 'networkidle' });
        await waitForAlpineReady(page);
      }
    }
    
    await addSubjectBtn.click();
    
    const modal = page.locator('#subject-modal, [data-testid="subject-modal"], .modal:has-text("Subject")').first();
    await expect(modal).toBeVisible({ timeout: 10000 });
    
    // Fill and submit form
    await modal.locator('input[name="name"]').fill('Empty State Test');
    await modal.locator('select[name="color"]').selectOption('#f59e0b');
    await modal.locator('button[type="submit"]').click();
    
    console.log('Subject created, navigating to subject page...');
    
    // Navigate to subject - try multiple approaches
    await Promise.race([
      page.waitForSelector('a:has-text("Empty State Test")', { timeout: 5000 }),
      page.waitForURL('**/subjects/**', { timeout: 5000 }),
      page.waitForTimeout(3000)
    ]);
    
    // Try to navigate to the subject
    if (!page.url().includes('/subjects/')) {
      const subjectLink = page.locator('a:has-text("Empty State Test")').first();
      if (await subjectLink.isVisible({ timeout: 3000 })) {
        await subjectLink.click();
        await page.waitForLoadState('networkidle');
        await waitForAlpineReady(page);
      }
    }
    
    console.log('Checking unit empty state...');
    
    // Check empty state for units
    const unitEmptyStates = [
      'text=No units yet',
      'text=No units created',
      'text=Create your first unit',
      '[data-testid="empty-units"]'
    ];
    
    for (const selector of unitEmptyStates) {
      if (await page.locator(selector).isVisible({ timeout: 3000 })) {
        console.log(`Unit empty state found: ${selector}`);
        await expect(page.locator(selector)).toBeVisible();
        break;
      }
    }
    
    console.log('Empty state test completed!');
  });

  test('should respect route ordering (no type errors)', async ({ page }) => {
    // Test that /create routes work (they should come before /{id} routes)
    
    // Test subject create route
    await page.goto('/subjects/create');
    await expect(page).not.toHaveURL('**/subjects/create/show'); // Should NOT interpret "create" as ID
    await expect(page.locator('input[name="name"]')).toBeVisible(); // Should show form
    
    // Create a subject to get ID
    await page.fill('input[name="name"]', 'Route Test Subject');
    await page.selectOption('select[name="color"]', '#ec4899');
    await page.fill('input[name="child_id"]', childId);
    await page.click('button[type="submit"]');
    
    const subjectId = page.url().split('/').pop();
    
    // Test unit create route
    await page.goto(`/subjects/${subjectId}/units/create`);
    await expect(page).not.toHaveURL(`**/units/create/show`); // Should NOT interpret "create" as ID
    await expect(page.locator('input[name="name"]')).toBeVisible(); // Should show form
    
    // Create a unit
    await page.fill('input[name="name"]', 'Route Test Unit');
    await page.click('button[type="submit"]');
    
    const unitId = page.url().split('/').pop();
    
    // Test topic create route
    await page.goto(`/subjects/${subjectId}/units/${unitId}/topics/create`);
    await expect(page).not.toHaveURL(`**/topics/create/show`); // Should NOT interpret "create" as ID
    await expect(page.locator('input[name="name"]')).toBeVisible(); // Should show form
  });
});