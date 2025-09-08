<div class="bg-white rounded-lg shadow-sm border">
    <div class="p-6">
        <h3 class="text-lg font-semibold mb-4">{{ __('Daily Capacity Analysis') }}</h3>
        
        @if(empty($analysis))
            <p class="text-gray-500 text-center py-4">{{ __('No capacity analysis available.') }}</p>
        @else
            <div class="space-y-4">
                @foreach($analysis as $day => $data)
                    <div class="border-l-4 @if($data['status'] === 'over') border-red-500 @elseif($data['status'] === 'warning') border-yellow-500 @else border-green-500 @endif pl-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-medium">{{ ucfirst($day) }}</h4>
                            <span class="text-sm @if($data['status'] === 'over') text-red-600 @elseif($data['status'] === 'warning') text-yellow-600 @else text-green-600 @endif">
                                {{ $data['total_minutes'] ?? 0 }} {{ __('minutes') }}
                            </span>
                        </div>
                        
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                            <div class="@if($data['status'] === 'over') bg-red-500 @elseif($data['status'] === 'warning') bg-yellow-500 @else bg-green-500 @endif h-2 rounded-full"
                                 style="width: {{ min(($data['total_minutes'] ?? 0) / ($data['recommended_max'] ?? 480) * 100, 100) }}%"></div>
                        </div>
                        
                        <div class="text-sm text-gray-600">
                            @if($data['status'] === 'over')
                                <span class="text-red-600">⚠️ {{ __('Over recommended capacity') }}</span>
                            @elseif($data['status'] === 'warning')
                                <span class="text-yellow-600">⚠️ {{ __('Near capacity limit') }}</span>
                            @else
                                <span class="text-green-600">✓ {{ __('Within healthy limits') }}</span>
                            @endif
                            
                            @if(isset($data['sessions_count']))
                                <span class="ml-2">{{ $data['sessions_count'] }} {{ __('sessions') }}</span>
                            @endif
                        </div>
                        
                        @if(!empty($data['recommendations']))
                            <div class="mt-2 text-sm">
                                @foreach($data['recommendations'] as $recommendation)
                                    <div class="text-blue-600">💡 {{ $recommendation }}</div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            
            @if(isset($weeklyTotal))
                <div class="mt-6 pt-4 border-t">
                    <div class="flex items-center justify-between">
                        <span class="font-medium">{{ __('Weekly Total') }}</span>
                        <span class="text-lg font-semibold @if($weeklyTotal['status'] === 'over') text-red-600 @elseif($weeklyTotal['status'] === 'warning') text-yellow-600 @else text-green-600 @endif">
                            {{ $weeklyTotal['minutes'] ?? 0 }} {{ __('minutes') }}
                        </span>
                    </div>
                    
                    @if(!empty($weeklyTotal['recommendation']))
                        <p class="text-sm text-blue-600 mt-2">💡 {{ $weeklyTotal['recommendation'] }}</p>
                    @endif
                </div>
            @endif
        @endif
    </div>
</div>