import { Page } from '@playwright/test';
import { ModalHelper } from './modal-helpers';
import { TestSetupHelper } from './test-setup-helpers';

/**
 * Global test configuration and setup utilities
 * Provides consistent test isolation across all test files
 */

export async function setupTestIsolation(page: Page) {
  const testSetupHelper = new TestSetupHelper(page);
  const modalHelper = new ModalHelper(page);
  
  return {
    beforeTest: async () => {
      await testSetupHelper.isolateTest();
    },
    afterTest: async () => {
      await modalHelper.resetTestState();
    }
  };
}

/**
 * Enhanced beforeEach hook that ensures test isolation
 */
export async function enhancedBeforeEach(page: Page, testSetup?: () => Promise<void>) {
  const { beforeTest } = await setupTestIsolation(page);
  await beforeTest();
  
  if (testSetup) {
    await testSetup();
  }
}

/**
 * Enhanced afterEach hook that ensures cleanup
 */
export async function enhancedAfterEach(page: Page, testCleanup?: () => Promise<void>) {
  const { afterTest } = await setupTestIsolation(page);
  
  if (testCleanup) {
    await testCleanup();
  }
  
  await afterTest();
}