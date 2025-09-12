import { test, expect, Page } from '@playwright/test';

test.describe('Flashcard Performance & UI Polish', () => {
  let page: Page;

  test.beforeEach(async ({ browser }) => {
    page = await browser.newPage();
    
    // Navigate to login page and authenticate
    await page.goto('/login');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    
    // Wait for dashboard to load
    await page.waitForURL('/dashboard');
  });

  test('loading skeleton appears while flashcards are loading', async () => {
    // Navigate to a unit with many flashcards
    await page.goto('/units/1');
    
    // Check for loading skeleton
    const skeleton = page.locator('[data-testid="flashcard-loading-skeleton"]');
    await expect(skeleton).toBeVisible();
    
    // Wait for actual content to load
    await page.waitForSelector('.flashcard-item', { timeout: 10000 });
    
    // Skeleton should disappear
    await expect(skeleton).not.toBeVisible();
  });

  test('search interface shows loading indicators', async () => {
    await page.goto('/units/1');
    
    // Wait for page to load
    await page.waitForSelector('#flashcard-search');
    
    // Start typing in search
    const searchInput = page.locator('#flashcard-search');
    await searchInput.fill('mathematics');
    
    // Check for search loading indicator
    const loadingIndicator = page.locator('#search-loading');
    await expect(loadingIndicator).toBeVisible();
    
    // Wait for results to load
    await page.waitForSelector('#search-results .flashcard-item', { timeout: 10000 });
    
    // Loading indicator should disappear
    await expect(loadingIndicator).not.toBeVisible();
  });

  test('search suggestions appear and work correctly', async () => {
    await page.goto('/units/1');
    
    const searchInput = page.locator('#flashcard-search');
    await searchInput.fill('mat');
    
    // Wait for suggestions to appear
    const suggestions = page.locator('#search-suggestions');
    await expect(suggestions).toBeVisible({ timeout: 3000 });
    
    // Click on a suggestion
    const firstSuggestion = page.locator('.suggestion-item').first();
    await expect(firstSuggestion).toBeVisible();
    await firstSuggestion.click();
    
    // Search input should be filled
    await expect(searchInput).toHaveValue(/^mat/);
  });

  test('advanced search panel toggles correctly', async () => {
    await page.goto('/units/1');
    
    // Click advanced search toggle
    const toggleButton = page.locator('#toggle-advanced-search');
    await toggleButton.click();
    
    // Advanced panel should appear
    const advancedPanel = page.locator('#advanced-search-panel');
    await expect(advancedPanel).toBeVisible();
    
    // Toggle again to hide
    await toggleButton.click();
    await expect(advancedPanel).not.toBeVisible();
  });

  test('filter pills work correctly', async () => {
    await page.goto('/units/1');
    
    // Click on a filter pill
    const basicCardsPill = page.locator('[data-filter="card_type"][data-value="basic"]');
    await basicCardsPill.click();
    
    // Pill should become active
    await expect(basicCardsPill).toHaveClass(/active/);
    
    // Results should update
    await page.waitForSelector('#search-results', { timeout: 3000 });
    
    // Click again to deactivate
    await basicCardsPill.click();
    await expect(basicCardsPill).not.toHaveClass(/active/);
  });

  test('import modal shows progress indicators', async () => {
    await page.goto('/units/1');
    
    // Open import modal
    await page.click('button[hx-get*="import"]');
    
    // Wait for modal to appear
    await page.waitForSelector('#flashcard-modal');
    
    // Upload a file (simulate)
    const fileInput = page.locator('input[type="file"]');
    await fileInput.setInputFiles([{
      name: 'test.csv',
      mimeType: 'text/csv',
      buffer: Buffer.from('question,answer\nTest Question,Test Answer')
    }]);
    
    // Check for progress indication
    const progressBar = page.locator('.progress-bar');
    await expect(progressBar).toBeVisible({ timeout: 10000 });
  });

  test('export modal shows loading states', async () => {
    await page.goto('/units/1');
    
    // Open export modal
    await page.click('button[hx-get*="export"]');
    
    // Wait for modal
    await page.waitForSelector('#flashcard-modal');
    
    // Select export format and submit
    await page.selectOption('select[name="export_format"]', 'csv');
    await page.click('button[type="submit"]');
    
    // Check for loading spinner
    const spinner = page.locator('.loading-spinner');
    await expect(spinner).toBeVisible({ timeout: 3000 });
  });

  test('error messages are user-friendly', async () => {
    await page.goto('/units/999'); // Non-existent unit
    
    // Should show friendly error message
    const errorMessage = page.locator('.error-message');
    await expect(errorMessage).toBeVisible();
    await expect(errorMessage).toContainText(/not found|access denied/i);
    
    // Should have recovery actions
    const retryButton = page.locator('button:has-text("Try Again")');
    await expect(retryButton).toBeVisible();
  });

  test('performance metrics are displayed in debug mode', async () => {
    // Enable debug mode (this would be set in environment)
    await page.goto('/units/1?debug=true');
    
    // Look for performance information
    const performanceInfo = page.locator('[data-testid="performance-metrics"]');
    
    // Should show timing information
    await expect(performanceInfo).toContainText(/ms|seconds/i);
  });

  test('bulk operations show progress', async () => {
    await page.goto('/units/1');
    
    // Select multiple flashcards
    await page.click('.flashcard-checkbox'); // First checkbox
    await page.click('.flashcard-checkbox >> nth=1'); // Second checkbox
    
    // Trigger bulk operation
    await page.click('button:has-text("Bulk Actions")');
    await page.click('button:has-text("Export Selected")');
    
    // Should show progress
    const progressIndicator = page.locator('.bulk-progress');
    await expect(progressIndicator).toBeVisible({ timeout: 3000 });
  });

  test('responsive design works on mobile viewport', async () => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto('/units/1');
    
    // Search interface should be mobile-friendly
    const searchInput = page.locator('#flashcard-search');
    await expect(searchInput).toBeVisible();
    
    // Advanced filters should be collapsed on mobile
    const advancedPanel = page.locator('#advanced-search-panel');
    await expect(advancedPanel).toHaveClass(/hidden/);
    
    // Cards should stack vertically
    const flashcardList = page.locator('.flashcard-list');
    await expect(flashcardList).toHaveCSS('display', /flex|block/);
  });

  test('keyboard navigation works correctly', async () => {
    await page.goto('/units/1');
    
    // Focus search input
    await page.focus('#flashcard-search');
    
    // Type to trigger suggestions
    await page.keyboard.type('test');
    
    // Use arrow keys to navigate suggestions
    await page.keyboard.press('ArrowDown');
    
    // Press Enter to select
    await page.keyboard.press('Enter');
    
    // Search should execute
    await page.waitForSelector('#search-results', { timeout: 3000 });
  });

  test('cache warming indicator works', async () => {
    await page.goto('/units/1');
    
    // Trigger cache warming (if available)
    const cacheButton = page.locator('button:has-text("Warm Cache")');
    
    if (await cacheButton.isVisible()) {
      await cacheButton.click();
      
      // Should show warming indicator
      const warmingIndicator = page.locator('.cache-warming');
      await expect(warmingIndicator).toBeVisible({ timeout: 1000 });
      
      // Should complete
      await expect(warmingIndicator).not.toBeVisible({ timeout: 10000 });
    }
  });

  test('search statistics are displayed', async () => {
    await page.goto('/units/1');
    
    // Perform a search
    await page.fill('#flashcard-search', 'mathematics');
    await page.waitForSelector('#search-results');
    
    // Check for search statistics
    const statsContainer = page.locator('#search-stats');
    await expect(statsContainer).toBeVisible();
    
    // Should show result count
    const resultCount = page.locator('#results-count');
    await expect(resultCount).toContainText(/\d+ results?/);
    
    // Should show search time
    const searchTime = page.locator('#search-time');
    await expect(searchTime).toContainText(/\d+ms/);
  });

  test('error recovery suggestions work', async () => {
    // Trigger an error (disconnect network or similar)
    await page.route('**/api/units/*/flashcards', route => {
      route.fulfill({ status: 500, body: 'Server Error' });
    });
    
    await page.goto('/units/1');
    
    // Should show error with recovery options
    const errorContainer = page.locator('.error-container');
    await expect(errorContainer).toBeVisible();
    
    const retryButton = page.locator('button:has-text("Try Again")');
    await expect(retryButton).toBeVisible();
    
    const backButton = page.locator('button:has-text("Go Back")');
    await expect(backButton).toBeVisible();
    
    const supportButton = page.locator('button:has-text("Contact Support")');
    await expect(supportButton).toBeVisible();
  });

  test('loading states transition smoothly', async () => {
    await page.goto('/units/1');
    
    // Trigger a search that will show loading state
    await page.fill('#flashcard-search', 'test');
    
    // Check that loading appears quickly
    const loadingStart = Date.now();
    await expect(page.locator('#search-loading')).toBeVisible({ timeout: 500 });
    const loadingAppearTime = Date.now() - loadingStart;
    
    expect(loadingAppearTime).toBeLessThan(500); // Should appear within 500ms
    
    // Wait for results
    await page.waitForSelector('#search-results .flashcard-item');
    
    // Loading should disappear
    await expect(page.locator('#search-loading')).not.toBeVisible();
  });
});