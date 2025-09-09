import { test, expect } from '@playwright/test';

test.describe('Navigation Menu', () => {
  let testUser: { email: string; password: string };

  test.beforeEach(async ({ page }) => {
    // Create unique test user
    testUser = {
      email: `nav-test-${Date.now()}@example.com`,
      password: 'testpass123'
    };

    // Register user
    await page.goto('/register');
    await page.fill('input[name="name"]', 'Nav Test User');
    await page.fill('input[name="email"]', testUser.email);
    await page.fill('input[name="password"]', testUser.password);
    await page.fill('input[name="password_confirmation"]', testUser.password);
    await page.click('button[type="submit"]');
    
    // Wait for registration to complete
    await page.waitForLoadState('networkidle');
    
    // If redirected to onboarding, complete it
    if (page.url().includes('/onboarding')) {
      // Step 1: Click Next button to proceed to children form
      await page.click('[data-testid="next-button"]');
      await page.waitForTimeout(1000);
      
      // Fill child form and wait for validation
      await page.fill('input[name="children[0][name]"]', 'Test Child');
      await page.selectOption('select[name="children[0][age]"]', '10');
      await page.selectOption('select[name="children[0][independence_level]"]', '1');
      
      // Wait for Next button to be enabled (Alpine.js reactivity)
      await page.waitForFunction(() => {
        const nextBtn = document.querySelector('[data-testid="next-button"]');
        return nextBtn && !nextBtn.disabled;
      }, { timeout: 5000 });
      
      await page.click('[data-testid="next-button"]');
      await page.waitForTimeout(1000);
      
      // Skip subjects step (Step 3) - use Next button to proceed to review
      await page.click('[data-testid="next-button"]');
      await page.waitForTimeout(1000);
      
      // Complete onboarding (Step 4 - Review)
      if (await page.locator('[data-testid="complete-onboarding-button"]').isVisible()) {
        await page.click('[data-testid="complete-onboarding-button"]');
        await page.waitForLoadState('networkidle');
      }
    }
  });

  test('should display all navigation menu items for logged-in parent', async ({ page }) => {
    // Navigate to dashboard to ensure we're on an authenticated page
    await page.goto('/dashboard/parent');
    await page.waitForLoadState('networkidle');
    
    // Check that navigation is visible
    const nav = page.locator('nav').first();
    await expect(nav).toBeVisible({ timeout: 10000 });
    
    // Verify all main navigation links are present
    const navLinks = [
      { text: 'Dashboard', href: '/dashboard/parent' },
      { text: 'Children', href: '/children' },
      { text: 'Subjects', href: '/subjects' },
      { text: 'Planning', href: '/planning' },
      { text: 'Reviews', href: '/reviews' },
      { text: 'Calendar', href: '/calendar' }
    ];
    
    for (const link of navLinks) {
      const linkElement = nav.locator(`a:has-text("${link.text}")`);
      await expect(linkElement).toBeVisible({ timeout: 5000 });
      
      // Verify href attribute
      const href = await linkElement.getAttribute('href');
      expect(href).toContain(link.href);
    }
    
    // Check for Child Views dropdown if children exist
    const childViewsButton = page.locator('button:has-text("Child Views")');
    if (await childViewsButton.count() > 0) {
      await expect(childViewsButton).toBeVisible();
      
      // Click to open dropdown
      await childViewsButton.click();
      
      // Check for child links in dropdown
      const childLinks = page.locator('a:has-text("Today")');
      if (await childLinks.count() > 0) {
        await expect(childLinks.first()).toBeVisible();
      }
    }
    
    // Verify user dropdown is visible
    const userDropdown = page.locator('button').filter({ hasText: testUser.email });
    await expect(userDropdown).toBeVisible({ timeout: 5000 });
  });

  test('should navigate to each menu item successfully', async ({ page }) => {
    await page.goto('/dashboard/parent');
    await page.waitForLoadState('networkidle');
    
    const nav = page.locator('nav').first();
    
    // Test navigation to each page
    const pages = [
      { link: 'Children', expectedUrl: '/children', expectedHeading: /Children|My Children/ },
      { link: 'Subjects', expectedUrl: '/subjects', expectedHeading: /Subjects|Curriculum/ },
      { link: 'Planning', expectedUrl: '/planning', expectedHeading: /Planning|Schedule/ },
      { link: 'Reviews', expectedUrl: '/reviews', expectedHeading: /Review System|Reviews/ },
      { link: 'Calendar', expectedUrl: '/calendar', expectedHeading: /Calendar|Schedule/ },
      { link: 'Dashboard', expectedUrl: '/dashboard', expectedHeading: /Dashboard|Parent Dashboard/ }
    ];
    
    for (const pageInfo of pages) {
      // Click navigation link
      await nav.locator(`a:has-text("${pageInfo.link}")`).click();
      await page.waitForLoadState('networkidle');
      
      // Verify URL
      expect(page.url()).toContain(pageInfo.expectedUrl);
      
      // Verify page loaded with expected content - look for specific heading text anywhere on page
      await expect(page.locator('h1, h2').filter({ hasText: pageInfo.expectedHeading })).toBeVisible({ timeout: 10000 });
      
      // Verify navigation menu is still visible
      await expect(nav).toBeVisible();
    }
  });

  test('should hide navigation in kids mode', async ({ page }) => {
    await page.goto('/dashboard/parent');
    await page.waitForLoadState('networkidle');
    
    // First verify navigation is visible
    const nav = page.locator('nav').first();
    await expect(nav).toBeVisible();
    
    // Enter kids mode if button is available
    const kidsModeBtns = page.locator('button:has-text("Enter Kids Mode")');
    if (await kidsModeBtns.count() > 0) {
      await kidsModeBtns.first().click();
      await page.waitForLoadState('networkidle');
      
      // Navigation should show limited items in kids mode
      const parentNavItems = ['Dashboard', 'Children', 'Subjects', 'Planning'];
      for (const item of parentNavItems) {
        const navItem = nav.locator(`a:has-text("${item}")`);
        // These should not be visible in kids mode (or have limited visibility)
        const isVisible = await navItem.isVisible().catch(() => false);
        if (isVisible) {
          // If visible, it should be the kids mode version
          expect(page.url()).toContain('child');
        }
      }
    }
  });

  test('should show active state for current page', async ({ page }) => {
    await page.goto('/dashboard/parent');
    await page.waitForLoadState('networkidle');
    
    const nav = page.locator('nav').first();
    
    // Dashboard should be active
    const dashboardLink = nav.locator('a:has-text("Dashboard")');
    await expect(dashboardLink).toHaveClass(/text-blue-600|bg-blue-50/);
    
    // Navigate to Children
    await nav.locator('a:has-text("Children")').click();
    await page.waitForLoadState('networkidle');
    
    // Re-find the navigation links after navigation (DOM may have updated)
    const navAfterNavigation = page.locator('nav').first();
    const childrenLink = navAfterNavigation.locator('a:has-text("Children")');
    const dashboardLinkAfter = navAfterNavigation.locator('a:has-text("Dashboard")');
    
    // Children should now be active
    await expect(childrenLink).toHaveClass(/text-blue-600.*bg-blue-50|bg-blue-50.*text-blue-600/);
    
    // Dashboard should not be active anymore (should have default gray classes)
    await expect(dashboardLinkAfter).toHaveClass(/text-gray-700/);
    await expect(dashboardLinkAfter).not.toHaveClass(/text-blue-600.*bg-blue-50|bg-blue-50.*text-blue-600/);
  });

  test('should display user dropdown with logout option', async ({ page }) => {
    await page.goto('/dashboard/parent');
    await page.waitForLoadState('networkidle');
    
    // Find and click user dropdown - it contains the user's email
    const userDropdown = page.locator('button').filter({ hasText: testUser.email });
    await expect(userDropdown).toBeVisible();
    await userDropdown.click();
    
    // Check dropdown menu items
    await expect(page.locator('text=Signed in as')).toBeVisible();
    
    // Find logout button - check for the translated text or button type
    const logoutBtn = page.locator('button[type="submit"]').filter({ hasText: /logout|log out|sign out/i });
    await expect(logoutBtn).toBeVisible();
    
    // Click logout
    await logoutBtn.click();
    await page.waitForLoadState('networkidle');
    
    // Should be redirected to login or home
    expect(page.url()).toMatch(/\/(login|$)/);
    
    // Navigation should not be visible after logout or should not have authenticated items
    const childrenLink = page.locator('a:has-text("Children")');
    const childrenVisible = await childrenLink.isVisible().catch(() => false);
    
    // After logout, children link should not be visible
    if (childrenVisible) {
      throw new Error('Children navigation link should not be visible after logout');
    }
  });

  test('should work on mobile viewport', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    
    await page.goto('/dashboard/parent');
    await page.waitForLoadState('networkidle');
    
    // On mobile, desktop navigation should be hidden (md:flex class)
    const desktopNav = page.locator('#desktop-nav');
    await expect(desktopNav).not.toBeVisible();
    
    // Look for mobile navigation elements - either in a mobile menu or directly visible
    // First, check if there's a mobile hamburger button
    const hamburgerButton = page.locator('button.md\\:hidden').filter({
      has: page.locator('svg')
    }).first();
    
    let foundMobileNav = false;
    
    // Try to click hamburger button if it exists
    if (await hamburgerButton.count() > 0 && await hamburgerButton.isVisible().catch(() => false)) {
      await hamburgerButton.click();
      await page.waitForTimeout(500);
      foundMobileNav = true;
    }
    
    // Check if mobile navigation is now visible
    const mobileNavLinks = page.locator('div.md\\:hidden a:has-text("Dashboard"), div.md\\:hidden a:has-text("Children")');
    const hasVisibleLinks = await mobileNavLinks.first().isVisible().catch(() => false);
    
    if (hasVisibleLinks) {
      // Verify main links are accessible in mobile menu
      await expect(page.locator('div.md\\:hidden a:has-text("Dashboard")')).toBeVisible();
      await expect(page.locator('div.md\\:hidden a:has-text("Children")')).toBeVisible();
      
      // Test navigation functionality - click a link
      await page.locator('div.md\\:hidden a:has-text("Children")').click();
      await page.waitForLoadState('networkidle');
      
      // Verify navigation worked
      expect(page.url()).toContain('/children');
    } else {
      // If no mobile-specific navigation, just verify that some navigation exists and works
      const anyDashboardLink = page.locator('a:has-text("Dashboard")').first();
      const anyChildrenLink = page.locator('a:has-text("Children")').first();
      
      if (await anyDashboardLink.isVisible() && await anyChildrenLink.isVisible()) {
        await anyChildrenLink.click();
        await page.waitForLoadState('networkidle');
        expect(page.url()).toContain('/children');
      } else {
        throw new Error('No navigation links found on mobile viewport');
      }
    }
  });
});