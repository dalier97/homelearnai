{{-- Traditional Index Cards Layout (3x5 or 4x6) --}}
@foreach($cards as $card)
    <div class="index-card">
        {{-- Card Front --}}
        <div class="index-card-front">
            <div class="card-type-badge">{{ $card['card_type'] }}</div>
            <div class="difficulty-badge difficulty-{{ $card['difficulty'] }}">{{ ucfirst($card['difficulty']) }}</div>
            
            <div class="card-question">
                Q: {!! $card['question'] !!}
            </div>
            
            @if($card['card_type'] === 'multiple_choice' && !empty($card['choices']))
                <ol class="choices-list" type="A">
                    @foreach($card['choices'] as $index => $choice)
                        <li class="{{ in_array($index, $card['correct_choices']) ? 'correct-choice' : '' }}">
                            {{ $choice }}
                        </li>
                    @endforeach
                </ol>
            @elseif($card['card_type'] === 'true_false' && !empty($card['choices']))
                <div class="choices-list">
                    @foreach($card['choices'] as $index => $choice)
                        <span class="{{ in_array($index, $card['correct_choices']) ? 'correct-choice' : '' }}">
                            {{ $choice }}
                        </span>
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
        
        <div class="cut-line"></div>
        
        {{-- Card Back --}}
        @if($options['include_answers'])
            <div class="index-card-back">
                <div class="card-answer">
                    <strong>A:</strong> {!! $card['answer'] !!}
                </div>
                
                @if($card['card_type'] === 'cloze' && !empty($card['cloze_data']['answers']))
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
    
    {{-- Start new page every 4 cards --}}
    @if($loop->iteration % 4 === 0 && !$loop->last)
        <div style="page-break-after: always;"></div>
    @endif
@endforeach