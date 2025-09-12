{{-- Study Sheet Layout (List format) --}}
<div class="study-sheet">
    <h1 style="text-align: center; margin-bottom: 1in; border-bottom: 2px solid #000; padding-bottom: 0.25in;">
        Flashcard Study Sheet
    </h1>
    
    @foreach($cards as $index => $card)
        <div class="study-item">
            <div class="study-meta">
                <strong>Card {{ $index + 1 }}</strong>
                <span class="card-type-badge">{{ ucfirst(str_replace('_', ' ', $card['card_type'])) }}</span>
                <span class="difficulty-badge difficulty-{{ $card['difficulty'] }}">{{ ucfirst($card['difficulty']) }}</span>
                
                @if(!empty($card['tags']))
                    <span class="tags">
                        @foreach($card['tags'] as $tag)
                            <span class="tag">#{{ $tag }}</span>
                        @endforeach
                    </span>
                @endif
            </div>
            
            <div class="study-question">
                <strong>Question:</strong> {!! $card['question'] !!}
            </div>
            
            @if($card['card_type'] === 'multiple_choice' && !empty($card['choices']))
                <div class="study-answer">
                    <strong>Choices:</strong>
                    <ol type="A" style="margin: 4px 0 8px 20px;">
                        @foreach($card['choices'] as $index => $choice)
                            <li class="{{ in_array($index, $card['correct_choices']) ? 'correct-choice' : '' }}" style="margin: 2px 0;">
                                {{ $choice }}
                                @if(in_array($index, $card['correct_choices'])) <strong>✓ CORRECT</strong> @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            @elseif($card['card_type'] === 'true_false' && !empty($card['choices']))
                <div class="study-answer">
                    <strong>Options:</strong>
                    @foreach($card['choices'] as $index => $choice)
                        <span class="{{ in_array($index, $card['correct_choices']) ? 'correct-choice' : '' }}">
                            {{ $choice }}
                            @if(in_array($index, $card['correct_choices'])) <strong>✓ CORRECT</strong> @endif
                        </span>
                        @if(!$loop->last) / @endif
                    @endforeach
                </div>
            @elseif($card['card_type'] === 'cloze' && !empty($card['cloze_data']))
                <div class="study-answer">
                    <strong>Cloze Text:</strong>
                    <div class="cloze-text" style="margin: 4px 0 8px 20px;">
                        {!! preg_replace('/\{\{[^}]*\}\}/', '<span class="cloze-blank"></span>', $card['cloze_data']['text']) !!}
                    </div>
                    @if(!empty($card['cloze_data']['answers']))
                        <strong>Fill-in Answers:</strong>
                        @foreach($card['cloze_data']['answers'] as $answer)
                            <span class="tag">{{ $answer }}</span>
                        @endforeach
                    @endif
                </div>
            @endif
            
            @if($options['include_answers'] && $card['card_type'] !== 'multiple_choice' && $card['card_type'] !== 'true_false')
                <div class="study-answer">
                    <strong>Answer:</strong> {!! $card['answer'] !!}
                </div>
            @endif
            
            @if($card['hint'] && $options['include_hints'])
                <div class="study-answer card-hint">
                    <strong>💡 Hint:</strong> {!! $card['hint'] !!}
                </div>
            @endif
            
            {{-- Add some blank space for notes --}}
            <div style="margin-top: 0.25in; border-top: 1px dotted #ccc; padding-top: 0.125in;">
                <strong>Notes:</strong>
                <div style="height: 0.5in; border-bottom: 1px solid #ddd; margin: 4px 0;"></div>
                <div style="height: 0.5in; border-bottom: 1px solid #ddd; margin: 4px 0;"></div>
            </div>
        </div>
        
        {{-- Page break every 3-4 items depending on content --}}
        @if($index > 0 && ($index + 1) % 3 === 0 && !$loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach
</div>