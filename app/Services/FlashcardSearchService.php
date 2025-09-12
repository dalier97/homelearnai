<?php

namespace App\Services;

use App\Models\Flashcard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class FlashcardSearchService
{
    private FlashcardCacheService $cacheService;

    public function __construct(FlashcardCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Search flashcards with filters and caching
     */
    public function search(string $query, array $filters = [], ?int $unitId = null, int $perPage = 20): array
    {
        $startTime = microtime(true);

        // Try cache first for exact query/filter combinations
        $cacheKey = $this->buildCacheKey($query, $filters, $unitId);
        $cached = $this->cacheService->cacheSearchResults($query, array_merge($filters, ['unit_id' => $unitId]));

        if ($cached !== null && ! $this->shouldRefreshCache($filters)) {
            return $this->formatSearchResults($cached, $query, microtime(true) - $startTime, true);
        }

        // Build query
        $builder = $this->buildSearchQuery($query, $filters, $unitId);

        // Execute search
        $results = $builder->get();

        // Cache results
        $this->cacheService->cacheSearchResults(
            $query,
            array_merge($filters, ['unit_id' => $unitId]),
            $results
        );

        $executionTime = microtime(true) - $startTime;

        Log::debug('Flashcard search executed', [
            'query' => $query,
            'filters' => $filters,
            'unit_id' => $unitId,
            'results_count' => $results->count(),
            'execution_time' => $executionTime,
        ]);

        return $this->formatSearchResults($results, $query, $executionTime, false);
    }

    /**
     * Advanced search with multiple criteria
     */
    public function advancedSearch(array $criteria, ?int $unitId = null): Collection
    {
        $builder = Flashcard::query()->where('is_active', true);

        if ($unitId !== null) {
            $builder->where('unit_id', $unitId);
        }

        // Text search in question/answer/hint
        if (! empty($criteria['text'])) {
            $text = $criteria['text'];

            if (config('database.default') === 'pgsql') {
                // Use PostgreSQL full-text search
                $builder->whereRaw(
                    "to_tsvector('english', question || ' ' || answer || ' ' || COALESCE(hint, '')) @@ plainto_tsquery('english', ?)",
                    [$text]
                );
            } else {
                // Fallback to LIKE search
                $builder->where(function (Builder $query) use ($text) {
                    $query->where('question', 'ILIKE', "%{$text}%")
                        ->orWhere('answer', 'ILIKE', "%{$text}%")
                        ->orWhere('hint', 'ILIKE', "%{$text}%");
                });
            }
        }

        // Card type filter
        if (! empty($criteria['card_types'])) {
            $builder->whereIn('card_type', $criteria['card_types']);
        }

        // Difficulty filter
        if (! empty($criteria['difficulties'])) {
            $builder->whereIn('difficulty_level', $criteria['difficulties']);
        }

        // Tag filter
        if (! empty($criteria['tags'])) {
            foreach ($criteria['tags'] as $tag) {
                $builder->whereJsonContains('tags', $tag);
            }
        }

        // Date range filter
        if (! empty($criteria['date_from'])) {
            $builder->where('created_at', '>=', $criteria['date_from']);
        }

        if (! empty($criteria['date_to'])) {
            $builder->where('created_at', '<=', $criteria['date_to']);
        }

        // Has images filter
        if (isset($criteria['has_images']) && $criteria['has_images']) {
            $builder->where(function (Builder $query) {
                $query->whereNotNull('question_image_url')
                    ->orWhereNotNull('answer_image_url');
            });
        }

        // Has hints filter
        if (isset($criteria['has_hints']) && $criteria['has_hints']) {
            $builder->whereNotNull('hint');
        }

        // Import source filter
        if (! empty($criteria['import_source'])) {
            $builder->where('import_source', $criteria['import_source']);
        }

        // Sorting
        $sortBy = $criteria['sort_by'] ?? 'created_at';
        $sortDirection = $criteria['sort_direction'] ?? 'desc';

        if (in_array($sortBy, ['created_at', 'updated_at', 'question', 'card_type', 'difficulty_level'])) {
            $builder->orderBy($sortBy, $sortDirection);
        } else {
            $builder->orderBy('created_at', 'desc');
        }

        return $builder->get();
    }

    /**
     * Search with highlighted results
     */
    public function searchWithHighlights(string $query, array $filters = [], ?int $unitId = null): Collection
    {
        $results = $this->search($query, $filters, $unitId)['results'];

        if (empty(trim($query))) {
            return $results;
        }

        // Add highlights to results
        return $results->map(function (Flashcard $flashcard) use ($query) {
            $flashcard->setAttribute('highlighted_question', $this->highlightText($flashcard->question, $query));
            $flashcard->setAttribute('highlighted_answer', $this->highlightText($flashcard->answer, $query));

            if ($flashcard->hint) {
                $flashcard->setAttribute('highlighted_hint', $this->highlightText($flashcard->hint, $query));
            }

            return $flashcard;
        });
    }

    /**
     * Get quick search suggestions
     */
    public function getSearchSuggestions(string $query, ?int $unitId = null, int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $builder = Flashcard::query()
            ->select('question', 'answer', 'card_type')
            ->where('is_active', true)
            ->limit($limit);

        if ($unitId !== null) {
            $builder->where('unit_id', $unitId);
        }

        if (config('database.default') === 'pgsql') {
            // Use PostgreSQL similarity search
            $builder->whereRaw(
                'similarity(question, ?) > 0.3 OR similarity(answer, ?) > 0.3',
                [$query, $query]
            )->orderByRaw('GREATEST(similarity(question, ?), similarity(answer, ?)) DESC', [$query, $query]);
        } else {
            // Fallback to LIKE search
            $builder->where(function (Builder $q) use ($query) {
                $q->where('question', 'ILIKE', "%{$query}%")
                    ->orWhere('answer', 'ILIKE', "%{$query}%");
            });
        }

        return $builder->get()
            ->map(function (Flashcard $card) use ($query) {
                return [
                    'text' => strlen($card->question) > 50 ? substr($card->question, 0, 47).'...' : $card->question,
                    'type' => 'question',
                    'card_type' => $card->card_type,
                    'match_score' => $this->calculateMatchScore($card->question, $query),
                ];
            })
            ->sortByDesc('match_score')
            ->values()
            ->toArray();
    }

    /**
     * Get popular search terms
     */
    public function getPopularSearchTerms(?int $unitId = null, int $limit = 10): array
    {
        // This would typically come from search analytics
        // For now, we'll extract common words from flashcard content

        $builder = Flashcard::query()
            ->select('question', 'answer', 'tags')
            ->where('is_active', true);

        if ($unitId !== null) {
            $builder->where('unit_id', $unitId);
        }

        $flashcards = $builder->get();

        $words = [];
        foreach ($flashcards as $card) {
            // Extract words from question and answer
            $text = $card->question.' '.$card->answer;
            $cardWords = str_word_count(strtolower($text), 1);

            foreach ($cardWords as $word) {
                if (strlen($word) > 3) { // Skip short words
                    $words[$word] = ($words[$word] ?? 0) + 1;
                }
            }

            // Add tags
            if (! empty($card->tags)) {
                foreach ($card->tags as $tag) {
                    $words[strtolower($tag)] = ($words[strtolower($tag)] ?? 0) + 2; // Give tags higher weight
                }
            }
        }

        arsort($words);

        return array_slice(array_keys($words), 0, $limit);
    }

    /**
     * Build search query
     */
    private function buildSearchQuery(string $query, array $filters = [], ?int $unitId = null): Builder
    {
        $builder = Flashcard::query()
            ->with(['unit.subject'])
            ->where('is_active', true);

        if ($unitId !== null) {
            $builder->where('unit_id', $unitId);
        }

        // Text search
        if (! empty(trim($query))) {
            if (config('database.default') === 'pgsql') {
                // Use PostgreSQL full-text search with ranking
                $builder->select('flashcards.*')
                    ->selectRaw(
                        "ts_rank(to_tsvector('english', question || ' ' || answer || ' ' || COALESCE(hint, '')), plainto_tsquery('english', ?)) as search_rank",
                        [$query]
                    )
                    ->whereRaw(
                        "to_tsvector('english', question || ' ' || answer || ' ' || COALESCE(hint, '')) @@ plainto_tsquery('english', ?)",
                        [$query]
                    )
                    ->orderBy('search_rank', 'desc');
            } else {
                // Fallback to LIKE search with relevance scoring
                $builder->where(function (Builder $q) use ($query) {
                    $q->where('question', 'ILIKE', "%{$query}%")
                        ->orWhere('answer', 'ILIKE', "%{$query}%")
                        ->orWhere('hint', 'ILIKE', "%{$query}%");
                });
            }
        }

        // Apply filters
        if (! empty($filters['card_type'])) {
            $builder->where('card_type', $filters['card_type']);
        }

        if (! empty($filters['difficulty'])) {
            $builder->where('difficulty_level', $filters['difficulty']);
        }

        if (! empty($filters['has_images'])) {
            $builder->where(function (Builder $q) {
                $q->whereNotNull('question_image_url')
                    ->orWhereNotNull('answer_image_url');
            });
        }

        if (! empty($filters['has_hints'])) {
            $builder->whereNotNull('hint');
        }

        if (! empty($filters['tag'])) {
            $builder->whereJsonContains('tags', $filters['tag']);
        }

        // Default ordering if no text search
        if (empty(trim($query))) {
            $builder->orderBy('created_at', 'desc');
        }

        return $builder;
    }

    /**
     * Format search results
     */
    private function formatSearchResults(Collection $results, string $query, float $executionTime, bool $fromCache): array
    {
        return [
            'results' => $results,
            'count' => $results->count(),
            'query' => $query,
            'execution_time' => round($executionTime, 4),
            'from_cache' => $fromCache,
            'performance' => [
                'execution_time_ms' => round($executionTime * 1000, 2),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
            ],
        ];
    }

    /**
     * Build cache key for search results
     */
    private function buildCacheKey(string $query, array $filters, ?int $unitId): string
    {
        $parts = [
            'query' => $query,
            'filters' => $filters,
            'unit_id' => $unitId,
        ];

        return 'flashcard_search:'.md5(serialize($parts));
    }

    /**
     * Check if cache should be refreshed
     */
    private function shouldRefreshCache(array $filters): bool
    {
        // Refresh cache if real-time filters are applied
        return isset($filters['real_time']) && $filters['real_time'];
    }

    /**
     * Highlight search terms in text
     */
    private function highlightText(string $text, string $query): string
    {
        if (empty(trim($query))) {
            return $text;
        }

        $words = explode(' ', trim($query));

        foreach ($words as $word) {
            if (strlen($word) > 2) {
                $text = preg_replace(
                    '/('.preg_quote($word, '/').')/i',
                    '<mark class="bg-yellow-200 px-1 rounded">$1</mark>',
                    $text
                );
            }
        }

        return $text;
    }

    /**
     * Calculate match score for suggestions
     */
    private function calculateMatchScore(string $text, string $query): float
    {
        $text = strtolower($text);
        $query = strtolower($query);

        // Exact match gets highest score
        if (strpos($text, $query) === 0) {
            return 1.0;
        }

        // Contains query gets medium score
        if (strpos($text, $query) !== false) {
            return 0.7;
        }

        // Similar words get lower score
        $words = explode(' ', $query);
        $matches = 0;

        foreach ($words as $word) {
            if (strpos($text, $word) !== false) {
                $matches++;
            }
        }

        return count($words) > 0 ? ($matches / count($words)) * 0.5 : 0;
    }
}
