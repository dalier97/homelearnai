# Flashcard System - Developer Guide

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [Database Schema](#database-schema)
3. [Service Layer](#service-layer)
4. [Model Layer](#model-layer)
5. [Controller Layer](#controller-layer)
6. [Frontend Integration](#frontend-integration)
7. [Extension Points](#extension-points)
8. [Testing Strategy](#testing-strategy)
9. [Performance Considerations](#performance-considerations)
10. [Security Implementation](#security-implementation)
11. [Deployment](#deployment)
12. [Monitoring](#monitoring)

## Architecture Overview

The Flashcard System follows a layered architecture pattern with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────────┐
│                        Frontend Layer                       │
│  HTMX + Alpine.js + Tailwind CSS + JavaScript Components  │
└─────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────┐
│                     Controller Layer                       │
│           FlashcardController + Specialized Controllers    │
└─────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────┐
│                      Service Layer                         │
│   Import/Export + Print + Search + Cache + Performance    │
└─────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────┐
│                       Model Layer                          │
│        Flashcard + Unit + Review + FlashcardImport        │
└─────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────┐
│                     Database Layer                         │
│              PostgreSQL with Optimized Indexes            │
└─────────────────────────────────────────────────────────────┘
```

### Core Principles

1. **Single Responsibility**: Each service handles one specific domain
2. **Dependency Injection**: Services are injected via Laravel's container
3. **Interface Segregation**: Clear contracts between layers
4. **Open/Closed**: Easy to extend without modifying existing code
5. **Domain-Driven Design**: Business logic encapsulated in services

## Database Schema

### Primary Tables

#### flashcards
```sql
CREATE TABLE flashcards (
    id BIGSERIAL PRIMARY KEY,
    unit_id BIGINT NOT NULL REFERENCES units(id) ON DELETE CASCADE,
    card_type VARCHAR(50) NOT NULL DEFAULT 'basic',
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    hint TEXT NULL,
    
    -- Multiple choice fields
    choices JSONB NULL,
    correct_choices JSONB NULL,
    
    -- Cloze deletion fields
    cloze_text TEXT NULL,
    cloze_answers JSONB NULL,
    
    -- Image fields
    question_image_url VARCHAR(255) NULL,
    answer_image_url VARCHAR(255) NULL,
    occlusion_data JSONB NULL,
    
    -- Metadata
    difficulty_level VARCHAR(20) NOT NULL DEFAULT 'medium',
    tags JSONB NULL,
    is_active BOOLEAN NOT NULL DEFAULT true,
    import_source VARCHAR(50) NULL,
    
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMP NULL
);
```

#### flashcard_imports
```sql
CREATE TABLE flashcard_imports (
    id BIGSERIAL PRIMARY KEY,
    unit_id BIGINT NOT NULL REFERENCES units(id) ON DELETE CASCADE,
    user_id VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NULL,
    format VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    total_rows INTEGER NOT NULL DEFAULT 0,
    imported_count INTEGER NOT NULL DEFAULT 0,
    error_count INTEGER NOT NULL DEFAULT 0,
    duplicate_count INTEGER NOT NULL DEFAULT 0,
    preview_data JSONB NULL,
    error_details JSONB NULL,
    import_settings JSONB NULL,
    
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);
```

### Indexes for Performance

```sql
-- Primary access patterns
CREATE INDEX idx_flashcards_unit_active ON flashcards(unit_id, is_active);
CREATE INDEX idx_flashcards_type_difficulty ON flashcards(card_type, difficulty_level);
CREATE INDEX idx_flashcards_created_at ON flashcards(created_at);

-- Search optimization
CREATE INDEX idx_flashcards_search ON flashcards USING gin(
    to_tsvector('english', question || ' ' || answer || ' ' || COALESCE(hint, ''))
);

-- Tag-based filtering
CREATE INDEX idx_flashcards_tags ON flashcards USING gin(tags);

-- Import tracking
CREATE INDEX idx_flashcard_imports_user_status ON flashcard_imports(user_id, status);
CREATE INDEX idx_flashcard_imports_unit_created ON flashcard_imports(unit_id, created_at);
```

### JSONB Field Structures

#### Card Type: Multiple Choice
```json
{
    "choices": ["Option A", "Option B", "Option C", "Option D"],
    "correct_choices": [0, 2]  // Indexes of correct options
}
```

#### Card Type: Cloze Deletion
```json
{
    "cloze_text": "The {{capital}} of {{country}} is {{city}}",
    "cloze_answers": ["capital", "France", "Paris"]
}
```

#### Card Type: Image Occlusion
```json
{
    "occlusion_data": [
        {
            "x": 100,
            "y": 50,
            "width": 80,
            "height": 40,
            "label": "aorta"
        }
    ]
}
```

## Service Layer

### FlashcardImportService

Central service for handling all import operations.

```php
<?php

namespace App\Services;

class FlashcardImportService
{
    private AnkiImportService $ankiImporter;
    private MnemosyneImportService $mnemosyneImporter;
    private DuplicateDetectionService $duplicateDetector;
    private MediaStorageService $mediaStorage;

    public function __construct(
        AnkiImportService $ankiImporter,
        MnemosyneImportService $mnemosyneImporter,
        DuplicateDetectionService $duplicateDetector,
        MediaStorageService $mediaStorage
    ) {
        $this->ankiImporter = $ankiImporter;
        $this->mnemosyneImporter = $mnemosyneImporter;
        $this->duplicateDetector = $duplicateDetector;
        $this->mediaStorage = $mediaStorage;
    }

    /**
     * Parse and preview import data
     */
    public function previewImport(array $data): array
    {
        $format = $this->detectFormat($data);
        $parser = $this->getParser($format);
        
        return $parser->preview($data);
    }

    /**
     * Execute import operation
     */
    public function executeImport(int $unitId, array $data): array
    {
        $format = $this->detectFormat($data);
        $parser = $this->getParser($format);
        
        $cards = $parser->parse($data);
        $duplicates = $this->duplicateDetector->findDuplicates($cards, $unitId);
        
        return $this->processImport($unitId, $cards, $duplicates, $data['options'] ?? []);
    }

    private function detectFormat(array $data): string
    {
        // Format detection logic
    }

    private function getParser(string $format): ImportParserInterface
    {
        return match($format) {
            'anki' => $this->ankiImporter,
            'mnemosyne' => $this->mnemosyneImporter,
            'quizlet' => new QuizletImportService(),
            'csv' => new CsvImportService(),
            default => throw new UnsupportedFormatException($format)
        };
    }
}
```

### FlashcardExportService

Handles export to various formats.

```php
<?php

namespace App\Services;

class FlashcardExportService
{
    public function export(Collection $flashcards, string $format, array $options = []): array
    {
        $exporter = $this->getExporter($format);
        
        return $exporter->export($flashcards, $options);
    }

    private function getExporter(string $format): ExporterInterface
    {
        return match($format) {
            'anki' => new AnkiExporter(),
            'quizlet' => new QuizletExporter(),
            'csv' => new CsvExporter(),
            'json' => new JsonExporter(),
            'pdf' => new PdfExporter(),
            default => throw new UnsupportedFormatException($format)
        };
    }
}
```

### FlashcardSearchService

Advanced search functionality with caching.

```php
<?php

namespace App\Services;

class FlashcardSearchService
{
    private FlashcardCacheService $cache;

    public function search(int $unitId, string $query, array $filters = []): Collection
    {
        $cacheKey = $this->generateCacheKey($unitId, $query, $filters);
        
        return $this->cache->remember($cacheKey, 300, function () use ($unitId, $query, $filters) {
            return $this->performSearch($unitId, $query, $filters);
        });
    }

    private function performSearch(int $unitId, string $query, array $filters): Collection
    {
        $builder = Flashcard::query()
            ->where('unit_id', $unitId)
            ->where('is_active', true);

        // Full-text search
        if (!empty($query)) {
            $builder->whereRaw(
                "to_tsvector('english', question || ' ' || answer || ' ' || COALESCE(hint, '')) @@ plainto_tsquery('english', ?)",
                [$query]
            );
        }

        // Apply filters
        $this->applyFilters($builder, $filters);

        return $builder->get();
    }

    private function applyFilters($builder, array $filters): void
    {
        if (!empty($filters['card_types'])) {
            $builder->whereIn('card_type', $filters['card_types']);
        }

        if (!empty($filters['difficulty_levels'])) {
            $builder->whereIn('difficulty_level', $filters['difficulty_levels']);
        }

        if (!empty($filters['tags'])) {
            $builder->whereJsonContains('tags', $filters['tags']);
        }

        if (!empty($filters['date_range'])) {
            $builder->whereBetween('created_at', [
                $filters['date_range']['start'],
                $filters['date_range']['end']
            ]);
        }
    }
}
```

### FlashcardCacheService

Sophisticated caching layer for performance optimization.

```php
<?php

namespace App\Services;

class FlashcardCacheService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'flashcards:';

    public function warmUserCache(string $userId): void
    {
        $units = Unit::where('user_id', $userId)->pluck('id');
        
        foreach ($units as $unitId) {
            $this->warmUnitCache($unitId);
        }
    }

    public function warmUnitCache(int $unitId): void
    {
        $flashcards = Flashcard::where('unit_id', $unitId)
            ->where('is_active', true)
            ->get();

        Cache::put(
            self::CACHE_PREFIX . "unit:{$unitId}",
            $flashcards,
            self::CACHE_TTL
        );

        // Cache by type
        $byType = $flashcards->groupBy('card_type');
        foreach ($byType as $type => $cards) {
            Cache::put(
                self::CACHE_PREFIX . "unit:{$unitId}:type:{$type}",
                $cards,
                self::CACHE_TTL
            );
        }
    }

    public function invalidateUnit(int $unitId): void
    {
        $pattern = self::CACHE_PREFIX . "unit:{$unitId}*";
        $this->clearCachePattern($pattern);
    }

    public function remember(string $key, int $ttl, callable $callback)
    {
        return Cache::remember(self::CACHE_PREFIX . $key, $ttl, $callback);
    }
}
```

## Model Layer

### Flashcard Model

The main model with all card type logic.

```php
<?php

namespace App\Models;

class Flashcard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unit_id', 'card_type', 'question', 'answer', 'hint',
        'choices', 'correct_choices', 'cloze_text', 'cloze_answers',
        'question_image_url', 'answer_image_url', 'occlusion_data',
        'difficulty_level', 'tags', 'is_active', 'import_source'
    ];

    protected $casts = [
        'choices' => 'array',
        'correct_choices' => 'array',
        'cloze_answers' => 'array',
        'occlusion_data' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean'
    ];

    // Card type validation
    public function validateCardData(): array
    {
        return match($this->card_type) {
            'multiple_choice' => $this->validateMultipleChoice(),
            'cloze' => $this->validateCloze(),
            'image_occlusion' => $this->validateImageOcclusion(),
            default => []
        };
    }

    private function validateMultipleChoice(): array
    {
        $errors = [];
        
        if (empty($this->choices) || count($this->choices) < 2) {
            $errors[] = 'Multiple choice cards need at least 2 choices';
        }
        
        if (empty($this->correct_choices)) {
            $errors[] = 'Must specify correct choices';
        }
        
        return $errors;
    }

    // Relationships
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('card_type', $type);
    }

    public function scopeForUnit($query, int $unitId)
    {
        return $query->where('unit_id', $unitId);
    }
}
```

### FlashcardImport Model

Tracks import operations and their status.

```php
<?php

namespace App\Models;

class FlashcardImport extends Model
{
    protected $fillable = [
        'unit_id', 'user_id', 'filename', 'format', 'status',
        'total_rows', 'imported_count', 'error_count', 'duplicate_count',
        'preview_data', 'error_details', 'import_settings'
    ];

    protected $casts = [
        'preview_data' => 'array',
        'error_details' => 'array',
        'import_settings' => 'array'
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getSuccessRate(): float
    {
        if ($this->total_rows === 0) {
            return 0;
        }
        
        return ($this->imported_count / $this->total_rows) * 100;
    }
}
```

## Controller Layer

### FlashcardController

Main controller handling all flashcard operations.

```php
<?php

namespace App\Http\Controllers;

class FlashcardController extends Controller
{
    private FlashcardService $flashcardService;
    private FlashcardImportService $importService;
    private FlashcardExportService $exportService;

    public function __construct(
        FlashcardService $flashcardService,
        FlashcardImportService $importService,
        FlashcardExportService $exportService
    ) {
        $this->flashcardService = $flashcardService;
        $this->importService = $importService;
        $this->exportService = $exportService;
    }

    public function index(Request $request, int $unitId)
    {
        $this->authorize('view', Unit::findOrFail($unitId));

        $flashcards = $this->flashcardService->getForUnit(
            $unitId,
            $request->only(['page', 'per_page', 'card_type', 'difficulty', 'search'])
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $flashcards->items(),
                'meta' => [
                    'total' => $flashcards->total(),
                    'per_page' => $flashcards->perPage(),
                    'current_page' => $flashcards->currentPage(),
                    'last_page' => $flashcards->lastPage()
                ]
            ]);
        }

        return view('flashcards.partials.flashcard-list', compact('flashcards'));
    }

    public function store(FlashcardRequest $request, int $unitId)
    {
        $this->authorize('create', [Flashcard::class, $unitId]);

        $flashcard = $this->flashcardService->create($unitId, $request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $flashcard
            ], 201);
        }

        return redirect()->back()->with('success', 'Flashcard created successfully');
    }

    public function previewImport(Request $request, int $unitId)
    {
        $this->authorize('create', [Flashcard::class, $unitId]);

        $preview = $this->importService->previewImport($request->all());

        return response()->json([
            'success' => true,
            'data' => $preview
        ]);
    }

    public function executeImport(Request $request, int $unitId)
    {
        $this->authorize('create', [Flashcard::class, $unitId]);

        $result = $this->importService->executeImport($unitId, $request->all());

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }
}
```

### Request Validation

```php
<?php

namespace App\Http\Requests;

class FlashcardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return !session('kids_mode', false);
    }

    public function rules(): array
    {
        $rules = [
            'card_type' => 'required|in:' . implode(',', Flashcard::getCardTypes()),
            'question' => 'required|string|max:1000',
            'difficulty_level' => 'required|in:' . implode(',', Flashcard::getDifficultyLevels()),
            'hint' => 'nullable|string|max:500',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50'
        ];

        // Type-specific validation
        match($this->card_type) {
            'basic', 'typed_answer' => $rules['answer'] = 'required|string|max:1000',
            'multiple_choice' => array_merge($rules, [
                'choices' => 'required|array|min:2|max:6',
                'choices.*' => 'required|string|max:200',
                'correct_choices' => 'required|array|min:1',
                'correct_choices.*' => 'integer|min:0'
            ]),
            'true_false' => array_merge($rules, [
                'choices' => 'required|array|size:2',
                'correct_choices' => 'required|array|size:1'
            ]),
            'cloze' => array_merge($rules, [
                'cloze_text' => 'required|string|max:1000',
                'cloze_answers' => 'required|array|min:1',
                'cloze_answers.*' => 'required|string|max:100'
            ]),
            default => []
        };

        return $rules;
    }

    public function messages(): array
    {
        return [
            'choices.min' => 'Multiple choice cards need at least 2 options',
            'correct_choices.required' => 'You must specify which choices are correct',
            'cloze_text.required' => 'Cloze cards need text with {{blanks}}',
            'cloze_answers.required' => 'You must provide answers for all cloze blanks'
        ];
    }
}
```

## Frontend Integration

### HTMX Implementation

The flashcard system uses HTMX for dynamic interactions without full page reloads.

```html
<!-- Flashcard List -->
<div id="flashcard-list" hx-get="/api/units/{{ $unit->id }}/flashcards" 
     hx-trigger="load, flashcard-updated from:body">
    <!-- Content loads here -->
</div>

<!-- Add Flashcard Modal -->
<div id="flashcard-modal" class="modal">
    <form hx-post="/api/units/{{ $unit->id }}/flashcards"
          hx-target="#flashcard-list"
          hx-swap="innerHTML"
          hx-on::after-request="closeModal()">
        
        <select name="card_type" 
                hx-get="/flashcards/type-fields"
                hx-target="#type-specific-fields"
                hx-swap="innerHTML">
            <option value="basic">Basic Q&A</option>
            <option value="multiple_choice">Multiple Choice</option>
            <!-- ... -->
        </select>

        <div id="type-specific-fields">
            <!-- Dynamic fields load here based on type -->
        </div>

        <button type="submit">Create Flashcard</button>
    </form>
</div>
```

### Alpine.js Components

```javascript
// Flashcard management component
Alpine.data('flashcardManager', () => ({
    flashcards: [],
    currentCard: null,
    isReviewing: false,
    
    init() {
        this.loadFlashcards();
    },
    
    async loadFlashcards() {
        const response = await fetch(`/api/units/${this.unitId}/flashcards`);
        const data = await response.json();
        this.flashcards = data.data;
    },
    
    startReview() {
        this.isReviewing = true;
        this.currentCard = this.flashcards[0];
    },
    
    nextCard() {
        const currentIndex = this.flashcards.indexOf(this.currentCard);
        if (currentIndex < this.flashcards.length - 1) {
            this.currentCard = this.flashcards[currentIndex + 1];
        } else {
            this.finishReview();
        }
    },
    
    rateCard(difficulty) {
        // Send rating to backend
        fetch(`/api/reviews/${this.currentCard.id}/rate`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ difficulty })
        });
        
        this.nextCard();
    }
}));
```

### Tailwind CSS Styling

```css
/* Flashcard-specific styles */
.flashcard {
    @apply bg-white rounded-lg shadow-md p-6 border border-gray-200;
    @apply transition-all duration-200 hover:shadow-lg;
}

.flashcard-question {
    @apply text-lg font-medium text-gray-900 mb-4;
}

.flashcard-answer {
    @apply text-gray-700 bg-gray-50 p-4 rounded border-l-4 border-blue-500;
}

.flashcard-hint {
    @apply text-sm text-gray-500 italic mt-2;
}

.card-type-badge {
    @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
}

.card-type-basic { @apply bg-blue-100 text-blue-800; }
.card-type-multiple-choice { @apply bg-green-100 text-green-800; }
.card-type-cloze { @apply bg-purple-100 text-purple-800; }
.card-type-true-false { @apply bg-yellow-100 text-yellow-800; }
```

## Extension Points

### Adding New Card Types

1. **Add to Model Constants**:
```php
// In Flashcard.php
public const CARD_TYPE_NEW_TYPE = 'new_type';

public static function getCardTypes(): array
{
    return [
        // existing types...
        self::CARD_TYPE_NEW_TYPE,
    ];
}
```

2. **Add Validation Logic**:
```php
// In Flashcard.php
public function validateCardData(): array
{
    return match($this->card_type) {
        // existing cases...
        'new_type' => $this->validateNewType(),
        default => []
    };
}

private function validateNewType(): array
{
    // Validation logic for new type
}
```

3. **Create Review Template**:
```blade
{{-- resources/views/reviews/partials/flashcard-types/new-type.blade.php --}}
<div class="new-type-card">
    <!-- Review interface for new type -->
</div>
```

4. **Update Request Validation**:
```php
// In FlashcardRequest.php
match($this->card_type) {
    // existing cases...
    'new_type' => array_merge($rules, [
        'new_field' => 'required|string|max:255'
    ]),
}
```

### Adding Import/Export Formats

1. **Create Parser Class**:
```php
<?php

namespace App\Services\Import;

class NewFormatImportService implements ImportParserInterface
{
    public function preview(array $data): array
    {
        // Preview logic
    }

    public function parse(array $data): array
    {
        // Parsing logic
    }
}
```

2. **Register in ImportService**:
```php
// In FlashcardImportService.php
private function getParser(string $format): ImportParserInterface
{
    return match($format) {
        // existing formats...
        'new_format' => new NewFormatImportService(),
        default => throw new UnsupportedFormatException($format)
    };
}
```

3. **Create Exporter**:
```php
<?php

namespace App\Services\Export;

class NewFormatExporter implements ExporterInterface
{
    public function export(Collection $flashcards, array $options = []): array
    {
        // Export logic
    }
}
```

### Custom Search Filters

```php
// Extend FlashcardSearchService
class ExtendedSearchService extends FlashcardSearchService
{
    protected function applyCustomFilters($builder, array $filters): void
    {
        if (!empty($filters['custom_field'])) {
            $builder->where('custom_field', $filters['custom_field']);
        }
        
        if (!empty($filters['performance_threshold'])) {
            $builder->whereHas('reviews', function ($query) use ($filters) {
                $query->havingRaw('AVG(score) >= ?', [$filters['performance_threshold']]);
            });
        }
    }
}
```

## Testing Strategy

### Unit Tests

```php
<?php

namespace Tests\Unit\Services;

class FlashcardImportServiceTest extends TestCase
{
    private FlashcardImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FlashcardImportService::class);
    }

    public function test_parses_quizlet_format(): void
    {
        $content = "Question 1\tAnswer 1\nQuestion 2\tAnswer 2";
        
        $result = $this->service->parseText($content);
        
        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['cards']);
        $this->assertEquals('Question 1', $result['cards'][0]['question']);
    }

    public function test_detects_multiple_choice_format(): void
    {
        $content = "Question\ta) Option 1\tb) Option 2\tc) Option 3\ta";
        
        $result = $this->service->parseText($content);
        
        $this->assertEquals('multiple_choice', $result['cards'][0]['card_type']);
        $this->assertCount(3, $result['cards'][0]['choices']);
    }
}
```

### Feature Tests

```php
<?php

namespace Tests\Feature;

class FlashcardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_create_flashcard(): void
    {
        $user = User::factory()->create();
        $unit = Unit::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson("/api/units/{$unit->id}/flashcards", [
                'card_type' => 'basic',
                'question' => 'Test question',
                'answer' => 'Test answer',
                'difficulty_level' => 'medium'
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('flashcards', [
            'unit_id' => $unit->id,
            'question' => 'Test question'
        ]);
    }

    public function test_kids_mode_cannot_create_flashcard(): void
    {
        $user = User::factory()->create();
        $unit = Unit::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->withSession(['kids_mode' => true])
            ->postJson("/api/units/{$unit->id}/flashcards", [
                'card_type' => 'basic',
                'question' => 'Test question',
                'answer' => 'Test answer'
            ]);

        $response->assertStatus(403);
    }
}
```

### E2E Tests

```typescript
// tests/e2e/flashcard-system.spec.ts
import { test, expect } from '@playwright/test';

test('should create and review flashcard', async ({ page }) => {
    await page.goto('/units/1');
    
    // Create flashcard
    await page.click('text=Add Flashcard');
    await page.fill('[name="question"]', 'What is 2+2?');
    await page.fill('[name="answer"]', '4');
    await page.click('text=Save');
    
    // Verify creation
    await expect(page.locator('.flashcard-list')).toContainText('What is 2+2?');
    
    // Start review
    await page.click('text=Start Review');
    await expect(page.locator('.review-question')).toContainText('What is 2+2?');
    
    // Show answer
    await page.click('text=Show Answer');
    await expect(page.locator('.review-answer')).toContainText('4');
    
    // Rate difficulty
    await page.click('text=Good');
    
    // Verify review completed
    await expect(page.locator('.review-complete')).toBeVisible();
});
```

## Performance Considerations

### Database Optimization

1. **Proper Indexing**:
```sql
-- Search performance
CREATE INDEX CONCURRENTLY idx_flashcards_search 
ON flashcards USING gin(to_tsvector('english', question || ' ' || answer));

-- Tag queries
CREATE INDEX CONCURRENTLY idx_flashcards_tags 
ON flashcards USING gin(tags);

-- Compound indexes for common queries
CREATE INDEX CONCURRENTLY idx_flashcards_unit_type_active 
ON flashcards(unit_id, card_type, is_active);
```

2. **Query Optimization**:
```php
// Eager loading to prevent N+1 queries
$flashcards = Flashcard::with('unit.subject')
    ->where('unit_id', $unitId)
    ->paginate(20);

// Use raw queries for complex operations
$stats = DB::select('
    SELECT card_type, COUNT(*) as count, AVG(difficulty_level) as avg_difficulty
    FROM flashcards 
    WHERE unit_id = ? AND is_active = true
    GROUP BY card_type
', [$unitId]);
```

### Caching Strategy

1. **Multi-Level Caching**:
```php
// User-level cache (long TTL)
Cache::remember("user:{$userId}:units", 3600, function () use ($userId) {
    return Unit::where('user_id', $userId)->with('flashcards')->get();
});

// Unit-level cache (medium TTL)
Cache::remember("unit:{$unitId}:flashcards", 1800, function () use ($unitId) {
    return Flashcard::where('unit_id', $unitId)->active()->get();
});

// Search cache (short TTL)
Cache::remember("search:{$hash}", 300, function () use ($query) {
    return $this->performSearch($query);
});
```

2. **Cache Invalidation**:
```php
// Observer pattern for automatic invalidation
class FlashcardObserver
{
    public function saved(Flashcard $flashcard): void
    {
        Cache::forget("unit:{$flashcard->unit_id}:flashcards");
        Cache::tags(['user:' . $flashcard->unit->user_id])->flush();
    }
}
```

### Background Processing

```php
// Queue large import operations
class ProcessFlashcardImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(FlashcardImportService $service): void
    {
        $service->executeImport($this->unitId, $this->importData);
    }
}

// Dispatch job
ProcessFlashcardImport::dispatch($unitId, $importData);
```

## Security Implementation

### Authorization Policies

```php
<?php

namespace App\Policies;

class FlashcardPolicy
{
    public function view(User $user, Flashcard $flashcard): bool
    {
        return $flashcard->unit->subject->user_id === $user->id;
    }

    public function create(User $user, int $unitId): bool
    {
        // Block in kids mode
        if (session('kids_mode', false)) {
            return false;
        }

        $unit = Unit::find($unitId);
        return $unit && $unit->subject->user_id === $user->id;
    }

    public function update(User $user, Flashcard $flashcard): bool
    {
        return !session('kids_mode', false) && 
               $flashcard->unit->subject->user_id === $user->id;
    }
}
```

### Input Sanitization

```php
// Custom validation rules
class FlashcardRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Sanitize input
        $this->merge([
            'question' => strip_tags($this->question),
            'answer' => strip_tags($this->answer),
            'hint' => strip_tags($this->hint ?? ''),
        ]);
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:1000', new NoScriptTags],
            'answer' => ['required', 'string', 'max:1000', new NoScriptTags],
            // ...
        ];
    }
}
```

### Rate Limiting

```php
// In RouteServiceProvider
RateLimiter::for('flashcard-import', function (Request $request) {
    return Limit::perMinute(5)->by($request->user()?->id);
});

// Apply to routes
Route::middleware('throttle:flashcard-import')
    ->post('/import/execute', [FlashcardController::class, 'executeImport']);
```

## Deployment

### Environment Configuration

```env
# Production settings
FLASHCARD_CACHE_TTL=3600
FLASHCARD_MAX_IMPORT_SIZE=1000
FLASHCARD_STORAGE_DISK=s3
FLASHCARD_SEARCH_DRIVER=elasticsearch

# Performance tuning
DB_CONNECTION=pgsql
REDIS_CLIENT=phpredis
QUEUE_CONNECTION=redis
```

### Docker Configuration

```dockerfile
# Dockerfile additions for flashcard features
RUN apt-get update && apt-get install -y \
    poppler-utils \
    imagemagick \
    && rm -rf /var/lib/apt/lists/*

# Copy flashcard assets
COPY resources/js/flashcards ./resources/js/flashcards
COPY resources/views/flashcards ./resources/views/flashcards
```

### Database Migrations

```bash
# Deploy database changes
php artisan migrate --force

# Warm caches after deployment
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
php artisan queue:restart
```

## Monitoring

### Performance Metrics

```php
// Custom metrics collection
class FlashcardMetrics
{
    public function recordImportDuration(float $duration, string $format): void
    {
        Metrics::histogram('flashcard_import_duration', $duration)
            ->tag('format', $format);
    }

    public function recordSearchLatency(float $latency, int $resultCount): void
    {
        Metrics::histogram('flashcard_search_latency', $latency)
            ->tag('result_count_range', $this->getCountRange($resultCount));
    }
}
```

### Error Tracking

```php
// Custom error reporting
class FlashcardErrorService
{
    public function reportImportError(Exception $e, array $context): void
    {
        Log::error('Flashcard import failed', [
            'exception' => $e,
            'context' => $context,
            'user_id' => auth()->id(),
            'unit_id' => $context['unit_id'] ?? null
        ]);

        // Send to error tracking service
        app('sentry')->captureException($e, $context);
    }
}
```

### Health Checks

```php
// Health check endpoint
class FlashcardHealthCheck
{
    public function check(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'search' => $this->checkSearch()
        ];
    }

    private function checkDatabase(): bool
    {
        try {
            return Flashcard::count() >= 0;
        } catch (Exception $e) {
            return false;
        }
    }
}
```

This developer guide provides comprehensive coverage of the flashcard system architecture, implementation details, and extension points. It serves as a complete reference for developers working on or extending the system.