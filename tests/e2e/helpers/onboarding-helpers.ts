import { Page, expect } from '@playwright/test';
import { ModalHelper, ElementHelper } from './modal-helpers';

/**
 * Onboarding wizard helpers for E2E tests
 * 
 * Provides reusable helper methods for interacting with the onboarding wizard:
 * - Complete wizard workflows
 * - Add children with various configurations
 * - Select subjects for children
 * - Skip functionality
 * - Validation helpers
 */

export interface ChildData {
  name: string;
  age: string;
  independence: '1' | '2' | '3' | '4'; // Needs Help, Basic, Intermediate, Independent
}

export interface SubjectData {
  standard: string[]; // Array of standard subject names to select
  custom: string[];   // Array of custom subject names to add
}

export class OnboardingHelper {
  private modalHelper: ModalHelper;
  private elementHelper: ElementHelper;

  constructor(private page: Page) {
    this.modalHelper = new ModalHelper(page);
    this.elementHelper = new ElementHelper(page);
  }

  /**
   * Register a test user and navigate to onboarding
   * Uses the same robust pattern as working kids mode tests
   */
  async registerAndStartOnboarding(userSuffix: string = Date.now().toString()): Promise<void> {
    const testUser = {
      name: `Onboarding User ${userSuffix}`,
      email: `onboarding-test-${Date.now()}-${Math.random().toString(36).substring(7)}@example.com`,
      password: 'password123'
    };

    console.log(`Creating test user for onboarding: ${testUser.email}`);
    
    // Try registration first
    let registrationSuccess = false;
    try {
      await this.page.goto('/register', { waitUntil: 'networkidle' });
      
      // Wait for form to be visible and interactive
      await expect(this.page.locator('input[name="name"]')).toBeVisible({ timeout: 5000 });
      await expect(this.page.locator('input[name="email"]')).toBeVisible({ timeout: 5000 });
      
      await this.page.fill('input[name="name"]', testUser.name);
      await this.page.fill('input[name="email"]', testUser.email);
      await this.page.fill('input[name="password"]', testUser.password);
      await this.page.fill('input[name="password_confirmation"]', testUser.password);
      
      // Submit registration form
      await this.page.click('button[type="submit"]');
      
      console.log('Registration form submitted, waiting for response...');
      
      // Wait for either success (onboarding for new users, or dashboard) or failure (stay on register page with errors)
      await Promise.race([
        this.page.waitForURL('/onboarding', { timeout: 8000 }).then(() => {
          registrationSuccess = true;
          console.log('Registration successful - redirected to onboarding (new user flow)');
        }),
        this.page.waitForURL('/dashboard', { timeout: 8000 }).then(() => {
          registrationSuccess = true;
          console.log('Registration successful - redirected to dashboard');
        }),
        this.page.waitForSelector('.text-red-500, .alert-danger, .error', { timeout: 8000 }).then(() => {
          console.log('Registration failed - error messages detected');
        }),
        new Promise((_, reject) => setTimeout(() => reject(new Error('Registration timeout')), 8000))
      ]).catch((e) => {
        console.log('Registration attempt timed out or failed:', e.message);
      });
      
    } catch (registrationError) {
      console.log('Registration attempt failed:', registrationError);
    }

    // If registration failed or didn't redirect to dashboard, try login with known test user
    if (!registrationSuccess) {
      console.log('Registration failed, attempting login with test user...');
      
      try {
        await this.page.goto('/login', { waitUntil: 'networkidle' });
        
        // Try with a known test user that should exist
        await this.page.fill('input[name="email"]', 'test@example.com');
        await this.page.fill('input[name="password"]', 'password123');
        
        await this.page.click('button[type="submit"]');
        
        // Wait for login to complete
        await Promise.race([
          this.page.waitForURL('/dashboard', { timeout: 8000 }).then(() => {
            console.log('Login successful with test user');
            registrationSuccess = true;
          }),
          this.page.waitForTimeout(8000).then(() => {
            console.log('Login attempt timed out');
          })
        ]);
      } catch (loginError) {
        console.log('Login attempt failed:', loginError);
      }
    }

    // Final check - ensure we're authenticated (could be on dashboard or onboarding)
    const currentUrl = this.page.url();
    const isAuthenticated = registrationSuccess || currentUrl.includes('/dashboard') || currentUrl.includes('/onboarding');
    
    if (!isAuthenticated) {
      throw new Error('Failed to authenticate user for onboarding test - neither registration nor login worked');
    }

    // Navigate to onboarding (or stay if already there)
    if (currentUrl.includes('/onboarding')) {
      console.log('Authentication successful - already on onboarding page');
      return; // Already on onboarding, no need to navigate
    } else {
      console.log('Authentication successful, navigating to onboarding...');
    }
    await this.page.goto('/onboarding');
    await this.page.waitForURL('/onboarding', { timeout: 10000 });
    await expect(this.page.getByTestId('step-1')).toBeVisible({ timeout: 10000 });
  }

  /**
   * Complete the full onboarding wizard with provided data
   */
  async completeOnboardingWizard(
    children: ChildData[],
    subjects: { [childName: string]: SubjectData } = {}
  ): Promise<void> {
    // Step 1: Welcome
    await this.completeWelcomeStep();
    
    // Step 2: Children
    await this.addChildren(children);
    
    // Step 3: Subjects
    await this.selectSubjectsForChildren(children, subjects);
    
    // Step 4: Review and Complete
    await this.completeReviewStep();
  }

  /**
   * Complete Step 1 (Welcome)
   */
  async completeWelcomeStep(): Promise<void> {
    await expect(this.page.getByTestId('step-1')).toBeVisible({ timeout: 10000 });
    await expect(this.page.locator('h1')).toContainText('Welcome to Homeschool Hub!');
    await this.page.getByTestId('next-button').click();
  }

  /**
   * Add multiple children in Step 2
   */
  async addChildren(children: ChildData[]): Promise<void> {
    await expect(this.page.getByTestId('step-2')).toBeVisible({ timeout: 10000 });
    
    if (children.length === 0) {
      throw new Error('At least one child is required for onboarding');
    }
    
    // Add first child (always exists)
    await this.addChild(children[0], 0);
    
    // Add additional children
    for (let i = 1; i < children.length; i++) {
      await this.page.getByTestId('add-another-child').click();
      await this.addChild(children[i], i);
    }
    
    // Submit children form
    await this.page.getByTestId('next-button').click();
    await expect(this.page.getByTestId('form-success')).toBeVisible({ timeout: 5000 });
    await expect(this.page.getByTestId('step-3')).toBeVisible({ timeout: 10000 });
  }

  /**
   * Add a single child at the specified index
   */
  async addChild(child: ChildData, index: number): Promise<void> {
    await this.page.getByTestId(`child-name-${index}`).fill(child.name);
    await this.page.selectOption(`[data-testid="child-age-${index}"]`, child.age);
    await this.page.selectOption(`[data-testid="child-independence-${index}"]`, child.independence);
    
    // Verify the child was added correctly
    await expect(this.page.getByTestId(`child-name-${index}`)).toHaveValue(child.name);
  }

  /**
   * Select subjects for all children in Step 3
   */
  async selectSubjectsForChildren(
    children: ChildData[],
    subjects: { [childName: string]: SubjectData } = {}
  ): Promise<void> {
    await expect(this.page.getByTestId('step-3')).toBeVisible({ timeout: 10000 });
    
    // Verify all children are displayed
    for (const child of children) {
      await expect(this.page.locator(`text=${child.name}`)).toBeVisible();
      await expect(this.page.locator(`text=${child.age} years old`)).toBeVisible();
    }
    
    // Configure subjects for each child
    for (let i = 0; i < children.length; i++) {
      const child = children[i];
      const childSubjects = subjects[child.name];
      
      if (childSubjects) {
        await this.selectSubjectsForChild(child.name, childSubjects, i);
      }
    }
    
    // Submit subjects form
    await this.page.getByTestId('next-button').click();
    await expect(this.page.getByTestId('subjects-form-success')).toBeVisible({ timeout: 5000 });
    await expect(this.page.getByTestId('step-4')).toBeVisible({ timeout: 10000 });
  }

  /**
   * Select subjects for a specific child
   */
  async selectSubjectsForChild(
    childName: string,
    subjectData: SubjectData,
    childIndex: number
  ): Promise<void> {
    const childSection = this.page.getByTestId(`child-subjects-${childIndex}`);
    
    // First, uncheck all existing subjects if we want to start fresh
    if (subjectData.standard.length > 0) {
      // We'll selectively check/uncheck based on what we want
      const allCheckboxes = childSection.locator('input[type="checkbox"]:not([name*="skip"]):not([name*="custom"])');
      const count = await allCheckboxes.count();
      
      for (let i = 0; i < count; i++) {
        const checkbox = allCheckboxes.nth(i);
        const value = await checkbox.getAttribute('value') || '';
        
        const shouldBeChecked = subjectData.standard.some(subject => 
          value.toLowerCase().includes(subject.toLowerCase())
        );
        
        const isChecked = await checkbox.isChecked();
        
        if (shouldBeChecked && !isChecked) {
          await checkbox.check();
        } else if (!shouldBeChecked && isChecked) {
          await checkbox.uncheck();
        }
      }
    }
    
    // Add custom subjects
    if (subjectData.custom.length > 0) {
      for (let i = 0; i < subjectData.custom.length; i++) {
        const customInput = childSection.locator('input[name*="custom"]').nth(i);
        
        if (i > 0) {
          // Need to add another custom subject field
          await childSection.locator('text=Add Custom Subject').click();
        }
        
        await customInput.fill(subjectData.custom[i]);
      }
    }
  }

  /**
   * Complete Step 4 (Review and Complete)
   */
  async completeReviewStep(): Promise<void> {
    await expect(this.page.getByTestId('step-4')).toBeVisible({ timeout: 10000 });
    await expect(this.page.locator('h2:has-text("🎉 Your Homeschool is Ready!")')).toBeVisible();
    
    // Complete the onboarding
    await this.page.getByTestId('complete-onboarding-button').click();
    
    // Wait for completion and redirect to dashboard
    await expect(this.page.locator('text=Completing Setup...')).toBeVisible();
    await expect(this.page.locator('text=🎉 Setup complete! Welcome to your homeschool hub!')).toBeVisible({ timeout: 15000 });
    await this.page.waitForURL('/dashboard', { timeout: 15000 });
  }

  /**
   * Skip the entire onboarding wizard
   */
  async skipOnboarding(): Promise<void> {
    await expect(this.page.getByTestId('step-1')).toBeVisible({ timeout: 10000 });
    
    // Click skip setup button
    await expect(this.page.getByTestId('skip-button')).toBeVisible();
    await this.page.getByTestId('skip-button').click();
    
    // Should redirect to dashboard
    await this.page.waitForURL('/dashboard', { timeout: 10000 });
  }

  /**
   * Navigate backwards in the wizard
   */
  async goToPreviousStep(): Promise<void> {
    const previousButton = this.page.getByTestId('previous-button');
    await expect(previousButton).toBeVisible();
    await previousButton.click();
    
    // Wait a moment for navigation
    await this.page.waitForTimeout(1000);
  }

  /**
   * Navigate backwards from review step
   */
  async goBackFromReview(): Promise<void> {
    await expect(this.page.getByTestId('step-4')).toBeVisible();
    const backButton = this.page.getByTestId('review-back-button');
    await expect(backButton).toBeVisible();
    await backButton.click();
    
    // Should go back to step 3
    await expect(this.page.getByTestId('step-3')).toBeVisible({ timeout: 5000 });
  }

  /**
   * Verify children appear in review step
   */
  async verifyChildrenInReview(children: ChildData[]): Promise<void> {
    await expect(this.page.getByTestId('step-4')).toBeVisible();
    
    // Check children count
    const childrenCount = children.length;
    const countText = childrenCount === 1 ? '1 Child Added' : `${childrenCount} Children Added`;
    await expect(this.page.locator(`h3:has-text("${countText}")`)).toBeVisible();
    
    // Check each child appears
    for (const child of children) {
      await expect(this.page.locator(`text=${child.name}`)).toBeVisible();
      await expect(this.page.locator(`text=${child.age} years old`)).toBeVisible();
      
      // Check independence level display
      const independenceLabels = {
        '1': 'Needs Help',
        '2': 'Basic',
        '3': 'Intermediate', 
        '4': 'Independent'
      };
      const expectedLabel = independenceLabels[child.independence];
      await expect(this.page.locator(`text=${expectedLabel}`)).toBeVisible();
    }
  }

  /**
   * Verify subjects appear in review step
   */
  async verifySubjectsInReview(expectedSubjects: string[]): Promise<void> {
    await expect(this.page.getByTestId('step-4')).toBeVisible();
    await expect(this.page.locator('h3:has-text("Subjects Created")')).toBeVisible();
    
    for (const subject of expectedSubjects) {
      await expect(this.page.locator(`span:has-text("${subject}")`)).toBeVisible();
    }
  }

  /**
   * Verify dashboard has the expected children after onboarding
   */
  async verifyDashboardHasChildren(expectedChildren: string[]): Promise<void> {
    // Should be on dashboard
    await expect(this.page).toHaveURL('/dashboard');
    
    // Each child should appear on the dashboard
    for (const childName of expectedChildren) {
      await expect(this.page.locator(`text=${childName}`)).toBeVisible({ timeout: 10000 });
    }
  }

  /**
   * Verify subjects were created in the system
   */
  async verifySubjectsWereCreated(expectedSubjects: string[]): Promise<void> {
    await this.page.goto('/subjects');
    
    for (const subject of expectedSubjects) {
      await expect(this.page.locator(`text=${subject}`)).toBeVisible();
    }
  }

  /**
   * Verify onboarding doesn't show again after completion
   */
  async verifyOnboardingCompleted(): Promise<void> {
    await this.page.goto('/onboarding');
    
    // Should redirect away from onboarding
    await expect(this.page).toHaveURL('/dashboard');
  }

  /**
   * Get the current step number
   */
  async getCurrentStep(): Promise<number> {
    for (let step = 1; step <= 4; step++) {
      const stepElement = this.page.getByTestId(`step-${step}`);
      const isVisible = await stepElement.isVisible().catch(() => false);
      
      if (isVisible) {
        return step;
      }
    }
    
    throw new Error('No visible step found');
  }

  /**
   * Wait for form submission and success message
   */
  async waitForFormSuccess(step: number): Promise<void> {
    if (step === 2) {
      await expect(this.page.getByTestId('form-success')).toBeVisible({ timeout: 5000 });
    } else if (step === 3) {
      await expect(this.page.getByTestId('subjects-form-success')).toBeVisible({ timeout: 5000 });
    }
  }

  /**
   * Fill form with validation error testing
   */
  async testFormValidation(step: number): Promise<void> {
    if (step === 2) {
      // Try to submit empty children form
      await this.page.getByTestId('next-button').click();
      
      // Should show validation errors
      await expect(this.page.getByTestId('form-error')).toBeVisible();
      await expect(this.page.locator('text=Child name is required')).toBeVisible();
      await expect(this.page.locator('text=Child age is required')).toBeVisible();
    } else if (step === 3) {
      // Uncheck all subjects and try to submit
      const checkboxes = this.page.locator('input[type="checkbox"]:not([name*="skip"])');
      const count = await checkboxes.count();
      
      for (let i = 0; i < count; i++) {
        await checkboxes.nth(i).uncheck();
      }
      
      // Next button should be disabled
      await expect(this.page.getByTestId('next-button')).toBeDisabled();
    }
  }

  /**
   * Create a simple test scenario with one child
   */
  static createSimpleScenario(): { children: ChildData[], subjects: { [key: string]: SubjectData } } {
    const children: ChildData[] = [
      {
        name: 'Emma Thompson',
        age: '8',
        independence: '2'
      }
    ];
    
    const subjects: { [key: string]: SubjectData } = {
      'Emma Thompson': {
        standard: ['Mathematics', 'Reading/Language Arts', 'Science'],
        custom: ['Piano Lessons']
      }
    };
    
    return { children, subjects };
  }

  /**
   * Create a complex test scenario with multiple children
   */
  static createComplexScenario(): { children: ChildData[], subjects: { [key: string]: SubjectData } } {
    const children: ChildData[] = [
      {
        name: 'Alice Johnson',
        age: '7',
        independence: '1'
      },
      {
        name: 'Bob Johnson', 
        age: '12',
        independence: '3'
      },
      {
        name: 'Charlie Johnson',
        age: '16',
        independence: '4'
      }
    ];
    
    const subjects: { [key: string]: SubjectData } = {
      'Alice Johnson': {
        standard: ['Mathematics', 'Reading/Language Arts'],
        custom: ['Art']
      },
      'Bob Johnson': {
        standard: ['Mathematics', 'Science', 'Social Studies'],
        custom: ['Coding']
      },
      'Charlie Johnson': {
        standard: ['Mathematics', 'Science', 'English'],
        custom: ['Advanced Physics']
      }
    };
    
    return { children, subjects };
  }

  /**
   * Create a custom subjects only scenario
   */
  static createCustomSubjectsScenario(): { children: ChildData[], subjects: { [key: string]: SubjectData } } {
    const children: ChildData[] = [
      {
        name: 'Creative Kid',
        age: '10',
        independence: '3'
      }
    ];
    
    const subjects: { [key: string]: SubjectData } = {
      'Creative Kid': {
        standard: [], // No standard subjects
        custom: ['Music Composition', 'Digital Art', 'Creative Writing']
      }
    };
    
    return { children, subjects };
  }
}

/**
 * Quick helper functions for common scenarios
 */

/**
 * Complete basic onboarding with one elementary child
 */
export async function completeBasicOnboarding(page: Page): Promise<void> {
  const helper = new OnboardingHelper(page);
  const { children, subjects } = OnboardingHelper.createSimpleScenario();
  
  await helper.registerAndStartOnboarding();
  await helper.completeOnboardingWizard(children, subjects);
}

/**
 * Complete complex onboarding with multiple children of different ages
 */
export async function completeComplexOnboarding(page: Page): Promise<void> {
  const helper = new OnboardingHelper(page);
  const { children, subjects } = OnboardingHelper.createComplexScenario();
  
  await helper.registerAndStartOnboarding();
  await helper.completeOnboardingWizard(children, subjects);
}

/**
 * Skip onboarding entirely
 */
export async function skipCompleteOnboarding(page: Page): Promise<void> {
  const helper = new OnboardingHelper(page);
  
  await helper.registerAndStartOnboarding();
  await helper.skipOnboarding();
}