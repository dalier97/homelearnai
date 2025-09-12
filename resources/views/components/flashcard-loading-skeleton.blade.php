{{-- Flashcard Loading Skeleton Component --}}
@props(['count' => 3])

<div class="space-y-4 animate-pulse">
    @for($i = 0; $i < $count; $i++)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-2">
                        {{-- Question placeholder --}}
                        <div class="h-4 bg-gray-200 rounded w-2/3"></div>
                        
                        {{-- Card type badge placeholder --}}
                        <div class="h-5 bg-gray-200 rounded-full w-20"></div>
                        
                        {{-- Difficulty badge placeholder --}}
                        <div class="h-5 bg-gray-200 rounded-full w-16"></div>
                    </div>
                    
                    <div class="space-y-2">
                        {{-- Answer placeholder --}}
                        <div class="flex items-center">
                            <div class="w-4 h-4 bg-gray-200 rounded mr-2"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                        </div>
                        
                        {{-- Hint placeholder (random) --}}
                        @if($i % 2 === 0)
                            <div class="flex items-center">
                                <div class="w-4 h-4 bg-gray-200 rounded mr-2"></div>
                                <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                            </div>
                        @endif
                        
                        {{-- Tags placeholder --}}
                        @if($i % 3 === 0)
                            <div class="flex items-center space-x-1 mt-2">
                                <div class="w-4 h-4 bg-gray-200 rounded mr-2"></div>
                                <div class="h-4 bg-gray-200 rounded w-12"></div>
                                <div class="h-4 bg-gray-200 rounded w-16"></div>
                                <div class="h-4 bg-gray-200 rounded w-14"></div>
                            </div>
                        @endif
                    </div>
                </div>
                
                {{-- Action buttons placeholder --}}
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-gray-200 rounded"></div>
                    <div class="w-8 h-8 bg-gray-200 rounded"></div>
                </div>
            </div>
        </div>
    @endfor
</div>