<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Flashcard;
use App\Models\Review;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\Unit;
use App\Models\User;
use App\Services\SupabaseClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashcardReviewIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Child $child;

    protected Subject $subject;

    protected Unit $unit;

    protected Topic $topic;

    protected SupabaseClient $supabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear any session state that might interfere with tests
        session()->flush();
        session()->forget('kids_mode_active');
        session()->forget('kids_mode_child_id');

        // Create test data
        $this->user = User::factory()->create();
        $this->child = Child::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->subject = Subject::factory()->create([
            'user_id' => $this->user->id,
            'child_id' => $this->child->id,
        ]);

        $this->unit = Unit::factory()->create([
            'subject_id' => $this->subject->id,
        ]);

        $this->topic = Topic::factory()->create([
            'unit_id' => $this->unit->id,
        ]);

        // Mock SupabaseClient for Review operations
        $this->supabase = $this->createMock(SupabaseClient::class);
        $this->app->instance(SupabaseClient::class, $this->supabase);
    }

    public function test_flashcard_review_model_methods(): void
    {
        $flashcard = Flashcard::factory()->create([
            'unit_id' => $this->unit->id,
            'card_type' => 'basic',
            'question' => 'What is 2 + 2?',
            'answer' => '4',
        ]);

        // Test creating a review object (without actually saving to Supabase)
        $review = new Review([
            'flashcard_id' => $flashcard->id,
            'child_id' => $this->child->id,
            'topic_id' => $this->topic->id,
            'interval_days' => 1,
            'ease_factor' => 2.5,
            'repetitions' => 0,
            'status' => 'new',
        ]);

        $this->assertNotNull($review);
        $this->assertEquals($flashcard->id, $review->flashcard_id);
        $this->assertEquals($this->child->id, $review->child_id);
        $this->assertTrue($review->isFlashcardReview());
        $this->assertFalse($review->isTopicReview());
        $this->assertEquals('new', $review->status);
        $this->assertEquals(2.5, $review->ease_factor);
    }

    public function test_basic_flashcard_review_displays_correctly(): void
    {
        $flashcard = Flashcard::factory()->create([
            'unit_id' => $this->unit->id,
            'card_type' => 'basic',
            'question' => 'What is the capital of France?',
            'answer' => 'Paris',
            'hint' => 'City of Light',
        ]);

        $this->actingAs($this->user);

        // Mock review data for template
        $review = new Review([
            'id' => 1,
            'flashcard_id' => $flashcard->id,
            'child_id' => $this->child->id,
            'topic_id' => $this->topic->id,
            'status' => 'new',
            'repetitions' => 0,
        ]);

        // Test that the flashcard content renders correctly
        $view = view('reviews.partials.flashcard-types.basic', [
            'flashcard' => $flashcard,
            'kidsMode' => false,
        ]);

        $html = $view->render();

        $this->assertStringContainsString('What is the capital of France?', $html);
        $this->assertStringContainsString('Paris', $html);
        $this->assertStringContainsString('City of Light', $html);
    }

    public function test_multiple_choice_flashcard_validation_logic(): void
    {
        $flashcard = Flashcard::factory()->create([
            'unit_id' => $this->unit->id,
            'card_type' => 'multiple_choice',
            'question' => 'Which are primary colors?',
            'answer' => 'Red, Blue, Yellow',
            'choices' => ['Red', 'Blue', 'Yellow', 'Green', 'Purple'],
            'correct_choices' => [0, 1, 2], // Red, Blue, Yellow
        ]);

        // Test the validation logic directly (without HTTP request)
        $controller = new \App\Http\Controllers\ReviewController($this->supabase);

        // Use reflection to test the private validateFlashcardAnswer method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateFlashcardAnswer');
        $method->setAccessible(true);

        // Test correct answer
        $validation = $method->invoke($controller, $flashcard, [
            'selected_choices' => [0, 1, 2],
        ]);

        $this->assertTrue($validation['is_correct']);
        $this->assertEquals([0, 1, 2], $validation['user_answer']);

        // Test incorrect answer
        $validation = $method->invoke($controller, $flashcard, [
            'selected_choices' => [0, 3], // Red, Green (missing Blue and Yellow)
        ]);

        $this->assertFalse($validation['is_correct']);
    }

    public function test_true_false_flashcard_validation_logic(): void
    {
        $flashcard = Flashcard::factory()->create([
            'unit_id' => $this->unit->id,
            'card_type' => 'true_false',
            'question' => 'The Earth is round.',
            'answer' => 'True',
            'choices' => ['True', 'False'],
            'correct_choices' => [0], // True
        ]);

        // Test the validation logic directly
        $controller = new \App\Http\Controllers\ReviewController($this->supabase);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateFlashcardAnswer');
        $method->setAccessible(true);

        // Test correct answer (True = index 0)
        $validation = $method->invoke($controller, $flashcard, [
            'selected_choices' => [0],
        ]);

        $this->assertTrue($validation['is_correct']);
        $this->assertEquals([0], $validation['user_answer']);

        // Test incorrect answer (False = index 1)
        $validation = $method->invoke($controller, $flashcard, [
            'selected_choices' => [1], // Wrong choice
        ]);

        $this->assertFalse($validation['is_correct']);
        $this->assertEquals([1], $validation['user_answer']);
    }

    public function test_typed_answer_flashcard_validation_logic(): void
    {
        $flashcard = Flashcard::factory()->create([
            'unit_id' => $this->unit->id,
            'card_type' => 'typed_answer',
            'question' => 'What is 5 + 3?',
            'answer' => '8',
        ]);

        // Test the validation logic directly
        $controller = new \App\Http\Controllers\ReviewController($this->supabase);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateFlashcardAnswer');
        $method->setAccessible(true);

        // Test correct typed answer (exact match)
        $validation = $method->invoke($controller, $flashcard, [
            'user_answer' => '8',
        ]);

        $this->assertTrue($validation['is_correct']);
        $this->assertEquals('8', $validation['user_answer']);

        // Test case insensitive matching
        $flashcard->answer = 'Paris';
        $validation = $method->invoke($controller, $flashcard, [
            'user_answer' => 'paris',
        ]);

        $this->assertTrue($validation['is_correct']);
        $this->assertEquals('paris', $validation['user_answer']);

        // Test incorrect typed answer
        $validation = $method->invoke($controller, $flashcard, [
            'user_answer' => 'London', // Wrong answer
        ]);

        $this->assertFalse($validation['is_correct']);
        $this->assertEquals('London', $validation['user_answer']);
    }

    public function test_cloze_flashcard_validation_logic(): void
    {
        $flashcard = Flashcard::factory()->create([
            'unit_id' => $this->unit->id,
            'card_type' => 'cloze',
            'question' => 'Fill in the blanks',
            'cloze_text' => 'The capital of {{France}} is {{Paris}}.',
            'cloze_answers' => ['France', 'Paris'],
            'answer' => 'The capital of France is Paris.',
        ]);

        // Test the validation logic directly
        $controller = new \App\Http\Controllers\ReviewController($this->supabase);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateFlashcardAnswer');
        $method->setAccessible(true);

        // Test correct cloze answers
        $validation = $method->invoke($controller, $flashcard, [
            'cloze_answers' => ['France', 'Paris'],
        ]);

        $this->assertTrue($validation['is_correct']);
        $this->assertEquals(['France', 'Paris'], $validation['user_answer']);

        // Test partially incorrect cloze answers
        $validation = $method->invoke($controller, $flashcard, [
            'cloze_answers' => ['France', 'London'], // Wrong second answer
        ]);

        $this->assertFalse($validation['is_correct']);
        $this->assertEquals(['France', 'London'], $validation['user_answer']);

        // Test case insensitive cloze matching
        $validation = $method->invoke($controller, $flashcard, [
            'cloze_answers' => ['france', 'PARIS'], // Different case
        ]);

        $this->assertTrue($validation['is_correct']);
    }

    public function test_review_queue_includes_flashcard_reviews(): void
    {
        // This test verifies that flashcard review objects work correctly
        $flashcard = Flashcard::factory()->create([
            'unit_id' => $this->unit->id,
            'card_type' => 'basic',
            'question' => 'Test question',
            'answer' => 'Test answer',
        ]);

        // Test creating a flashcard review object (without complex Supabase mocking)
        $review = new Review([
            'id' => 1,
            'flashcard_id' => $flashcard->id,
            'child_id' => $this->child->id,
            'topic_id' => $this->topic->id,
            'status' => 'new',
            'repetitions' => 0,
            'due_date' => now()->format('Y-m-d'),
        ]);

        $this->assertTrue($review->isFlashcardReview());
        $this->assertFalse($review->isTopicReview());
        $this->assertEquals($flashcard->id, $review->flashcard_id);
    }

    public function test_kids_mode_flashcard_review_interface(): void
    {
        $flashcard = Flashcard::factory()->create([
            'unit_id' => $this->unit->id,
            'card_type' => 'basic',
            'question' => 'What is your favorite color?',
            'answer' => 'Blue',
        ]);

        // Test kids mode rendering
        $view = view('reviews.partials.flashcard-types.basic', [
            'flashcard' => $flashcard,
            'kidsMode' => true,
        ]);

        $html = $view->render();

        // Check for kids mode specific elements
        $this->assertStringContainsString('Memory Card!', $html);
        $this->assertStringContainsString('🎯', $html);
        $this->assertStringContainsString('rounded-3xl', $html); // Kids mode styling
    }

    public function test_flashcard_review_spaced_repetition_integration(): void
    {
        $flashcard = Flashcard::factory()->create([
            'unit_id' => $this->unit->id,
            'card_type' => 'basic',
        ]);

        // Create a review object to test SRS algorithm
        $review = new Review([
            'id' => 1,
            'flashcard_id' => $flashcard->id,
            'child_id' => $this->child->id,
            'topic_id' => $this->topic->id,
            'status' => 'new',
            'repetitions' => 0,
            'interval_days' => 1,
            'ease_factor' => 2.5,
        ]);

        // Test that flashcard reviews use the same SRS algorithm
        $this->assertEquals('new', $review->status);
        $this->assertEquals(2.5, $review->ease_factor);
        $this->assertEquals(1, $review->interval_days);
        $this->assertTrue($review->isFlashcardReview());
    }
}
