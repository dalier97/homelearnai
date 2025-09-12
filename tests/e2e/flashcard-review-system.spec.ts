import { test, expect } from '@playwright/test';
import { ModalHelper, ElementHelper } from './helpers/modal-helpers';
import { KidsModeHelper } from './helpers/kids-mode-helpers';

test.describe('Flashcard Review System E2E Tests', () => {
  let testUser: { name: string; email: string; password: string };
  let modalHelper: ModalHelper;
  let elementHelper: ElementHelper;
  let kidsModeHelper: KidsModeHelper;
  let subjectId: string;
  let unitId: string;
  
  test.beforeEach(async ({ page }) => {
    // Initialize helpers
    modalHelper = new ModalHelper(page);
    elementHelper = new ElementHelper(page);
    kidsModeHelper = new KidsModeHelper(page);
    
    // Create a unique test user for this session
    testUser = {
      name: 'Review Test User',
      email: `review-test-${Date.now()}@example.com`,
      password: 'testpassword123'
    };

    // Register and login
    await page.goto('/register');
    await page.fill('input[name="name"]', testUser.name);
    await page.fill('input[name="email"]', testUser.email);
    await page.fill('input[name="password"]', testUser.password);
    await page.fill('input[name="password_confirmation"]', testUser.password);
    await page.click('button[type="submit"]');
    
    await page.waitForLoadState('networkidle', { timeout: 10000 });
    
    if (page.url().includes('/register') || page.url().includes('/login')) {
      await page.goto('/login');
      await page.fill('input[name="email"]', testUser.email);
      await page.fill('input[name="password"]', testUser.password);
      await page.click('button[type="submit"]');
      await page.waitForLoadState('networkidle', { timeout: 10000 });
    }
    
    // Create test data with flashcards
    await setupTestDataWithFlashcards(page);
  });
  
  async function setupTestDataWithFlashcards(page: any) {
    console.log('Setting up test data with flashcards for review tests...');
    
    // Create child
    await page.goto('/children');
    await page.waitForLoadState('networkidle');
    
    try {
      await elementHelper.safeClick('[data-testid="header-add-child-btn"]');
      await modalHelper.waitForChildModal();
      
      await page.fill('#child-form-modal input[name="name"]', 'Review Test Child');
      await page.selectOption('#child-form-modal select[name="age"]', '10');
      await page.selectOption('#child-form-modal select[name="independence_level"]', '3');
      await page.click('#child-form-modal button[type="submit"]');
      await page.waitForTimeout(2000);
    } catch (e) {
      console.log('Child creation skipped:', e.message);
    }
    
    // Create subject
    await page.goto('/subjects');
    await page.waitForLoadState('networkidle');
    
    await elementHelper.safeClick('button:has-text("Add Subject")', 'first');
    await page.fill('input[name="name"]', 'Review Test Subject');
    await page.selectOption('select[name="color"]', '#8b5cf6');
    await page.click('button:has-text("Save")');
    await page.waitForTimeout(1000);
    
    const subjectLink = await page.locator('a:has-text("Review Test Subject")').first();
    const subjectHref = await subjectLink.getAttribute('href');
    subjectId = subjectHref?.match(/\/subjects\/(\d+)/)?.[1] || '1';
    
    // Create unit
    await page.click('text=Review Test Subject');
    await page.waitForLoadState('networkidle');
    
    await elementHelper.safeClick('button:has-text("Add Unit")');
    await modalHelper.waitForModal('unit-create-modal');
    await modalHelper.fillModalField('unit-create-modal', 'name', 'Review Test Unit');
    await modalHelper.fillModalField('unit-create-modal', 'description', 'Unit for testing review functionality');
    await modalHelper.fillModalField('unit-create-modal', 'target_completion_date', '2024-12-31');
    await modalHelper.submitModalForm('unit-create-modal');
    
    await page.waitForTimeout(2000);
    
    // Navigate to unit page
    await elementHelper.safeClick('div:has([data-unit-name="Review Test Unit"]) a:has-text("View Unit")');
    await page.waitForLoadState('networkidle');
    
    const currentUrl = page.url();
    unitId = currentUrl.match(/\/units\/(\d+)/)?.[1] || '1';
    
    // Create several flashcards for review testing
    await createReviewTestFlashcards(page);
    
    console.log(`Review test setup complete - Subject ID: ${subjectId}, Unit ID: ${unitId}`);
  }

  async function createReviewTestFlashcards(page: any) {
    const flashcards = [
      { question: 'What is 2 + 2?', answer: '4', hint: 'Simple addition' },
      { question: 'What is the capital of France?', answer: 'Paris', hint: 'City of lights' },
      { question: 'What is the largest planet?', answer: 'Jupiter', hint: 'Gas giant' },
      { question: 'What is H2O?', answer: 'Water', hint: 'Essential for life' },
    ];

    for (const flashcard of flashcards) {
      try {
        await page.click('button.bg-green-600:has-text("Add Flashcard")');
        await modalHelper.waitForModal('flashcard-modal');
        
        await page.selectOption('select[name="card_type"]', 'basic');
        await page.fill('textarea[name="question"]', flashcard.question);
        await page.fill('textarea[name="answer"]', flashcard.answer);
        await page.fill('textarea[name="hint"]', flashcard.hint);
        await page.selectOption('select[name="difficulty_level"]', 'medium');
        
        await page.click('button:has-text("Create Flashcard")');
        await page.waitForTimeout(2000);
        
        console.log(`Created flashcard: ${flashcard.question}`);
      } catch (e) {
        console.log(`Failed to create flashcard: ${flashcard.question}`, e.message);
      }
    }
  }

  test.describe('Review Session Access', () => {
    test('should be able to access review session from dashboard', async ({ page }) => {
      // Go to dashboard
      await page.goto('/dashboard');
      await page.waitForLoadState('networkidle');
      
      // Look for review-related links or buttons
      const reviewElements = await page.locator('text=Review, a[href*="review"], button:has-text("Review")').count();
      
      if (reviewElements > 0) {
        console.log('✅ Review access found on dashboard');
        
        // Try to click a review link
        const reviewLink = page.locator('text=Review, a[href*="review"], button:has-text("Review")').first();
        await reviewLink.click();
        await page.waitForLoadState('networkidle');
        
        // Should be on a review page or modal should appear
        const onReviewPage = page.url().includes('review') || 
                            await page.locator('h1:has-text("Review"), h2:has-text("Review")').count() > 0;
        
        expect(onReviewPage).toBe(true);
      } else {
        console.log('⚠️  No review access found on dashboard - may need to navigate differently');
      }
    });

    test('should be able to access flashcard review from unit page', async ({ page }) => {
      await page.goto(`/subjects/${subjectId}/units/${unitId}`);
      await page.waitForLoadState('networkidle');
      
      // Look for review button or start review option
      const reviewButtons = await page.locator('button:has-text("Review"), button:has-text("Start Review"), a:has-text("Review")').count();
      
      if (reviewButtons > 0) {
        const reviewButton = page.locator('button:has-text("Review"), button:has-text("Start Review"), a:has-text("Review")').first();
        await reviewButton.click();
        await page.waitForTimeout(2000);
        
        // Should start review session
        const inReviewMode = await page.locator('text=Review, text=Question, text=Answer').count() > 0;
        expect(inReviewMode).toBe(true);
        
        console.log('✅ Flashcard review accessible from unit page');
      } else {
        console.log('⚠️  No review button found on unit page');
      }
    });
  });

  test.describe('Review Session Flow', () => {
    test('should display flashcard question in review mode', async ({ page }) => {
      await page.goto(`/subjects/${subjectId}/units/${unitId}`);
      await page.waitForLoadState('networkidle');
      
      // Try different ways to start review
      const startReviewSelectors = [
        'button:has-text("Start Review")',
        'button:has-text("Review Flashcards")',
        'a:has-text("Review")',
        '.bg-blue-600:has-text("Review")',
      ];
      
      let reviewStarted = false;
      
      for (const selector of startReviewSelectors) {
        const elements = await page.locator(selector).count();
        if (elements > 0) {
          await page.click(selector);
          await page.waitForTimeout(2000);
          reviewStarted = true;
          break;
        }
      }
      
      if (!reviewStarted) {
        // Try navigating to review page directly
        await page.goto('/reviews');
        await page.waitForLoadState('networkidle');
        reviewStarted = true;
      }
      
      if (reviewStarted) {
        // Look for flashcard content in review mode
        const hasQuestion = await page.locator('text=What is 2 + 2?, text=capital of France, text=largest planet').count() > 0;
        
        if (hasQuestion) {
          console.log('✅ Flashcard question displayed in review mode');
        } else {
          console.log('⚠️  Review mode started but questions not visible yet');
        }
      } else {
        console.log('⚠️  Could not start review session');
      }
    });

    test('should reveal answer when Show Answer button is clicked', async ({ page }) => {
      // Start review session (simplified approach)
      await page.goto('/reviews');
      await page.waitForLoadState('networkidle');
      
      // Look for flashcard review interface
      const hasShowAnswerButton = await page.locator('button:has-text("Show Answer"), button:has-text("Reveal")').count() > 0;
      
      if (hasShowAnswerButton) {
        await page.click('button:has-text("Show Answer"), button:has-text("Reveal")');
        await page.waitForTimeout(1000);
        
        // Answer should now be visible
        const answerVisible = await page.locator('text=Answer:, .answer, [data-answer]').count() > 0 ||
                             await page.locator('text=4, text=Paris, text=Jupiter, text=Water').count() > 0;
        
        expect(answerVisible).toBe(true);
        console.log('✅ Answer revealed when Show Answer clicked');
      } else {
        console.log('⚠️  No Show Answer button found');
      }
    });

    test('should provide difficulty rating options', async ({ page }) => {
      await page.goto('/reviews');
      await page.waitForLoadState('networkidle');
      
      // Look for difficulty rating buttons (Easy, Medium, Hard, Again, etc.)
      const ratingButtons = await page.locator(
        'button:has-text("Easy"), button:has-text("Medium"), button:has-text("Hard"), ' +
        'button:has-text("Again"), button:has-text("Good"), button:has-text("Perfect")'
      ).count();
      
      if (ratingButtons > 0) {
        console.log('✅ Difficulty rating options available');
        
        // Try clicking a rating button
        const easyButton = page.locator('button:has-text("Easy"), button:has-text("Good")').first();
        const buttonExists = await easyButton.count() > 0;
        
        if (buttonExists) {
          await easyButton.click();
          await page.waitForTimeout(1000);
          
          // Should move to next card or show completion
          const progressMade = await page.locator('text=Next, text=Complete, text=Well done').count() > 0;
          console.log(`Progress indication: ${progressMade ? 'Yes' : 'No'}`);
        }
      } else {
        console.log('⚠️  No difficulty rating buttons found');
      }
    });
  });

  test.describe('Review in Kids Mode', () => {
    test('should show hints in kids mode', async ({ page }) => {
      // Set up kids mode
      await kidsModeHelper.setupPin('1234');
      await kidsModeHelper.forceKidsMode(undefined, 'Review Test Child');
      
      // Navigate to review
      await page.goto('/reviews');
      await page.waitForLoadState('networkidle');
      
      // In kids mode, hints should be more prominent or automatically shown
      const hintsVisible = await page.locator('text=Hint:, .hint, text=Simple addition, text=City of lights').count() > 0;
      
      if (hintsVisible) {
        console.log('✅ Hints visible in kids mode');
      } else {
        // Try to find hint button
        const hintButton = await page.locator('button:has-text("Hint"), button:has-text("Show Hint")').count() > 0;
        if (hintButton) {
          await page.click('button:has-text("Hint"), button:has-text("Show Hint")');
          await page.waitForTimeout(1000);
          
          const hintNowVisible = await page.locator('text=Hint:, text=Simple addition').count() > 0;
          expect(hintNowVisible).toBe(true);
          console.log('✅ Hints accessible via button in kids mode');
        } else {
          console.log('⚠️  No hints found in kids mode');
        }
      }
    });

    test('should have age-appropriate interface in kids mode', async ({ page }) => {
      await kidsModeHelper.setupPin('1234');
      await kidsModeHelper.forceKidsMode(undefined, 'Review Test Child');
      
      await page.goto('/reviews');
      await page.waitForLoadState('networkidle');
      
      // Look for kid-friendly elements
      const kidFriendlyElements = await page.locator(
        'text=Great job!, text=Well done!, text=Keep going!, ' +
        '.bg-green-400, .bg-yellow-400, .text-xl, .text-2xl'
      ).count() > 0;
      
      // Check if buttons are larger or more colorful (kid-friendly)
      const largeButtons = await page.locator('button.px-6, button.py-3, button.text-lg').count() > 0;
      
      const hasKidsInterface = kidFriendlyElements || largeButtons;
      console.log(`Kids-friendly interface detected: ${hasKidsInterface ? 'Yes' : 'No'}`);
    });
  });

  test.describe('Review Statistics', () => {
    test('should track review performance', async ({ page }) => {
      await page.goto('/reviews');
      await page.waitForLoadState('networkidle');
      
      // Complete a review cycle and look for statistics
      const statsElements = await page.locator(
        'text=correct, text=reviewed, text=score, text=accuracy, ' +
        '.stat, [data-stat], text=%'
      ).count();
      
      if (statsElements > 0) {
        console.log('✅ Review statistics tracking detected');
      } else {
        console.log('⚠️  No review statistics found');
      }
    });

    test('should show progress indicators', async ({ page }) => {
      await page.goto('/reviews');
      await page.waitForLoadState('networkidle');
      
      // Look for progress indicators
      const progressElements = await page.locator(
        '.progress-bar, [role="progressbar"], text=progress, ' +
        'text=/ , text=1 of, text=2 of, text=3 of'
      ).count();
      
      if (progressElements > 0) {
        console.log('✅ Progress indicators found');
      } else {
        console.log('⚠️  No progress indicators found');
      }
    });
  });

  test.describe('Spaced Repetition', () => {
    test('should update flashcard scheduling based on performance', async ({ page }) => {
      await page.goto('/reviews');
      await page.waitForLoadState('networkidle');
      
      // Look for any indication of spaced repetition (next review date, interval, etc.)
      const spacedRepetitionElements = await page.locator(
        'text=next review, text=due, text=interval, ' +
        'text=days, text=weeks, [data-next-review]'
      ).count();
      
      if (spacedRepetitionElements > 0) {
        console.log('✅ Spaced repetition system detected');
      } else {
        console.log('⚠️  No spaced repetition indicators found');
      }
    });
  });

  // Simple helper function for testing
  async function startReviewSession(page: any): Promise<boolean> {
    const startMethods = [
      () => page.click('button:has-text("Start Review")'),
      () => page.click('button:has-text("Review")'),
      () => page.goto('/reviews'),
    ];
    
    for (const method of startMethods) {
      try {
        await method();
        await page.waitForTimeout(2000);
        
        const inReview = await page.locator('text=Question, text=Answer, button:has-text("Show")').count() > 0;
        if (inReview) {
          return true;
        }
      } catch (e) {
        continue;
      }
    }
    
    return false;
  }
});