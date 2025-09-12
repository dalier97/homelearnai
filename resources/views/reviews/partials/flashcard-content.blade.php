{{-- Flashcard Content Display for Different Card Types --}}
<div class="flashcard-review-content">
    @switch($flashcard->card_type)
        @case('basic')
            @include('reviews.partials.flashcard-types.basic', ['flashcard' => $flashcard, 'kidsMode' => $kidsMode])
            @break
            
        @case('multiple_choice')
            @include('reviews.partials.flashcard-types.multiple-choice', ['flashcard' => $flashcard, 'kidsMode' => $kidsMode])
            @break
            
        @case('true_false')
            @include('reviews.partials.flashcard-types.true-false', ['flashcard' => $flashcard, 'kidsMode' => $kidsMode])
            @break
            
        @case('cloze')
            @include('reviews.partials.flashcard-types.cloze', ['flashcard' => $flashcard, 'kidsMode' => $kidsMode])
            @break
            
        @case('typed_answer')
            @include('reviews.partials.flashcard-types.typed-answer', ['flashcard' => $flashcard, 'kidsMode' => $kidsMode])
            @break
            
        @case('image_occlusion')
            @include('reviews.partials.flashcard-types.image-occlusion', ['flashcard' => $flashcard, 'kidsMode' => $kidsMode])
            @break
            
        @default
            @include('reviews.partials.flashcard-types.basic', ['flashcard' => $flashcard, 'kidsMode' => $kidsMode])
    @endswitch
</div>