import { Page, expect } from '@playwright/test';

/**
 * Modal interaction helpers for E2E tests
 * Provides reliable modal handling with proper waiting strategies
 */

export class ModalHelper {
  constructor(private page: Page) {}

  /**
   * Wait for a modal to appear and be ready for interaction
   */
  async waitForModal(testId: string, timeout: number = 10000) {
    const modal = this.page.getByTestId(testId);
    
    // Wait for modal to be visible
    await modal.waitFor({ state: 'visible', timeout });
    
    // Wait for modal content to be ready
    const modalContent = modal.getByTestId('modal-content');
    await modalContent.waitFor({ state: 'visible', timeout: 5000 });
    
    // Small delay to ensure all animations and transitions complete
    await this.page.waitForTimeout(300);
    
    return modal;
  }

  /**
   * Close a modal by clicking the background overlay
   */
  async closeModalByOverlay(testId: string, timeout: number = 5000) {
    const modal = this.page.getByTestId(testId);
    
    // Click on the modal background (not the content)
    await modal.click({ position: { x: 10, y: 10 }, timeout });
    
    // Wait for modal to disappear
    await modal.waitFor({ state: 'hidden', timeout });
  }

  /**
   * Close a modal by clicking the X button
   */
  async closeModalByButton(testId: string, timeout: number = 5000) {
    const modal = this.page.getByTestId(testId);
    const closeButton = modal.locator('button[type="button"]').first();
    
    await closeButton.click({ timeout });
    
    // Wait for modal to be removed from DOM (not just hidden)
    await modal.waitFor({ state: 'detached', timeout });
  }

  /**
   * Fill a form field within a modal with proper waiting
   */
  async fillModalField(testId: string, fieldName: string, value: string, timeout: number = 5000) {
    const modal = this.page.getByTestId(testId);
    const field = modal.locator(`input[name="${fieldName}"], select[name="${fieldName}"], textarea[name="${fieldName}"]`);
    
    // Wait for field to be ready
    await field.waitFor({ state: 'visible', timeout });
    await field.waitFor({ state: 'attached' });
    
    // Clear and fill
    await field.clear();
    await field.fill(value);
    
    // Verify the value was set
    if (value) {
      await expect(field).toHaveValue(value);
    }
  }

  /**
   * Submit a modal form and wait for completion (HTMX compatible)
   */
  async submitModalForm(testId: string, timeout: number = 15000) {
    const modal = this.page.getByTestId(testId);
    const submitButton = modal.locator('button[type="submit"]');
    
    // Wait for submit button to be ready
    await submitButton.waitFor({ state: 'visible', timeout: 5000 });
    
    // Submit the form
    await submitButton.click({ timeout: 5000 });
    
    // Wait for HTMX request to complete
    await this.waitForHtmxCompletion();
    
    // Give a moment for the modal removal JavaScript to execute
    await this.page.waitForTimeout(1000);
    
    // Check if modal still exists and force close if needed
    const modalExists = await modal.count() > 0;
    if (modalExists) {
      const isVisible = await modal.isVisible().catch(() => false);
      if (isVisible) {
        console.log(`Modal ${testId} still visible after form submission, force closing...`);
        await modal.evaluate(el => el.remove());
      }
    }
    
    // Wait for modal to be fully removed from DOM
    await modal.waitFor({ state: 'detached', timeout });
  }

  /**
   * Wait for HTMX requests to complete
   */
  async waitForHtmxCompletion(timeout: number = 10000) {
    // Wait for any ongoing HTMX requests to finish
    await this.page.waitForFunction(
      () => {
        // @ts-ignore - htmx is a global variable
        return !window.htmx || !document.querySelector('.htmx-request');
      },
      {},
      { timeout }
    );
    
    // Additional small wait to ensure DOM changes are complete
    await this.page.waitForTimeout(500);
  }

  /**
   * Force close any open modals by removing them from DOM
   */
  async forceCloseModals() {
    await this.page.evaluate(() => {
      // Remove all modals with common modal classes
      const modals = document.querySelectorAll('.fixed.inset-0, [data-testid$="-modal"]');
      modals.forEach(modal => modal.remove());
    });
    
    // Small delay to ensure DOM cleanup
    await this.page.waitForTimeout(200);
  }

  /**
   * Wait for any existing modal to close before proceeding
   */
  async waitForNoModals(timeout: number = 5000) {
    // Wait for HTMX requests to complete first
    await this.waitForHtmxCompletion();
    
    // Common modal test IDs to check
    const modalTestIds = [
      'subject-modal',
      'subject-edit-modal',
      'unit-create-modal', 
      'unit-edit-modal',
      'topic-create-modal',
      'topic-edit-modal',
      'review-session-modal',
      'add-slot-modal',
      'scheduling-modal'
    ];

    for (const testId of modalTestIds) {
      const modal = this.page.getByTestId(testId);
      const exists = await modal.count() > 0;
      
      if (exists) {
        const isVisible = await modal.isVisible({ timeout: 100 }).catch(() => false);
        
        if (isVisible) {
          // Try to close it first, then wait for it to be removed
          try {
            await this.closeModalByButton(testId, 2000);
          } catch {
            // If button close fails, force close
            await modal.evaluate(el => el.remove());
          }
          
          // Ensure it's completely removed from DOM
          await modal.waitFor({ state: 'detached', timeout });
        }
      }
    }
  }
}

/**
 * Element interaction helpers
 */
export class ElementHelper {
  constructor(private page: Page) {}

  /**
   * Wait for an element to be ready for interaction
   */
  async waitForInteraction(selector: string, timeout: number = 10000) {
    const element = this.page.locator(selector);
    
    // Wait for element to exist, be visible, and be enabled
    await element.waitFor({ state: 'visible', timeout });
    await element.waitFor({ state: 'attached' });
    
    // Ensure element is not disabled
    await expect(element).not.toBeDisabled({ timeout: 5000 });
    
    return element;
  }

  /**
   * Safe click with retries and proper waiting
   */
  async safeClick(selector: string, timeout: number = 10000, useFirst: boolean = true) {
    let locator = this.page.locator(selector);
    
    // Handle multiple elements by using first() if needed
    if (useFirst) {
      const count = await locator.count();
      if (count > 1) {
        console.log(`Found ${count} elements matching '${selector}', using first()`);
        locator = locator.first();
      }
    }
    
    // Wait for element to be ready for interaction
    await locator.waitFor({ state: 'visible', timeout });
    await locator.waitFor({ state: 'attached' });
    
    // Ensure element is not disabled
    await expect(locator).not.toBeDisabled({ timeout: 5000 });
    
    // Scroll element into view if needed
    await locator.scrollIntoViewIfNeeded();
    
    // Wait a moment for any animations
    await this.page.waitForTimeout(200);
    
    // Click with retry logic
    let attempts = 0;
    const maxAttempts = 3;
    
    while (attempts < maxAttempts) {
      try {
        await locator.click({ timeout: 5000 });
        return; // Success
      } catch (error) {
        attempts++;
        if (attempts >= maxAttempts) throw error;
        
        // Wait before retry
        await this.page.waitForTimeout(500);
      }
    }
  }

  /**
   * Safe fill with verification
   */
  async safeFill(selector: string, value: string, timeout: number = 10000) {
    const element = await this.waitForInteraction(selector, timeout);
    
    // Clear and fill
    await element.clear();
    await element.fill(value);
    
    // Verify the value was set correctly
    if (value) {
      await expect(element).toHaveValue(value);
    }
  }

  /**
   * Wait for page to be ready for interaction
   */
  async waitForPageReady() {
    // Wait for network to be idle
    await this.page.waitForLoadState('networkidle');
    
    // Wait for DOM to be stable
    await this.page.waitForLoadState('domcontentloaded');
    
    // Small delay for any client-side rendering
    await this.page.waitForTimeout(500);
  }
}