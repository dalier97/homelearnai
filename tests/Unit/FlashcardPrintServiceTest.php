<?php

namespace Tests\Unit;

use App\Models\Flashcard;
use App\Models\Subject;
use App\Models\Unit;
use App\Models\User;
use App\Services\FlashcardPrintService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashcardPrintServiceTest extends TestCase
{
    use RefreshDatabase;

    private FlashcardPrintService $printService;

    private User $user;

    private Subject $subject;

    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->printService = new FlashcardPrintService;

        // Create test data
        $this->user = User::factory()->create();
        $this->subject = Subject::factory()->create(['user_id' => $this->user->id]);
        $this->unit = Unit::factory()->create(['subject_id' => $this->subject->id]);
    }

    public function test_gets_available_layouts(): void
    {
        $layouts = FlashcardPrintService::getAvailableLayouts();

        $this->assertIsArray($layouts);
        $this->assertArrayHasKey('index', $layouts);
        $this->assertArrayHasKey('grid', $layouts);
        $this->assertArrayHasKey('foldable', $layouts);
        $this->assertArrayHasKey('study_sheet', $layouts);
        $this->assertEquals('Traditional Index Cards (3x5)', $layouts['index']);
    }

    public function test_gets_available_page_sizes(): void
    {
        $pageSizes = FlashcardPrintService::getAvailablePageSizes();

        $this->assertIsArray($pageSizes);
        $this->assertArrayHasKey('letter', $pageSizes);
        $this->assertArrayHasKey('a4', $pageSizes);
        $this->assertArrayHasKey('index35', $pageSizes);
        $this->assertArrayHasKey('index46', $pageSizes);
        $this->assertEquals('US Letter (8.5" x 11")', $pageSizes['letter']);
    }

    public function test_validates_print_options(): void
    {
        // Valid options
        $validOptions = [
            'page_size' => 'letter',
            'font_size' => 'medium',
            'color_mode' => 'color',
            'margin' => 'normal',
        ];
        $errors = $this->printService->validateOptions($validOptions);
        $this->assertEmpty($errors);

        // Invalid page size
        $invalidOptions = ['page_size' => 'invalid_size'];
        $errors = $this->printService->validateOptions($invalidOptions);
        $this->assertContains('Invalid page size selected', $errors);

        // Invalid font size
        $invalidOptions = ['font_size' => 'huge'];
        $errors = $this->printService->validateOptions($invalidOptions);
        $this->assertContains('Invalid font size selected', $errors);

        // Invalid color mode
        $invalidOptions = ['color_mode' => 'rainbow'];
        $errors = $this->printService->validateOptions($invalidOptions);
        $this->assertContains('Invalid color mode selected', $errors);
    }

    public function test_generates_pdf_for_basic_cards(): void
    {
        $flashcards = new Collection([
            Flashcard::factory()->create([
                'unit_id' => $this->unit->id,
                'card_type' => 'basic',
                'question' => 'What is 2 + 2?',
                'answer' => '4',
                'difficulty_level' => 'easy',
            ]),
            Flashcard::factory()->create([
                'unit_id' => $this->unit->id,
                'card_type' => 'basic',
                'question' => 'Capital of France?',
                'answer' => 'Paris',
                'hint' => 'City of lights',
            ]),
        ]);

        $pdf = $this->printService->generatePDF($flashcards, 'index');

        $this->assertNotNull($pdf);
        $this->assertInstanceOf(\Barryvdh\DomPDF\PDF::class, $pdf);
    }

    public function test_generates_pdf_for_multiple_choice_cards(): void
    {
        $flashcards = new Collection([
            Flashcard::factory()->create([
                'unit_id' => $this->unit->id,
                'card_type' => 'multiple_choice',
                'question' => 'Which planet is closest to the Sun?',
                'answer' => 'Mercury',
                'choices' => ['Mercury', 'Venus', 'Earth', 'Mars'],
                'correct_choices' => [0],
            ]),
        ]);

        $pdf = $this->printService->generatePDF($flashcards, 'grid');

        $this->assertNotNull($pdf);
        $this->assertInstanceOf(\Barryvdh\DomPDF\PDF::class, $pdf);
    }

    public function test_generates_pdf_for_true_false_cards(): void
    {
        $flashcards = new Collection([
            Flashcard::factory()->create([
                'unit_id' => $this->unit->id,
                'card_type' => 'true_false',
                'question' => 'The Earth is flat.',
                'answer' => 'False',
                'choices' => ['True', 'False'],
                'correct_choices' => [1],
            ]),
        ]);

        $pdf = $this->printService->generatePDF($flashcards, 'foldable');

        $this->assertNotNull($pdf);
    }

    public function test_generates_pdf_for_cloze_cards(): void
    {
        $flashcards = new Collection([
            Flashcard::factory()->create([
                'unit_id' => $this->unit->id,
                'card_type' => 'cloze',
                'question' => 'The capital of {{France}} is {{Paris}}.',
                'answer' => 'France, Paris',
                'cloze_text' => 'The capital of {{France}} is {{Paris}}.',
                'cloze_answers' => ['France', 'Paris'],
            ]),
        ]);

        $pdf = $this->printService->generatePDF($flashcards, 'study_sheet');

        $this->assertNotNull($pdf);
    }

    public function test_generates_pdf_for_image_occlusion_cards(): void
    {
        $flashcards = new Collection([
            Flashcard::factory()->create([
                'unit_id' => $this->unit->id,
                'card_type' => 'image_occlusion',
                'question' => 'Label the heart diagram',
                'answer' => 'Right ventricle',
                'question_image_url' => 'https://example.com/heart.jpg',
                'occlusion_data' => [
                    ['type' => 'rectangle', 'x' => 100, 'y' => 100, 'width' => 50, 'height' => 30],
                ],
            ]),
        ]);

        $pdf = $this->printService->generatePDF($flashcards, 'index');

        $this->assertNotNull($pdf);
    }

    public function test_generates_pdf_with_custom_options(): void
    {
        $flashcards = new Collection([
            Flashcard::factory()->create([
                'unit_id' => $this->unit->id,
                'card_type' => 'basic',
                'question' => 'Test question',
                'answer' => 'Test answer',
                'hint' => 'Test hint',
            ]),
        ]);

        $options = [
            'page_size' => 'a4',
            'font_size' => 'large',
            'color_mode' => 'grayscale',
            'margin' => 'wide',
            'include_answers' => false,
            'include_hints' => false,
        ];

        $pdf = $this->printService->generatePDF($flashcards, 'index', $options);

        $this->assertNotNull($pdf);
    }

    public function test_handles_empty_flashcard_collection(): void
    {
        $flashcards = new Collection([]);

        $pdf = $this->printService->generatePDF($flashcards, 'index');

        $this->assertNotNull($pdf);
    }

    public function test_throws_exception_for_invalid_layout(): void
    {
        $flashcards = new Collection([
            Flashcard::factory()->create(['unit_id' => $this->unit->id]),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid layout: invalid_layout');

        $this->printService->generatePDF($flashcards, 'invalid_layout');
    }

    public function test_generates_pdf_for_mixed_card_types(): void
    {
        $flashcards = new Collection([
            Flashcard::factory()->create([
                'unit_id' => $this->unit->id,
                'card_type' => 'basic',
                'question' => 'Basic question',
                'answer' => 'Basic answer',
            ]),
            Flashcard::factory()->create([
                'unit_id' => $this->unit->id,
                'card_type' => 'multiple_choice',
                'question' => 'MC question',
                'answer' => 'Option A',
                'choices' => ['Option A', 'Option B', 'Option C'],
                'correct_choices' => [0],
            ]),
            Flashcard::factory()->create([
                'unit_id' => $this->unit->id,
                'card_type' => 'true_false',
                'question' => 'TF question',
                'answer' => 'True',
                'choices' => ['True', 'False'],
                'correct_choices' => [0],
            ]),
        ]);

        foreach (['index', 'grid', 'foldable', 'study_sheet'] as $layout) {
            $pdf = $this->printService->generatePDF($flashcards, $layout);
            $this->assertNotNull($pdf, "Failed to generate PDF for layout: {$layout}");
        }
    }

    public function test_generates_pdf_with_special_characters(): void
    {
        $flashcards = new Collection([
            Flashcard::factory()->create([
                'unit_id' => $this->unit->id,
                'card_type' => 'basic',
                'question' => 'What is the chemical formula for water? H₂O',
                'answer' => 'H₂O (dihydrogen monoxide)',
                'hint' => 'It\'s essential for life & found everywhere!',
            ]),
        ]);

        $pdf = $this->printService->generatePDF($flashcards, 'index');

        $this->assertNotNull($pdf);
    }

    public function test_generates_pdf_with_long_content(): void
    {
        $longQuestion = str_repeat('This is a very long question that should test how the PDF generation handles extensive content. ', 20);
        $longAnswer = str_repeat('This is a very long answer that should wrap properly in the PDF layout. ', 15);

        $flashcards = new Collection([
            Flashcard::factory()->create([
                'unit_id' => $this->unit->id,
                'card_type' => 'basic',
                'question' => $longQuestion,
                'answer' => $longAnswer,
            ]),
        ]);

        $pdf = $this->printService->generatePDF($flashcards, 'study_sheet');

        $this->assertNotNull($pdf);
    }

    public function test_generates_pdf_for_large_card_set(): void
    {
        // Generate 25 cards to test performance and pagination
        $flashcards = new Collection;
        for ($i = 1; $i <= 25; $i++) {
            $flashcards->push(Flashcard::factory()->create([
                'unit_id' => $this->unit->id,
                'card_type' => 'basic',
                'question' => "Question {$i}",
                'answer' => "Answer {$i}",
            ]));
        }

        $pdf = $this->printService->generatePDF($flashcards, 'grid');

        $this->assertNotNull($pdf);
        $this->assertEquals(25, $flashcards->count());
    }

    public function test_handles_array_input(): void
    {
        $flashcard = Flashcard::factory()->create([
            'unit_id' => $this->unit->id,
            'card_type' => 'basic',
            'question' => 'Array test',
            'answer' => 'Array answer',
        ]);

        // Test with array instead of Collection
        $pdf = $this->printService->generatePDF([$flashcard], 'index');

        $this->assertNotNull($pdf);
    }

    public function test_validates_all_option_combinations(): void
    {
        $validOptionCombinations = [
            ['page_size' => 'letter', 'font_size' => 'small', 'color_mode' => 'color', 'margin' => 'tight'],
            ['page_size' => 'a4', 'font_size' => 'medium', 'color_mode' => 'grayscale', 'margin' => 'normal'],
            ['page_size' => 'legal', 'font_size' => 'large', 'color_mode' => 'color', 'margin' => 'wide'],
            ['page_size' => 'index35', 'font_size' => 'medium', 'color_mode' => 'grayscale', 'margin' => 'normal'],
        ];

        foreach ($validOptionCombinations as $options) {
            $errors = $this->printService->validateOptions($options);
            $this->assertEmpty($errors, 'Failed validation for options: '.json_encode($options));
        }
    }
}
