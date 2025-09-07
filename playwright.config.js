import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: false, // Disable full parallelization to prevent race conditions
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 1,
  workers: 1, // Force single worker to prevent server conflicts
  reporter: 'html',
  timeout: 60000, // Global test timeout: 60 seconds (for complex kids mode tests)
  expect: {
    timeout: 15000, // Assertion timeout: 15 seconds
  },
  
  use: {
    baseURL: 'http://127.0.0.1:18001',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    actionTimeout: 10000, // Action timeout: 10 seconds (optimized)
    navigationTimeout: 15000, // Navigation timeout: 15 seconds (optimized)
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },
    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },
  ],

  // Server management handled by run-e2e-tests.sh script
  // This ensures proper environment isolation
  // Disable built-in server since it interferes with testing environment
  webServer: undefined,
});