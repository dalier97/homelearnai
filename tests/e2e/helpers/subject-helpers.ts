import { Page, expect } from '@playwright/test';
import { ModalHelper } from './modal-helpers';

/**
 * Subject management helpers for E2E tests
 * Provides reusable functions for subject-related operations
 */

export class SubjectHelper {
  constructor(
    private page: Page,
    private modalHelper: ModalHelper
  ) {}

  /**
   * Create a single subject manually
   */
  async createSubject(name: string, color: string, childId?: string): Promise<void> {
    const createButton = this.page.locator('button:has-text("Add Subject")');
    await createButton.click();

    await this.modalHelper.waitForModal('subject-modal');
    await this.modalHelper.fillModalField('subject-modal', 'name', name);
    await this.page.selectOption('select[name="color"]', color);
    
    if (childId) {
      await this.page.selectOption('select[name="child_id"]', childId);
    }

    await this.modalHelper.submitModalForm('subject-modal');
  }

  /**
   * Open the Quick Start modal for a specific child
   */
  async openQuickStartModal(childId: string): Promise<void> {
    const quickStartButton = this.page.locator('button:has-text("Quick Start: Add Standard Subjects")');
    await expect(quickStartButton).toBeVisible();
    await quickStartButton.click();

    await this.modalHelper.waitForModal('quick-start-modal');
    await expect(this.page.locator('[data-testid="quick-start-modal"]')).toBeVisible();
  }

  /**
   * Select a grade level in the Quick Start modal and wait for subjects to populate
   */
  async selectGradeLevel(gradeLevel: 'elementary' | 'middle' | 'high'): Promise<void> {
    const gradeSelect = this.page.locator('select[name="grade_level"]');
    await gradeSelect.selectOption(gradeLevel);

    // Wait for Alpine.js to populate the subjects list
    await this.page.waitForTimeout(1000);

    // Verify subjects appeared
    await expect(this.page.locator('input[type="checkbox"][name="subjects[]"]').first()).toBeVisible();
  }

  /**
   * Toggle the selection of a specific subject
   */
  async toggleSubject(subjectName: string, shouldSelect: boolean = true): Promise<void> {
    const checkbox = this.page.locator(`input[type="checkbox"][value*="${subjectName.split(' ')[0]}"]`);
    
    if (shouldSelect) {
      await checkbox.check();
    } else {
      await checkbox.uncheck();
    }
  }

  /**
   * Deselect all recommended subjects
   */
  async deselectAllSubjects(): Promise<void> {
    const checkboxes = this.page.locator('input[type="checkbox"][name="subjects[]"]');
    const count = await checkboxes.count();
    
    for (let i = 0; i < count; i++) {
      await checkboxes.nth(i).uncheck();
    }
  }

  /**
   * Add custom subjects to the Quick Start form
   */
  async addCustomSubjects(subjects: string[]): Promise<void> {
    for (let i = 0; i < subjects.length; i++) {
      if (i > 0) {
        // Click "Add Custom Subject" button for additional subjects
        const addButton = this.page.locator('button:has-text("Add Custom Subject")');
        await addButton.click();
        await this.page.waitForTimeout(500);
      }

      // Fill the custom subject input
      const customInput = this.page.locator('input[name="custom_subjects[]"]').nth(i);
      await customInput.fill(subjects[i]);
    }
  }

  /**
   * Submit the Quick Start form and wait for completion
   */
  async submitQuickStart(): Promise<void> {
    const submitButton = this.page.locator('button[type="submit"]:has-text("Create")');
    await expect(submitButton).not.toBeDisabled();
    await submitButton.click();

    // Wait for HTMX request to complete
    await this.modalHelper.waitForHtmxCompletion();
    
    // Additional wait for modal to close and DOM to update
    await this.page.waitForTimeout(2000);
  }

  /**
   * Verify subjects are displayed in the subjects list
   */
  async verifySubjectsInList(expectedSubjects: string[]): Promise<void> {
    // Ensure we're not showing "No subjects yet"
    await expect(this.page.locator('text=No subjects yet')).not.toBeVisible();

    // Check each expected subject
    for (const subject of expectedSubjects) {
      await expect(this.page.locator(`text=${subject}`).first()).toBeVisible();
    }
  }

  /**
   * Verify subjects are NOT in the subjects list
   */
  async verifySubjectsNotInList(unexpectedSubjects: string[]): Promise<void> {
    for (const subject of unexpectedSubjects) {
      await expect(this.page.locator(`text=${subject}`)).not.toBeVisible();
    }
  }

  /**
   * Count the number of subject cards displayed
   */
  async getSubjectCount(): Promise<number> {
    const subjectCards = this.page.locator('[class*="grid"] > div[class*="bg-white"]');
    return await subjectCards.count();
  }

  /**
   * Verify that Quick Start button is visible (when no subjects exist)
   */
  async verifyQuickStartAvailable(): Promise<void> {
    await expect(this.page.locator('button:has-text("Quick Start: Add Standard Subjects")')).toBeVisible();
    await expect(this.page.locator('text=No subjects yet')).toBeVisible();
  }

  /**
   * Verify that Quick Start button is NOT visible (when subjects exist)
   */
  async verifyQuickStartNotAvailable(): Promise<void> {
    await expect(this.page.locator('button:has-text("Quick Start: Add Standard Subjects")')).not.toBeVisible();
    await expect(this.page.locator('text=No subjects yet')).not.toBeVisible();
  }

  /**
   * Get the expected subjects for a grade level
   */
  getExpectedSubjectsForGrade(gradeLevel: 'elementary' | 'middle' | 'high'): string[] {
    const subjectsByGrade = {
      elementary: [
        'Reading/Language Arts',
        'Mathematics',
        'Science',
        'Social Studies',
        'Art',
        'Music',
        'Physical Education'
      ],
      middle: [
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
      ],
      high: [
        'English Language Arts',
        'Algebra',
        'Geometry',
        'Calculus',
        'Biology',
        'Chemistry',
        'Physics',
        'World History',
        'U.S. History',
        'Foreign Language',
        'Computer Science',
        'Economics',
        'Psychology',
        'Art',
        'Physical Education'
      ]
    };

    return subjectsByGrade[gradeLevel] || [];
  }

  /**
   * Verify that the correct subjects are pre-selected for a grade level
   */
  async verifyGradeLevelSubjects(gradeLevel: 'elementary' | 'middle' | 'high'): Promise<void> {
    const expectedSubjects = this.getExpectedSubjectsForGrade(gradeLevel);

    for (const subject of expectedSubjects) {
      // Check that subject is visible in the list
      await expect(this.page.locator(`text=${subject}`).first()).toBeVisible();
      
      // Check that checkbox is checked by default
      const checkbox = this.page.locator(`input[type="checkbox"][value*="${subject.split(' ')[0]}"], input[type="checkbox"][value*="${subject.split('/')[0]}"]`);
      await expect(checkbox).toBeChecked();
    }
  }

  /**
   * Close the Quick Start modal without submitting
   */
  async cancelQuickStart(): Promise<void> {
    const cancelButton = this.page.locator('button:has-text("Skip Quick Start")');
    await cancelButton.click();

    // Wait for modal to close
    await expect(this.page.locator('[data-testid="quick-start-modal"]')).not.toBeVisible();
  }

  /**
   * Verify submit button state based on form validation
   */
  async verifySubmitButtonState(shouldBeEnabled: boolean): Promise<void> {
    const submitButton = this.page.locator('button[type="submit"]');
    
    if (shouldBeEnabled) {
      await expect(submitButton).not.toBeDisabled();
    } else {
      await expect(submitButton).toBeDisabled();
    }
  }

  /**
   * Navigate to subjects page and wait for it to load
   */
  async navigateToSubjects(): Promise<void> {
    await this.page.goto('/subjects');
    await this.page.waitForLoadState('networkidle');
  }

  /**
   * Complete Quick Start workflow with default settings for a grade level
   */
  async completeQuickStartDefault(gradeLevel: 'elementary' | 'middle' | 'high'): Promise<string[]> {
    await this.openQuickStartModal(''); // childId will be auto-detected
    await this.selectGradeLevel(gradeLevel);
    
    // Get expected subjects before submitting
    const expectedSubjects = this.getExpectedSubjectsForGrade(gradeLevel);
    
    await this.submitQuickStart();
    return expectedSubjects;
  }

  /**
   * Complete Quick Start workflow with custom subjects only
   */
  async completeQuickStartCustomOnly(customSubjects: string[]): Promise<void> {
    await this.openQuickStartModal('');
    await this.selectGradeLevel('elementary'); // Need to select any grade level first
    await this.deselectAllSubjects(); // Remove all recommended subjects
    await this.addCustomSubjects(customSubjects);
    await this.submitQuickStart();
  }
}

/**
 * Child management helpers for subject tests
 */
export class ChildHelper {
  constructor(
    private page: Page,
    private modalHelper: ModalHelper
  ) {}

  /**
   * Create a child with specified details
   */
  async createChild(name: string, age: string, independenceLevel: string = '2'): Promise<void> {
    await this.page.goto('/children');
    
    const addChildButton = this.page.locator('button:has-text("Add Child")');
    await addChildButton.click();

    await this.modalHelper.waitForModal('child-form-modal');
    await this.modalHelper.fillModalField('child-form-modal', 'name', name);
    await this.page.selectOption('#child-form-modal select[name="age"]', age);
    await this.page.selectOption('#child-form-modal select[name="independence_level"]', independenceLevel);
    await this.modalHelper.submitModalForm('child-form-modal');
  }

  /**
   * Create a child suitable for Quick Start testing
   */
  async createQuickStartTestChild(baseName: string, age: number): Promise<string> {
    const childName = `${baseName} ${Date.now()}`;
    await this.createChild(childName, age.toString());
    return childName;
  }
}