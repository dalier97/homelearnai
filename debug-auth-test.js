// Simple test to debug authentication flow

const { test, expect } = require('@playwright/test');

test('debug registration and dashboard access', async ({ page }) => {
  // Step 1: Try registration
  console.log('1. Going to register page...');
  await page.goto('http://localhost:8000/register');
  await expect(page.locator('h2')).toContainText('Create your account');
  
  // Step 2: Fill registration form
  console.log('2. Filling registration form...');
  const testUser = {
    name: 'Debug Test User',
    email: `debug-${Date.now()}@example.com`,
    password: 'debugtest123'
  };
  
  await page.fill('input[name="name"]', testUser.name);
  await page.fill('input[name="email"]', testUser.email);
  await page.fill('input[name="password"]', testUser.password);
  await page.fill('input[name="password_confirmation"]', testUser.password);
  
  console.log('3. Submitting registration...');
  await page.click('button[type="submit"]');
  
  // Wait for redirect/response
  await page.waitForLoadState('networkidle');
  
  console.log('4. Current URL after registration:', page.url());
  console.log('5. Page title:', await page.title());
  
  // Step 3: Check where we landed
  const currentUrl = page.url();
  
  if (currentUrl.includes('/dashboard')) {
    console.log('SUCCESS: Redirected to dashboard');
    const h1Text = await page.locator('h1').textContent();
    console.log('Dashboard H1 text:', h1Text);
  } else if (currentUrl.includes('/login')) {
    console.log('EXPECTED: Redirected to login (email confirmation required)');
    
    // Try manual login
    console.log('6. Trying to login...');
    await page.fill('input[name="email"]', testUser.email);
    await page.fill('input[name="password"]', testUser.password);
    await page.click('button[type="submit"]');
    
    await page.waitForLoadState('networkidle');
    console.log('7. URL after login:', page.url());
    
    if (page.url().includes('/dashboard')) {
      console.log('SUCCESS: Manual login worked');
      const h1Text = await page.locator('h1').textContent();
      console.log('Dashboard H1 text:', h1Text);
    } else {
      console.log('FAILED: Login did not work');
      const errors = await page.locator('.error, .text-red-500').allTextContents();
      console.log('Error messages:', errors);
    }
  } else if (currentUrl.includes('/register')) {
    console.log('STAYED: On register page');
    const errors = await page.locator('.error, .text-red-500').allTextContents();
    console.log('Error messages:', errors);
  } else {
    console.log('UNEXPECTED: Went to', currentUrl);
  }
  
  // Step 4: Try accessing children page
  console.log('8. Trying to access /children...');
  await page.goto('http://localhost:8000/children');
  await page.waitForLoadState('networkidle');
  
  console.log('9. Children page URL:', page.url());
  
  if (page.url().includes('/children')) {
    console.log('SUCCESS: Access to children page granted');
    const pageContent = await page.locator('body').textContent();
    console.log('Children page contains "Add Child"?', pageContent.includes('Add Child'));
  } else {
    console.log('BLOCKED: Redirected from children page');
  }
});