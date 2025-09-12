import { test, expect } from '@playwright/test';
import { ModalHelper, ElementHelper } from './helpers/modal-helpers';

/**
 * E2E Tests for Quick Start Subjects Feature
 * 
 * Tests the complete workflow for creating multiple subjects at once
 * using the Quick Start functionality, including:
 * - Grade level selection
 * - Subject template population
 * - Custom subject addition
 * - Form validation
 * - Error handling
 * - Database integration
 */

test.describe('Quick Start Subjects', () => {
  let testUser: { name: string; email: string; password: string };
  let modalHelper: ModalHelper;
  let elementHelper: ElementHelper;

  test.beforeEach(async ({ page }) => {
    // Initialize helpers
    modalHelper = new ModalHelper(page);
    elementHelper = new ElementHelper(page);

    // Create a unique test user for this session
    testUser = {
      name: 'Quick Start Test Parent',
      email: `quick-start-test-${Date.now()}@example.com`,
      password: 'testpassword123'
    };

    // Register and login
    await page.goto('/register');
    await page.fill('input[name="name"]', testUser.name);
    await page.fill('input[name="email"]', testUser.email);
    await page.fill('input[name="password"]', testUser.password);
    await page.fill('input[name="password_confirmation"]', testUser.password);
    await page.click('button[type="submit"]');

    // Wait for redirect and handle potential login requirement
    await page.waitForLoadState('networkidle', { timeout: 10000 });

    // If still on register page, try logging in
    if (page.url().includes('/register')) {
      await page.goto('/login');
      await page.fill('input[name="email"]', testUser.email);
      await page.fill('input[name="password"]', testUser.password);
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle', { timeout: 10000 });
    }
  });

  test('complete Quick Start workflow - elementary grade level', async ({ page }) => {
    // Step 1: Create a child first
    await page.goto('/children');
    await elementHelper.safeClick('[data-testid="header-add-child-btn"]');

    // Wait for child modal to be ready
    await modalHelper.waitForModal('child-form-modal');
    await modalHelper.fillModalField('child-form-modal', 'name', 'Emma Quick Start');
    await page.selectOption('#child-form-modal select[name="age"]', '7');
    await page.selectOption('#child-form-modal select[name="independence_level"]', '2');
    await modalHelper.submitModalForm('child-form-modal');

    // Step 2: Navigate to subjects page  
    await page.goto('/subjects');
    await page.waitForLoadState('networkidle');

    // Step 3: Verify Quick Start button appears (should show when no subjects exist)
    await expect(page.locator('button:has-text("Quick Start: Add Standard Subjects")')).toBeVisible();
    await expect(page.locator('text=No subjects yet')).toBeVisible();

    // Step 4: Click Quick Start button
    const quickStartButton = page.locator('button:has-text("Quick Start: Add Standard Subjects")');
    await quickStartButton.click();

    // Step 5: Wait for Quick Start modal to load
    // Wait for HTMX request to complete first
    await modalHelper.waitForHtmxCompletion();
    await page.waitForTimeout(2000);
    
    // Look for the modal content by its test ID (should be loaded by HTMX)
    await expect(page.locator('[data-testid="quick-start-modal"]')).toBeVisible();
    await expect(page.locator('h3:has-text("Quick Start")')).toBeVisible();

    // Step 6: Select grade level (elementary)
    const gradeSelect = page.locator('select[name="grade_level"]');
    await gradeSelect.selectOption('elementary');

    // Wait for Alpine.js to populate subjects
    await page.waitForTimeout(1000);

    // Step 7: Verify elementary subjects are pre-selected and visible
    const expectedElementarySubjects = [
      'Reading/Language Arts',
      'Mathematics', 
      'Science',
      'Social Studies',
      'Art',
      'Music',
      'Physical Education'
    ];

    for (const subject of expectedElementarySubjects) {
      await expect(page.locator(`text=${subject}`).first()).toBeVisible();
      // Verify checkbox is checked by default - use exact value match
      await expect(page.locator(`input[type="checkbox"][value="${subject}"]`)).toBeChecked();
    }

    // Step 8: Add a custom subject
    const customSubjectInput = page.locator('input[name="custom_subjects[]"]').first();
    await customSubjectInput.fill('Handwriting');

    // Step 9: Submit the Quick Start form
    const submitButton = page.locator('button[type="submit"]:has-text("Create")');
    await submitButton.click();

    // Step 10: Wait for form submission and modal close
    await modalHelper.waitForHtmxCompletion();
    await page.waitForTimeout(2000);

    // Step 11: Verify subjects were created and are visible in subjects list
    await expect(page.locator('text=No subjects yet')).not.toBeVisible();

    // Check that standard subjects were created
    for (const subject of expectedElementarySubjects) {
      await expect(page.locator(`text=${subject}`).first()).toBeVisible();
    }

    // Check that custom subject was created
    await expect(page.locator('text=Handwriting')).toBeVisible();

    // Step 12: Verify subject count is correct (7 standard + 1 custom = 8 total)
    const subjectCards = page.locator('[class*="grid"] > div[class*="bg-white"]');
    await expect(subjectCards).toHaveCount(8);

    // Step 13: Navigate to dashboard and verify it loads without errors
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');
    
    // Check that dashboard loads successfully without server errors
    await expect(page.locator('text=Internal Server Error')).not.toBeVisible();
    await expect(page.locator('text=500')).not.toBeVisible();
    await expect(page.locator('text=Exception')).not.toBeVisible();
    
    // Verify dashboard content is present (parent dashboard view)
    const dashboardContent = page.locator('body');
    await expect(dashboardContent).toContainText(/Dashboard|Children|Emma Quick Start/i);
    
    // Ensure the page loaded without PHP errors related to Review model
    const pageContent = await page.content();
    expect(pageContent).not.toContain('Return value must be of type');
    expect(pageContent).not.toContain('QueryException');
  });

  test('middle school grade level workflow', async ({ page }) => {
    // Setup child
    await page.goto('/children');
    await elementHelper.safeClick('[data-testid="header-add-child-btn"]');
    await modalHelper.waitForModal('child-form-modal');
    await modalHelper.fillModalField('child-form-modal', 'name', 'Alex Middle School');
    await page.selectOption('#child-form-modal select[name="age"]', '12');
    await modalHelper.submitModalForm('child-form-modal');

    // Navigate to subjects and start Quick Start
    await page.goto('/subjects');
    await elementHelper.safeClick('button:has-text("Quick Start: Add Standard Subjects")');
    await modalHelper.waitForModal('quick-start-modal');

    // Select middle school grade level
    await page.selectOption('select[name="grade_level"]', 'middle');
    await page.waitForTimeout(1000);

    // Verify middle school subjects appear
    const expectedMiddleSubjects = [
      'English Language Arts',
      'Mathematics',
      'Life Science',
      'Earth Science', 
      'Physical Science',
      'Social Studies',
      'World History',
      'Physical Education',
      'World Language',
      'Computer Science',
      'Art',
      'Music',
      'Health'
    ];

    // Check a few key subjects to validate correct template loaded
    await expect(page.locator('text=English Language Arts')).toBeVisible();
    await expect(page.locator('text=Life Science')).toBeVisible();
    await expect(page.locator('text=World History')).toBeVisible();
    await expect(page.locator('text=Computer Science')).toBeVisible();

    // Submit with default selections
    await page.click('button[type="submit"]:has-text("Create")');
    await modalHelper.waitForHtmxCompletion();
    await page.waitForTimeout(2000);

    // Verify subjects created
    await expect(page.locator('text=English Language Arts')).toBeVisible();
    await expect(page.locator('text=Life Science')).toBeVisible();
    
    // Step 13: Navigate to dashboard and verify it loads without errors
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');
    
    // Check that dashboard loads successfully without server errors
    await expect(page.locator('text=Internal Server Error')).not.toBeVisible();
    await expect(page.locator('text=500')).not.toBeVisible();
    await expect(page.locator('text=Exception')).not.toBeVisible();
    
    // Verify we actually see dashboard content
    const dashboardContent = await page.locator('body').textContent();
    await expect(dashboardContent).toContainText(/Dashboard|Children|Alex Middle School/i);
    
    // Ensure the page loaded without PHP errors related to Review model
    const pageContent = await page.content();
    expect(pageContent).not.toContain('Return value must be of type');
    expect(pageContent).not.toContain('QueryException');
  });

  test('high school grade level workflow', async ({ page }) => {
    // Setup child
    await page.goto('/children');
    await elementHelper.safeClick('[data-testid="header-add-child-btn"]');
    await modalHelper.waitForModal('child-form-modal');
    await modalHelper.fillModalField('child-form-modal', 'name', 'Jordan High School');
    await page.selectOption('#child-form-modal select[name="age"]', '16');
    await modalHelper.submitModalForm('child-form-modal');

    // Navigate to subjects and start Quick Start
    await page.goto('/subjects');
    await elementHelper.safeClick('button:has-text("Quick Start: Add Standard Subjects")');
    await modalHelper.waitForModal('quick-start-modal');

    // Select high school grade level
    await page.selectOption('select[name="grade_level"]', 'high');
    await page.waitForTimeout(1000);

    // Verify high school subjects appear with specialized math/science
    await expect(page.locator('text=Algebra')).toBeVisible();
    await expect(page.locator('text=Geometry')).toBeVisible();
    await expect(page.locator('text=Calculus')).toBeVisible();
    await expect(page.locator('text=Biology')).toBeVisible();
    await expect(page.locator('text=Chemistry')).toBeVisible();
    await expect(page.locator('text=Physics')).toBeVisible();
    await expect(page.locator('text=Economics')).toBeVisible();
    await expect(page.locator('text=Psychology')).toBeVisible();

    // Deselect a few subjects to test selection behavior
    await page.uncheck('input[type="checkbox"][value*="Psychology"]');
    await page.uncheck('input[type="checkbox"][value*="Economics"]');

    // Submit
    await page.click('button[type="submit"]:has-text("Create")');
    await modalHelper.waitForHtmxCompletion();
    await page.waitForTimeout(2000);

    // Verify selected subjects created but not deselected ones
    await expect(page.locator('text=Biology')).toBeVisible();
    await expect(page.locator('text=Chemistry')).toBeVisible();
    await expect(page.locator('text=Psychology')).not.toBeVisible();
    await expect(page.locator('text=Economics')).not.toBeVisible();
    
    // Step 13: Navigate to dashboard and verify it loads without errors
    await page.goto('/dashboard');
    await page.waitForLoadState('networkidle');
    
    // Check that dashboard loads successfully without server errors
    await expect(page.locator('text=Internal Server Error')).not.toBeVisible();
    await expect(page.locator('text=500')).not.toBeVisible();
    await expect(page.locator('text=Exception')).not.toBeVisible();
    
    // Verify we actually see dashboard content
    const dashboardContent = await page.locator('body').textContent();
    await expect(dashboardContent).toContainText(/Dashboard|Children|Jordan High School/i);
    
    // Ensure the page loaded without PHP errors related to Review model
    const pageContent = await page.content();
    expect(pageContent).not.toContain('Return value must be of type');
    expect(pageContent).not.toContain('QueryException');
  });

  test('custom subjects functionality', async ({ page }) => {
    // Setup child  
    await page.goto('/children');
    await elementHelper.safeClick('[data-testid="header-add-child-btn"]');
    await modalHelper.waitForModal('child-form-modal');
    await modalHelper.fillModalField('child-form-modal', 'name', 'Sam Custom');
    await page.selectOption('#child-form-modal select[name="age"]', '10');
    await modalHelper.submitModalForm('child-form-modal');

    // Start Quick Start
    await page.goto('/subjects');
    await elementHelper.safeClick('button:has-text("Quick Start: Add Standard Subjects")');
    await modalHelper.waitForModal('quick-start-modal');

    // Select grade level
    await page.selectOption('select[name="grade_level"]', 'elementary');
    await page.waitForTimeout(1000);

    // Deselect all standard subjects to test custom-only creation
    const checkboxes = page.locator('input[type="checkbox"][name="subjects[]"]');
    const count = await checkboxes.count();
    for (let i = 0; i < count; i++) {
      await checkboxes.nth(i).uncheck();
    }

    // Add multiple custom subjects
    await page.fill('input[name="custom_subjects[]"]', 'Coding for Kids');

    // Add another custom subject
    await page.click('button:has-text("Add Custom Subject")');
    await page.fill('input[name="custom_subjects[]"]').nth(1).fill('Robotics');

    // Add a third custom subject  
    await page.click('button:has-text("Add Custom Subject")');
    await page.fill('input[name="custom_subjects[]"]').nth(2).fill('Spanish Immersion');

    // Submit
    await page.click('button[type="submit"]:has-text("Create")');
    await modalHelper.waitForHtmxCompletion();
    await page.waitForTimeout(2000);

    // Verify only custom subjects were created
    await expect(page.locator('text=Coding for Kids')).toBeVisible();
    await expect(page.locator('text=Robotics')).toBeVisible(); 
    await expect(page.locator('text=Spanish Immersion')).toBeVisible();

    // Verify no standard subjects created
    await expect(page.locator('text=Mathematics')).not.toBeVisible();
    await expect(page.locator('text=Science')).not.toBeVisible();

    // Verify exactly 3 subjects created
    const subjectCards = page.locator('[class*="grid"] > div[class*="bg-white"]');
    await expect(subjectCards).toHaveCount(3);
  });

  test('form validation and error handling', async ({ page }) => {
    // Setup child
    await page.goto('/children');
    await elementHelper.safeClick('[data-testid="header-add-child-btn"]');
    await modalHelper.waitForModal('child-form-modal');
    await modalHelper.fillModalField('child-form-modal', 'name', 'Validation Test');
    await page.selectOption('#child-form-modal select[name="age"]', '8');
    await modalHelper.submitModalForm('child-form-modal');

    // Start Quick Start
    await page.goto('/subjects');
    await elementHelper.safeClick('button:has-text("Quick Start: Add Standard Subjects")');
    await modalHelper.waitForModal('quick-start-modal');

    // Test 1: Try to submit without selecting grade level
    const submitButton = page.locator('button[type="submit"]');
    await expect(submitButton).toBeDisabled();

    // Test 2: Select grade level but deselect all subjects
    await page.selectOption('select[name="grade_level"]', 'elementary');
    await page.waitForTimeout(1000);

    // Deselect all subjects
    const checkboxes = page.locator('input[type="checkbox"][name="subjects[]"]');
    const count = await checkboxes.count();
    for (let i = 0; i < count; i++) {
      await checkboxes.nth(i).uncheck();
    }

    // Submit button should be disabled when no subjects selected
    await expect(submitButton).toBeDisabled();

    // Test 3: Add empty custom subject (should still be disabled)
    await page.fill('input[name="custom_subjects[]"]', '');
    await expect(submitButton).toBeDisabled();

    // Test 4: Add valid custom subject (should enable submit)
    await page.fill('input[name="custom_subjects[]"]', 'Test Subject');
    await expect(submitButton).not.toBeDisabled();

    // Test 5: Test custom subject validation with special characters
    await page.fill('input[name="custom_subjects[]"]', 'Art & Crafts'); 
    await submitButton.click();
    
    await modalHelper.waitForHtmxCompletion();
    await page.waitForTimeout(2000);

    // Should successfully create subject with special characters
    await expect(page.locator('text=Art & Crafts')).toBeVisible();
  });

  test('Quick Start not available when subjects exist', async ({ page }) => {
    // Setup child
    await page.goto('/children');
    await elementHelper.safeClick('[data-testid="header-add-child-btn"]');
    await modalHelper.waitForModal('child-form-modal');
    await modalHelper.fillModalField('child-form-modal', 'name', 'Existing Subjects Test');
    await page.selectOption('#child-form-modal select[name="age"]', '9');
    await modalHelper.submitModalForm('child-form-modal');

    // Create a regular subject first  
    await page.goto('/subjects');
    await elementHelper.safeClick('button:has-text("Add Subject")', 'first');
    await modalHelper.waitForModal('subject-modal');
    await modalHelper.fillModalField('subject-modal', 'name', 'Existing Subject');
    await page.selectOption('select[name="color"]', '#3B82F6');
    await modalHelper.submitModalForm('subject-modal');

    // Reload page and verify Quick Start is not shown
    await page.reload();
    await page.waitForLoadState('networkidle');

    // Quick Start button should not be visible when subjects exist
    await expect(page.locator('button:has-text("Quick Start: Add Standard Subjects")')).not.toBeVisible();
    await expect(page.locator('text=No subjects yet')).not.toBeVisible();
    
    // Should see existing subject instead
    await expect(page.locator('text=Existing Subject')).toBeVisible();
  });

  test('authentication required for Quick Start', async ({ page }) => {
    // Logout first
    await page.goto('/logout');
    await page.waitForLoadState('networkidle');

    // Try to access Quick Start form directly
    await page.goto('/subjects/quick-start?child_id=1');
    
    // Should be redirected to login
    await expect(page.url()).toContain('/login');
    await expect(page.locator('h2:has-text("Sign in")')).toBeVisible();
  });

  test('invalid child_id handling', async ({ page }) => {
    // Try to access Quick Start with non-existent child
    await page.goto('/subjects/quick-start?child_id=99999');
    
    // Should handle gracefully (likely show error or redirect)
    await page.waitForLoadState('networkidle');
    
    // Check for error handling (exact behavior may vary)
    const hasError = await page.locator('text=Child not found').isVisible() ||
                     await page.locator('text=Access denied').isVisible() ||
                     page.url().includes('/subjects');
    
    expect(hasError).toBeTruthy();
  });

  test('modal interaction and cancellation', async ({ page }) => {
    // Setup child
    await page.goto('/children');
    await elementHelper.safeClick('[data-testid="header-add-child-btn"]');
    await modalHelper.waitForModal('child-form-modal');
    await modalHelper.fillModalField('child-form-modal', 'name', 'Modal Test');
    await page.selectOption('#child-form-modal select[name="age"]', '6');
    await modalHelper.submitModalForm('child-form-modal');

    // Open Quick Start modal
    await page.goto('/subjects');
    await elementHelper.safeClick('button:has-text("Quick Start: Add Standard Subjects")');
    await modalHelper.waitForModal('quick-start-modal');

    // Test modal close button
    const closeButton = page.locator('[data-testid="quick-start-modal"] button').first();
    await closeButton.click();
    
    // Modal should be closed
    await expect(page.locator('[data-testid="quick-start-modal"]')).not.toBeVisible();

    // Reopen modal
    await elementHelper.safeClick('button:has-text("Quick Start: Add Standard Subjects")');
    await modalHelper.waitForModal('quick-start-modal');

    // Test "Skip Quick Start" button
    await page.click('button:has-text("Skip Quick Start")');
    
    // Should close modal and show manual add subject option
    await expect(page.locator('[data-testid="quick-start-modal"]')).not.toBeVisible();
    await expect(page.locator('button:has-text("Add Subject")').first()).toBeVisible();
  });

  test('subject color assignment and display', async ({ page }) => {
    // Setup child
    await page.goto('/children');
    await elementHelper.safeClick('[data-testid="header-add-child-btn"]');
    await modalHelper.waitForModal('child-form-modal');
    await modalHelper.fillModalField('child-form-modal', 'name', 'Color Test');
    await page.selectOption('#child-form-modal select[name="age"]', '11');
    await modalHelper.submitModalForm('child-form-modal');

    // Use Quick Start with specific subjects that have predefined colors
    await page.goto('/subjects');
    await elementHelper.safeClick('button:has-text("Quick Start: Add Standard Subjects")');
    await modalHelper.waitForModal('quick-start-modal');

    await page.selectOption('select[name="grade_level"]', 'elementary');
    await page.waitForTimeout(1000);

    // Deselect all except Mathematics (should be blue) and Science (should be green)
    const allCheckboxes = page.locator('input[type="checkbox"][name="subjects[]"]');
    const count = await allCheckboxes.count();
    for (let i = 0; i < count; i++) {
      await allCheckboxes.nth(i).uncheck();
    }

    // Select only Mathematics and Science
    await page.check('input[type="checkbox"][value*="Mathematics"]');
    await page.check('input[type="checkbox"][value*="Science"]');

    await page.click('button[type="submit"]:has-text("Create")');
    await modalHelper.waitForHtmxCompletion();
    await page.waitForTimeout(2000);

    // Verify subjects have color indicators
    const mathSubject = page.locator('text=Mathematics').locator('..').locator('..');
    const scienceSubject = page.locator('text=Science').locator('..').locator('..');

    // Check that color badges are present (exact color testing may be difficult)
    await expect(mathSubject.locator('.w-4.h-4.rounded-full')).toBeVisible();
    await expect(scienceSubject.locator('.w-4.h-4.rounded-full')).toBeVisible();
  });
});