<div class="bg-white rounded-lg shadow-sm border">
    <div class="p-6">
        <h3 class="text-lg font-semibold mb-4">{{ __('Quality Heuristics Analysis') }}</h3>
        
        @if(empty($analysis))
            <p class="text-gray-500 text-center py-4">{{ __('No quality analysis available.') }}</p>
        @else
            <div class="space-y-4">
                @foreach($analysis as $category => $data)
                    <div class="border rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-medium capitalize">{{ str_replace('_', ' ', $category) }}</h4>
                            <span class="px-2 py-1 rounded-full text-xs @if($data['status'] === 'excellent') bg-green-100 text-green-800 @elseif($data['status'] === 'good') bg-blue-100 text-blue-800 @elseif($data['status'] === 'warning') bg-yellow-100 text-yellow-800 @else bg-red-100 text-red-800 @endif">
                                {{ ucfirst($data['status'] ?? 'unknown') }}
                            </span>
                        </div>
                        
                        @if(!empty($data['score']))
                            <div class="w-full bg-gray-200 rounded-full h-2 mb-3">
                                <div class="@if($data['score'] >= 80) bg-green-500 @elseif($data['score'] >= 60) bg-blue-500 @elseif($data['score'] >= 40) bg-yellow-500 @else bg-red-500 @endif h-2 rounded-full"
                                     style="width: {{ $data['score'] }}%"></div>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">{{ __('Score') }}: {{ $data['score'] }}%</p>
                        @endif
                        
                        @if(!empty($data['description']))
                            <p class="text-sm text-gray-700 mb-3">{{ $data['description'] }}</p>
                        @endif
                        
                        @if(!empty($data['issues']) && count($data['issues']) > 0)
                            <div class="mb-3">
                                <h5 class="text-sm font-medium text-red-700 mb-2">{{ __('Issues Found:') }}</h5>
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach($data['issues'] as $issue)
                                        <li class="text-sm text-red-600">{{ $issue }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        @if(!empty($data['recommendations']) && count($data['recommendations']) > 0)
                            <div>
                                <h5 class="text-sm font-medium text-blue-700 mb-2">{{ __('Recommendations:') }}</h5>
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach($data['recommendations'] as $recommendation)
                                        <li class="text-sm text-blue-600">{{ $recommendation }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            
            @if(isset($overallScore))
                <div class="mt-6 pt-4 border-t">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-medium text-lg">{{ __('Overall Quality Score') }}</span>
                        <span class="text-2xl font-bold @if($overallScore >= 80) text-green-600 @elseif($overallScore >= 60) text-blue-600 @elseif($overallScore >= 40) text-yellow-600 @else text-red-600 @endif">
                            {{ $overallScore }}%
                        </span>
                    </div>
                    
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="@if($overallScore >= 80) bg-green-500 @elseif($overallScore >= 60) bg-blue-500 @elseif($overallScore >= 40) bg-yellow-500 @else bg-red-500 @endif h-3 rounded-full transition-all"
                             style="width: {{ $overallScore }}%"></div>
                    </div>
                    
                    <p class="text-sm text-gray-600 mt-2">
                        @if($overallScore >= 80)
                            {{ __('Excellent planning quality!') }}
                        @elseif($overallScore >= 60)
                            {{ __('Good planning with room for improvement.') }}
                        @elseif($overallScore >= 40)
                            {{ __('Planning needs some attention.') }}
                        @else
                            {{ __('Consider reviewing and adjusting your schedule.') }}
                        @endif
                    </p>
                </div>
            @endif
        @endif
    </div>
</div>