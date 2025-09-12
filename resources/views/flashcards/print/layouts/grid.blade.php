{{-- Grid Layout (6 cards per page) --}}
@foreach($cards as $pageCards)
    <div class="grid-container">
        @foreach($pageCards as $card)
            <div class="grid-card">
                <div class="grid-card-header">
                    <span class="card-type-badge">{{ ucfirst(str_replace('_', ' ', $card['card_type'])) }}</span>
                    <span class="difficulty-badge difficulty-{{ $card['difficulty'] }}">{{ ucfirst($card['difficulty']) }}</span>
                </div>
                
                <div class="card-question">
                    <strong>Q:</strong> {!! $card['question'] !!}
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
                
                @if($options['include_answers'])
                    <div class="card-answer">
                        <strong>A:</strong> {!! $card['answer'] !!}
                    </div>
                    
                    @if($card['card_type'] === 'cloze' && !empty($card['cloze_data']['answers']))
                        <div class="cloze-answers">
                            <strong>Fills:</strong>
                            @foreach($card['cloze_data']['answers'] as $answer)
                                <span class="tag">{{ $answer }}</span>
                            @endforeach
                        </div>
                    @endif
                @endif
                
                @if($card['hint'] && $options['include_hints'])
                    <div class="card-hint">
                        💡 {!! $card['hint'] !!}
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
        @endforeach
    </div>
    
    @if(!$loop->last)
        <div style="page-break-after: always;"></div>
    @endif
@endforeach