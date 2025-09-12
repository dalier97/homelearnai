import { Page, expect } from '@playwright/test';

/**
 * Modal interaction helpers for E2E tests
 * Provides reliable modal handling with proper waiting strategies
 */

export class ModalHelper {
  constructor(private page: Page) {}

  /**
   * Wait for a modal to appear and be ready for interaction
   * Optimized for speed with reduced timeouts and smart waiting strategies
   */
  async waitForModal(testId: string, timeout: number = 6000) {
    // Handle different modal patterns
    if (testId.includes('flashcard')) {
      // Flashcard modals are loaded via HTMX, use optimized strategies
      console.log('Waiting for flashcard modal to load...');
      
      try {
        // Strategy 1: Wait for HTMX to populate the container
        const flashcardModalContainer = this.page.locator('#flashcard-modal');
        await this.page.waitForFunction(
          () => {
            const container = document.querySelector('#flashcard-modal');
            return container && container.innerHTML.trim() !== '';
          },
          { timeout: 4000 }
        );
        console.log('HTMX container populated');
        
        // Strategy 2: Look for the actual modal overlay that gets loaded
        const modalOverlay = this.page.locator('#flashcard-modal-overlay');
        await modalOverlay.waitFor({ state: 'visible', timeout: 4000 });
        console.log('Flashcard modal overlay is now visible');
        
        // Strategy 3: Wait for Alpine.js to show the modal (x-show="open")
        await this.page.waitForFunction(
          () => {
            const overlay = document.querySelector('#flashcard-modal-overlay');
            return overlay && !overlay.style.display.includes('none');
          },
          { timeout: 2000 }
        );
        console.log('Alpine.js modal display activated');
        
        // Strategy 4: Wait for modal content to be ready
        const modalContent = modalOverlay.locator('#flashcard-import-modal-content');
        await modalContent.waitFor({ state: 'visible', timeout: 2000 });
        console.log('Modal content is ready');
        
        await this.page.waitForTimeout(200); // Brief delay for animations
        return modalOverlay;
        
      } catch (error) {
        // Strategy 5: Final fallback - look for any flashcard modal elements
        console.log('Primary strategies failed, trying fallback...');
        
        // Check if we have any flashcard modal elements at all
        const anyFlashcardModal = this.page.locator('#flashcard-modal-overlay, #flashcard-import-modal-content').first();
        const modalCount = await anyFlashcardModal.count();
        
        if (modalCount > 0) {
          const isVisible = await anyFlashcardModal.isVisible().catch(() => false);
          if (isVisible) {
            console.log('Fallback found visible flashcard modal');
            return anyFlashcardModal;
          }
        }
        
        // Get detailed debug info before failing
        const containerContent = await this.page.locator('#flashcard-modal').innerHTML().catch(() => 'ERROR_GETTING_CONTENT');
        const containerExists = await this.page.locator('#flashcard-modal').count();
        const currentUrl = this.page.url();
        
        console.log('=== FLASHCARD MODAL DEBUG ===');
        console.log('Current URL:', currentUrl);
        console.log('Container exists:', containerExists > 0);
        console.log('Container content length:', containerContent.length);
        console.log('Container content sample:', containerContent.slice(0, 500));
        console.log('===========================');
        
        // Also check network failures 
        const networkErrors = await this.page.evaluate(() => {
          // Check for any failed requests in the browser console
          return (window as any).htmxErrors || 'No HTMX errors recorded';
        });
        console.log('Network/HTMX errors:', networkErrors);
        
        throw new Error(`Flashcard modal failed to load after all strategies. Original error: ${error.message}. URL: ${currentUrl}, Container exists: ${containerExists > 0}, Content length: ${containerContent.length}`);
      }
    }
    
    const modal = this.page.getByTestId(testId);
    
    // For HTMX-loaded modals, use optimized waiting (reduced from 1000ms)
    await this.page.waitForTimeout(400);
    
    // Check if modal has data-testid="modal-content" child
    const hasModalContent = await modal.getByTestId('modal-content').count() > 0;
    if (hasModalContent) {
      const modalContent = modal.getByTestId('modal-content');
      await modalContent.waitFor({ state: 'visible', timeout });
    } else {
      // Wait for the modal itself to be visible if no modal-content
      await modal.waitFor({ state: 'visible', timeout });
    }
    
    // Reduced delay to ensure animations complete (was 300ms)
    await this.page.waitForTimeout(150);
    
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
    
    // Check if this is a select element
    const tagName = await field.evaluate(el => el.tagName.toLowerCase());
    
    if (tagName === 'select') {
      // Handle select dropdown - don't clear, just select the option
      await field.selectOption(value);
    } else {
      // Handle input/textarea - clear and fill
      await field.clear();
      await field.fill(value);
      
      // Verify the value was set
      if (value) {
        await expect(field).toHaveValue(value);
      }
    }
  }

  /**
   * Fill multiple form fields in a modal
   */
  async fillModalForm(formData: Record<string, string>, testId?: string, timeout: number = 5000) {
    let modal = this.page;
    
    if (testId) {
      modal = this.page.getByTestId(testId);
    }
    
    for (const [fieldName, value] of Object.entries(formData)) {
      const field = modal.locator(`input[name="${fieldName}"], select[name="${fieldName}"], textarea[name="${fieldName}"]`);
      
      // Wait for field to be ready
      await field.waitFor({ state: 'visible', timeout });
      await field.waitFor({ state: 'attached' });
      
      // Check if this is a select element
      const tagName = await field.evaluate(el => el.tagName.toLowerCase());
      
      if (tagName === 'select') {
        // Handle select dropdown - don't clear, just select the option
        await field.selectOption(value);
      } else {
        // Handle input/textarea - clear and fill
        await field.clear();
        await field.fill(value);
        
        // Verify the value was set
        if (value) {
          await expect(field).toHaveValue(value);
        }
      }
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
    
    // Give more time for the modal removal JavaScript to execute
    await this.page.waitForTimeout(2000);
    
    // More aggressive modal cleanup
    await this.page.evaluate((modalTestId) => {
      // Find and remove the modal
      const modal = document.querySelector(`[data-testid="${modalTestId}"]`);
      if (modal && (modal as HTMLElement).style.display !== 'none') {
        modal.remove();
      }
      
      // Remove any modal backdrops
      const backdrops = document.querySelectorAll('.modal-backdrop, [data-testid="modal-backdrop"], .fixed.inset-0.bg-gray-500.bg-opacity-75');
      backdrops.forEach(backdrop => backdrop.remove());
      
      // Remove any overlays
      const overlays = document.querySelectorAll('[data-testid*="overlay"], .fixed.z-40');
      overlays.forEach(overlay => {
        if (overlay.classList.contains('bg-opacity-75') || overlay.classList.contains('bg-gray-500')) {
          overlay.remove();
        }
      });
    }, testId);
    
    // Short wait after cleanup
    await this.page.waitForTimeout(500);
    
    // Verify modal is gone - but don't fail if it takes time
    try {
      await modal.waitFor({ state: 'detached', timeout: 3000 });
    } catch {
      // Try waiting for hidden state
      try {
        await modal.waitFor({ state: 'hidden', timeout: 2000 });
      } catch {
        console.log(`Warning: Modal ${testId} cleanup timeout, continuing anyway`);
      }
    }
  }

  /**
   * Wait for HTMX requests to complete - optimized for speed
   */
  async waitForHtmxCompletion(timeout: number = 5000) {
    // Wait for any ongoing HTMX requests to finish
    await this.page.waitForFunction(
      () => {
        // @ts-ignore - htmx is a global variable
        return !window.htmx || !document.querySelector('.htmx-request');
      },
      {},
      { timeout }
    );
    
    // Reduced wait time for DOM changes (was 500ms)
    await this.page.waitForTimeout(200);
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
   * Wait for child modal specifically (handles the modal visibility pattern)
   */
  async waitForChildModal(timeout: number = 10000) {
    // Wait for HTMX to load the modal content into the container
    const modalContainer = this.page.locator('#child-form-modal');
    await modalContainer.waitFor({ state: 'attached', timeout });
    
    // Then wait for the Alpine.js modal overlay to be visible
    const alpineModal = this.page.locator('[data-testid="child-modal-overlay"]');
    await alpineModal.waitFor({ state: 'visible', timeout });
    
    // Wait for form fields to be ready inside the Alpine.js modal
    const nameInput = alpineModal.locator('input[name="name"]');
    await nameInput.waitFor({ state: 'visible', timeout: 5000 });
    
    await this.page.waitForTimeout(300);
    return modalContainer;
  }

  /**
   * Wait for any existing modal to close before proceeding
   */
  async waitForNoModals(timeout: number = 10000) {
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
      'scheduling-modal',
      'child-form-modal'
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
    
    // Also check for flashcard modal overlay and container
    const flashcardModal = this.page.locator('#flashcard-modal-overlay');
    const flashcardExists = await flashcardModal.count() > 0;
    if (flashcardExists && await flashcardModal.isVisible({ timeout: 100 }).catch(() => false)) {
      // Try Alpine.js close first
      await flashcardModal.locator('button').first().click().catch(() => {
        // Fallback: click outside to close
        flashcardModal.click({ position: { x: 10, y: 10 } });
      });
      await flashcardModal.waitFor({ state: 'hidden', timeout });
    }
    
    // Clear the flashcard modal container
    const flashcardContainer = this.page.locator('#flashcard-modal');
    const containerExists = await flashcardContainer.count() > 0;
    if (containerExists) {
      await flashcardContainer.evaluate(el => el.innerHTML = '');
    }
  }

  /**
   * Comprehensive test isolation cleanup - resets all modal state and DOM elements
   */
  async resetTestState() {
    console.log('🧹 Starting comprehensive test state reset...');
    
    // Step 1: Force close all modals
    await this.forceCloseModals();
    
    // Step 2: Wait for any pending HTMX requests
    await this.waitForHtmxCompletion();
    
    // Step 3: Reset all modal containers to empty state
    await this.page.evaluate(() => {
      // Reset all HTMX modal containers
      const modalContainers = [
        '#child-form-modal',
        '#subject-form-modal', 
        '#unit-form-modal',
        '#topic-form-modal',
        '#flashcard-modal',
        '#review-modal',
        '#scheduling-modal'
      ];
      
      modalContainers.forEach(selector => {
        const container = document.querySelector(selector);
        if (container) {
          container.innerHTML = '';
          container.className = '';
          // Reset any Alpine.js state
          if (container.__x) {
            container.__x = null;
          }
        }
      });
      
      // Clear any Alpine.js component state that might be lingering
      if (window.Alpine) {
        window.Alpine.clearPendingMutations?.();
      }
      
      // Reset HTMX state
      if (window.htmx) {
        // Clear any pending requests
        window.htmx.trigger(document.body, 'htmx:abort');
        
        // Reset request queue
        if (window.htmx.requestQueue) {
          window.htmx.requestQueue.length = 0;
        }
      }
    });
    
    // Step 4: Clear any toast notifications or alerts
    const notifications = this.page.locator('.toast, .alert, .notification, [role="alert"]');
    const notificationCount = await notifications.count();
    if (notificationCount > 0) {
      await this.page.evaluate(() => {
        const toasts = document.querySelectorAll('.toast, .alert, .notification, [role="alert"]');
        toasts.forEach(toast => toast.remove());
      });
    }
    
    // Step 5: Reset any form states that might persist
    await this.page.evaluate(() => {
      const forms = document.querySelectorAll('form');
      forms.forEach(form => {
        try {
          form.reset();
        } catch (e) {
          // Ignore form reset errors
        }
      });
    });
    
    // Step 6: Small delay to ensure all cleanup completes
    await this.page.waitForTimeout(300);
    
    console.log('✅ Test state reset completed');
  }

  /**
   * Safe click for buttons that might have multiple instances - uses specific testids to avoid strict mode violations
   */
  async safeClickButton(buttonText: string, preferredTestId?: string) {
    // First try to use specific test ID if provided
    if (preferredTestId) {
      const specificButton = this.page.getByTestId(preferredTestId);
      const specificExists = await specificButton.count() > 0;
      if (specificExists && await specificButton.isVisible().catch(() => false)) {
        await specificButton.click();
        return;
      }
    }
    
    // If specific test ID not found, look for buttons with text but be more strategic
    const allButtons = this.page.locator(`button:has-text("${buttonText}")`);
    const buttonCount = await allButtons.count();
    
    if (buttonCount === 0) {
      throw new Error(`No button found with text "${buttonText}"`);
    } else if (buttonCount === 1) {
      // Only one button - safe to click
      await allButtons.first().click();
    } else {
      // Multiple buttons - try to find the most appropriate one
      console.log(`Found ${buttonCount} buttons with text "${buttonText}", selecting strategically...`);
      
      // Strategy: prefer buttons that are not in empty states or headers
      for (let i = 0; i < buttonCount; i++) {
        const button = allButtons.nth(i);
        const isVisible = await button.isVisible().catch(() => false);
        const isEnabled = !await button.isDisabled().catch(() => false);
        
        if (isVisible && isEnabled) {
          // Check if this button has a useful test ID
          const testId = await button.getAttribute('data-testid').catch(() => null);
          
          // Prefer buttons with header or main action test IDs
          if (testId && (testId.includes('header') || testId.includes('main') || !testId.includes('empty-state'))) {
            await button.click();
            return;
          }
        }
      }
      
      // Fallback: click the first visible, enabled button
      const firstButton = allButtons.first();
      if (await firstButton.isVisible().catch(() => false)) {
        await firstButton.click();
      } else {
        throw new Error(`Multiple buttons found with text "${buttonText}" but none are clickable`);
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
   * Wait for page to be ready for interaction - optimized for speed
   */
  async waitForPageReady() {
    // Wait for DOM to be stable
    await this.page.waitForLoadState('domcontentloaded');
    
    // Wait for network to be idle (this has a built-in 500ms idle time by default)
    await this.page.waitForLoadState('networkidle');
    
    // Reduced delay for client-side rendering (was 500ms)
    await this.page.waitForTimeout(200);
  }
}