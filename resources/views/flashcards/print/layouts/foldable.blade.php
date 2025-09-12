{{-- Foldable Cards Layout (2 per page, fold in middle) --}}
@foreach($cards as $pageCards)
    <div class="foldable-page">
        @foreach($pageCards as $card)
            <div class="foldable-card">
                {{-- Card Front (Top half) --}}
                <div class="foldable-front">
                    <div class="card-type-badge">{{ ucfirst(str_replace('_', ' ', $card['card_type'])) }}</div>
                    <div class="difficulty-badge difficulty-{{ $card['difficulty'] }}">{{ ucfirst($card['difficulty']) }}</div>
                    
                    <div class="card-question">
                        <strong>Question:</strong> {!! $card['question'] !!}
                    </div>
                    
                    @if($card['card_type'] === 'multiple_choice' && !empty($card['choices']))
                        <ol class="choices-list" type="A">
                            @foreach($card['choices'] as $choice)
                                <li>{{ $choice }}</li>
                            @endforeach
                        </ol>
                    @elseif($card['card_type'] === 'true_false' && !empty($card['choices']))
                        <div class="choices-list">
                            @foreach($card['choices'] as $choice)
                                <span>{{ $choice }}</span>
                                @if(!$loop->last) / @endif
                            @endforeach
                        </div>
                    @elseif($card['card_type'] === 'cloze' && !empty($card['cloze_data']))
                        <div class="cloze-text">
                            {!! preg_replace('/\{\{[^}]*\}\}/', '<span class="cloze-blank"></span>', $card['cloze_data']['text']) !!}
                        </div>
                    @endif
                    
                    @if($card['hint'] && $options['include_hints'])
                        <div class="card-hint">
                            💡 Hint: {!! $card['hint'] !!}
                        </div>
                    @endif
                    
                    @if(!empty($card['tags']))
                        <div class="tags">
                            @foreach($card['tags'] as $tag)
                                <span class="tag">#{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
                
                <div class="fold-line"></div>
                
                {{-- Card Back (Bottom half, rotated for folding) --}}
                @if($options['include_answers'])
                    <div class="foldable-back">
                        <div class="card-answer">
                            <strong>Answer:</strong> {!! $card['answer'] !!}
                        </div>
                        
                        @if($card['card_type'] === 'multiple_choice' && !empty($card['correct_choices']))
                            <div class="correct-answers">
                                <strong>Correct:</strong>
                                @foreach($card['correct_choices'] as $correctIndex)
                                    <span class="correct-choice">
                                        {{ chr(65 + $correctIndex) }}: {{ $card['choices'][$correctIndex] ?? '' }}
                                    </span>
                                @endforeach
                            </div>
                        @elseif($card['card_type'] === 'cloze' && !empty($card['cloze_data']['answers']))
                            <div class="cloze-answers">
                                <strong>Cloze Answers:</strong>
                                @foreach($card['cloze_data']['answers'] as $answer)
                                    <span class="tag">{{ $answer }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    
    @if(!$loop->last)
        <div style="page-break-after: always;"></div>
    @endif
@endforeach