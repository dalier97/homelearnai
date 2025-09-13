import { Page, expect } from '@playwright/test';
import { ModalHelper, ElementHelper } from './modal-helpers';

/**
 * Optimized test setup helpers for E2E tests
 * 
 * Provides fast, reusable test user and data setup to avoid repeating
 * onboarding workflows in every test. Focuses on performance optimization.
 */

export interface TestUser {
  id?: number;
  name: string;
  email: string;
  password: string;
  hasOnboarding?: boolean;
}

export interface TestChild {
  id?: number;
  name: string;
  age: string;
  independence: string;
}

export interface TestSubject {
  id?: number;
  name: string;
  childId?: number;
}

export interface SharedTestData {
  user: TestUser;
  children: TestChild[];
  subjects: TestSubject[];
}

export class TestSetupHelper {
  private modalHelper: ModalHelper;
  private elementHelper: ElementHelper;
  private static sharedData: SharedTestData | null = null;

  constructor(private page: Page) {
    this.modalHelper = new ModalHelper(page);
    this.elementHelper = new ElementHelper(page);
  }

  /**
   * Get or create shared test data for the test suite
   * This avoids creating new users and going through onboarding for every test
   */
  async getOrCreateSharedTestUser(): Promise<TestUser> {
    // If we already have a shared user, try to login with them
    if (TestSetupHelper.sharedData) {
      const userData = TestSetupHelper.sharedData.user;
      console.log(`Attempting login with existing shared user: ${userData.email}`);
      
      try {
        await this.page.goto('/login', { waitUntil: 'networkidle' });
        await this.page.fill('input[name="email"]', userData.email);
        await this.page.fill('input[name="password"]', userData.password);
        await this.page.click('button[type="submit"]');
        
        // Wait for login to complete
        const loginResult = await Promise.race([
          this.page.waitForURL('/dashboard', { timeout: 5000 }).then(() => 'success'),
          this.page.waitForTimeout(5000).then(() => 'timeout')
        ]);
        
        if (loginResult === 'success') {
          console.log('Successfully logged in with shared user');
          return userData;
        }
      } catch (error) {
        console.log('Shared user login failed, will create new user:', error);
      }
    }

    // Create new shared user
    console.log('Creating new shared test user...');
    const userData = await this.createFreshTestUser();
    
    // Complete onboarding once for this shared user
    console.log('Completing onboarding for shared user...');
    await this.completeOnboardingForUser(userData);
    
    // Store for reuse
    TestSetupHelper.sharedData = {
      user: userData,
      children: [
        {
          name: 'Shared Test Child',
          age: '10', 
          independence: '2'
        }
      ],
      subjects: [
        { name: 'Mathematics' },
        { name: 'Reading/Language Arts' },
        { name: 'Science' }
      ]
    };
    
    console.log('Shared test data created successfully');
    return userData;
  }

  /**
   * Create a completely fresh test user with unique credentials
   */
  private async createFreshTestUser(): Promise<TestUser> {
    const timestamp = Date.now();
    const randomId = Math.random().toString(36).substring(7);
    
    const userData: TestUser = {
      name: `Test User ${timestamp}`,
      email: `test-user-${timestamp}-${randomId}@example.com`,
      password: 'testpassword123'
    };

    console.log(`Creating fresh test user: ${userData.email}`);
    
    await this.page.goto('/register', { waitUntil: 'networkidle' });
    
    // Wait for form to be visible
    await expect(this.page.locator('input[name="name"]')).toBeVisible({ timeout: 5000 });
    
    // Fill registration form
    await this.page.fill('input[name="name"]', userData.name);
    await this.page.fill('input[name="email"]', userData.email);
    await this.page.fill('input[name="password"]', userData.password);
    await this.page.fill('input[name="password_confirmation"]', userData.password);
    
    // Submit registration
    await this.page.click('button[type="submit"]');
    
    // Wait for registration to complete
    const registrationResult = await Promise.race([
      this.page.waitForURL('/onboarding', { timeout: 8000 }).then(() => 'onboarding'),
      this.page.waitForURL('/dashboard', { timeout: 8000 }).then(() => 'dashboard'),
      this.page.waitForTimeout(8000).then(() => 'timeout')
    ]);
    
    if (registrationResult === 'timeout') {
      throw new Error('Registration failed or timed out');
    }
    
    console.log(`Registration successful, redirected to: ${registrationResult}`);
    return userData;
  }

  /**
   * Complete onboarding workflow optimized for speed
   */
  private async completeOnboardingForUser(userData: TestUser): Promise<void> {
    // Ensure we're on onboarding page
    const currentUrl = this.page.url();
    if (!currentUrl.includes('/onboarding')) {
      await this.page.goto('/onboarding');
    }
    
    // Wait for onboarding to load
    await expect(this.page.getByTestId('step-1')).toBeVisible({ timeout: 10000 });
    
    console.log('Completing onboarding steps...');
    
    // Step 1: Welcome (just click next)
    await this.page.getByTestId('next-button').click();
    
    // Step 2: Children
    await expect(this.page.getByTestId('step-2')).toBeVisible({ timeout: 5000 });
    await this.page.getByTestId('child-name-0').fill('Shared Test Child');
    await this.page.selectOption('[data-testid="child-grade-0"]', '7th');
    await this.page.selectOption('[data-testid="child-independence-0"]', '2');
    
    await this.page.getByTestId('next-button').click();
    
    // Wait for success and next step
    await expect(this.page.getByTestId('form-success')).toBeVisible({ timeout: 8000 });
    await expect(this.page.getByTestId('step-3')).toBeVisible({ timeout: 5000 });
    
    // Step 3: Subjects (select quickly)
    const mathCheckbox = this.page.locator('input[type="checkbox"][value*="math"], input[type="checkbox"][value*="Math"]').first();
    const readingCheckbox = this.page.locator('input[type="checkbox"][value*="reading"], input[type="checkbox"][value*="Reading"], input[type="checkbox"][value*="Language"]').first();
    
    // Try to check some standard subjects quickly
    if (await mathCheckbox.isVisible({ timeout: 2000 })) {
      await mathCheckbox.check();
    }
    if (await readingCheckbox.isVisible({ timeout: 2000 })) {
      await readingCheckbox.check();
    }
    
    // If no subjects found, check first available or skip
    const allCheckboxes = this.page.locator('input[type="checkbox"]:not([name*="skip"]):not([name*="custom"])');
    const checkboxCount = await allCheckboxes.count();
    if (checkboxCount > 0) {
      await allCheckboxes.first().check();
      if (checkboxCount > 1) {
        await allCheckboxes.nth(1).check();
      }
    }
    
    await this.page.getByTestId('next-button').click();
    
    // Wait for step 4
    await expect(this.page.getByTestId('step-4')).toBeVisible({ timeout: 5000 });
    
    // Step 4: Complete
    await this.page.getByTestId('complete-onboarding-button').click();
    
    // Wait for completion
    await expect(this.page.locator('text=🎉 Setup complete!')).toBeVisible({ timeout: 15000 });
    await this.page.waitForURL('/dashboard', { timeout: 10000 });
    
    console.log('Onboarding completed successfully');
  }

  /**
   * Quick login without onboarding for tests that need fresh sessions
   */
  async quickLogin(userData: TestUser): Promise<void> {
    console.log(`Quick login for: ${userData.email}`);
    
    // Check if already logged in by trying to go to dashboard
    await this.page.goto('/dashboard', { waitUntil: 'networkidle' });
    
    // If we're redirected to login, then we need to log in
    const currentUrl = this.page.url();
    if (currentUrl.includes('/login') || currentUrl.includes('/register')) {
      await this.page.fill('input[name="email"]', userData.email);
      await this.page.fill('input[name="password"]', userData.password);
      await this.page.click('button[type="submit"]');
      
      await this.page.waitForURL('/dashboard', { timeout: 8000 });
    }
    
    console.log('Quick login successful (or already logged in)');
  }

  /**
   * Clear all modals and overlays to ensure clean test state
   */
  async ensureCleanState(): Promise<void> {
    await this.modalHelper.resetTestState();
  }

  /**
   * Comprehensive test isolation - call this in beforeEach hooks
   */
  async isolateTest(): Promise<void> {
    console.log('🔒 Starting test isolation...');
    
    // Step 1: Reset all modal and HTMX state
    await this.modalHelper.resetTestState();
    
    // Step 2: Clear browser state that might interfere
    await this.page.evaluate(() => {
      // Clear local storage
      try {
        localStorage.clear();
      } catch (e) {
        console.log('Could not clear localStorage:', e);
      }
      
      // Clear session storage
      try {
        sessionStorage.clear();
      } catch (e) {
        console.log('Could not clear sessionStorage:', e);
      }
      
      // Reset any global variables that might persist
      if (window.testState) {
        window.testState = {};
      }
    });
    
    // Step 3: Wait for any animations or transitions to complete
    await this.page.waitForTimeout(200);
    
    console.log('✅ Test isolation completed');
  }

  /**
   * Safe button click that handles multiple button scenarios
   */
  async safeButtonClick(buttonText: string, preferredTestId?: string, timeout: number = 10000): Promise<void> {
    await this.modalHelper.safeClickButton(buttonText, preferredTestId);
  }

  /**
   * Navigate to a specific page and ensure it's ready
   */
  async navigateAndWait(path: string): Promise<void> {
    await this.page.goto(path, { waitUntil: 'networkidle' });
    await this.elementHelper.waitForPageReady();
    await this.ensureCleanState();
  }

  /**
   * Get shared test data (children, subjects, etc.)
   */
  getSharedData(): SharedTestData | null {
    return TestSetupHelper.sharedData;
  }

  /**
   * Reset shared data (useful for test isolation between test files)
   */
  static resetSharedData(): void {
    TestSetupHelper.sharedData = null;
    console.log('Shared test data reset');
  }

  /**
   * Create a child via API for faster test setup
   */
  async createChildViaAPI(name: string, age: string, independence: string): Promise<void> {
    try {
      const cookies = await this.page.context().cookies();
      const sessionCookie = cookies.find(c => c.name.includes('session'));
      const csrfToken = await this.page.getAttribute('meta[name="csrf-token"]', 'content');
      
      if (sessionCookie && csrfToken) {
        const response = await this.page.request.post('/children', {
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Cookie': `${sessionCookie.name}=${sessionCookie.value}`,
            'Content-Type': 'application/x-www-form-urlencoded',
            'HX-Request': 'true',
          },
          form: {
            name,
            age,
            independence_level: independence
          }
        });
        
        if (!response.ok()) {
          throw new Error(`API call failed: ${response.status()}`);
        }
        
        console.log(`Created child "${name}" via API`);
      }
    } catch (error) {
      console.log('Failed to create child via API, falling back to UI:', error);
      throw error;
    }
  }

  /**
   * Create a subject via API for faster test setup
   */
  async createSubjectViaAPI(name: string, childId?: number): Promise<void> {
    try {
      const cookies = await this.page.context().cookies();
      const sessionCookie = cookies.find(c => c.name.includes('session'));
      const csrfToken = await this.page.getAttribute('meta[name="csrf-token"]', 'content');
      
      if (sessionCookie && csrfToken) {
        const response = await this.page.request.post('/subjects', {
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Cookie': `${sessionCookie.name}=${sessionCookie.value}`,
            'Content-Type': 'application/x-www-form-urlencoded',
            'HX-Request': 'true',
          },
          form: {
            name,
            description: `Test subject: ${name}`,
            child_id: childId || ''
          }
        });
        
        if (!response.ok()) {
          throw new Error(`API call failed: ${response.status()}`);
        }
        
        console.log(`Created subject "${name}" via API`);
      }
    } catch (error) {
      console.log('Failed to create subject via API:', error);
      throw error;
    }
  }
}

/**
 * Quick helper functions for common test scenarios
 */

/**
 * Get a test user that has completed onboarding (shared across tests for speed)
 */
export async function getOnboardedTestUser(page: Page): Promise<TestUser> {
  const helper = new TestSetupHelper(page);
  return await helper.getOrCreateSharedTestUser();
}

/**
 * Login quickly and ensure clean state for test
 */
export async function quickTestLogin(page: Page): Promise<TestUser> {
  const helper = new TestSetupHelper(page);
  const userData = await helper.getOrCreateSharedTestUser();
  await helper.quickLogin(userData);
  await helper.ensureCleanState();
  return userData;
}

/**
 * Reset all shared data (call this in test file setup if you need isolation)
 */
export function resetSharedTestData(): void {
  TestSetupHelper.resetSharedData();
}