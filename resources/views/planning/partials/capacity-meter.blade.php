<!-- Weekly Capacity Meter -->
<div class="bg-white rounded-lg border border-gray-200 p-6 mb-8">
  <div class="flex items-center justify-between mb-4">
    <h3 class="text-lg font-medium text-gray-900">Weekly Capacity Overview</h3>
    <span class="text-sm text-gray-500">for {{ $child->name }}</span>
  </div>

  <div class="grid grid-cols-7 gap-4">
    @foreach($capacityData as $dayData)
      @php
        $day = $dayData['day'];
        $dayName = $dayData['day_name'];
        $availableMinutes = $dayData['available_minutes'];
        $scheduledMinutes = $dayData['scheduled_minutes'];
        $remainingMinutes = $dayData['remaining_minutes'];
        $utilizationPercent = $dayData['utilization_percent'];
        $status = $dayData['status'];
        $timeBlocksCount = $dayData['time_blocks_count'];
        $sessionsCount = $dayData['sessions_count'];
        
        $statusColor = match($status) {
          'green' => 'bg-green-500',
          'yellow' => 'bg-yellow-500', 
          'red' => 'bg-red-500',
          default => 'bg-gray-300'
        };
      @endphp
      
      <div class="text-center">
        <!-- Day Name -->
        <div class="text-sm font-medium text-gray-900 mb-2">{{ substr($dayName, 0, 3) }}</div>
        
        <!-- Capacity Bar -->
        <div class="relative bg-gray-200 rounded-full h-4 mb-2 overflow-hidden">
          @if($availableMinutes > 0)
            <div 
              class="capacity-bar h-full {{ $statusColor }} transition-all duration-300" 
              style="width: {{ min(100, $utilizationPercent) }}%"
              title="{{ $utilizationPercent }}% utilized"
            ></div>
          @endif
        </div>
        
        <!-- Minutes Info -->
        <div class="text-xs text-gray-600 space-y-1">
          @if($availableMinutes > 0)
            <div>{{ $scheduledMinutes }}m / {{ $availableMinutes }}m</div>
            <div class="text-xs {{ $status === 'green' ? 'text-green-600' : ($status === 'yellow' ? 'text-yellow-600' : 'text-red-600') }}">
              {{ $remainingMinutes }}m left
            </div>
          @else
            <div class="text-gray-400">No time blocks</div>
          @endif
        </div>
        
        <!-- Blocks & Sessions Count -->
        <div class="text-xs text-gray-500 mt-1">
          {{ $timeBlocksCount }} blocks, {{ $sessionsCount }} sessions
        </div>
        
        <!-- Status Indicator -->
        <div class="mt-2">
          <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full 
                     {{ $status === 'green' ? 'bg-green-100 text-green-800' : 
                        ($status === 'yellow' ? 'bg-yellow-100 text-yellow-800' : 
                         ($status === 'red' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
            @if($availableMinutes === 0)
              No schedule
            @elseif($utilizationPercent >= 90)
              Overloaded
            @elseif($utilizationPercent >= 75)
              High load
            @elseif($utilizationPercent > 0)
              Available
            @else
              Open
            @endif
          </span>
        </div>
      </div>
    @endforeach
  </div>

  <!-- Summary Stats -->
  @php
    $totalAvailable = collect($capacityData)->sum('available_minutes');
    $totalScheduled = collect($capacityData)->sum('scheduled_minutes');
    $totalRemaining = collect($capacityData)->sum('remaining_minutes');
    $weeklyUtilization = $totalAvailable > 0 ? ($totalScheduled / $totalAvailable) * 100 : 0;
    
    $totalTimeBlocks = collect($capacityData)->sum('time_blocks_count');
    $totalSessions = collect($capacityData)->sum('sessions_count');
    
    $overloadedDays = collect($capacityData)->where('status', 'red')->count();
    $highLoadDays = collect($capacityData)->where('status', 'yellow')->count();
  @endphp

  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-4 border-t border-gray-200">
    <div class="text-center">
      <div class="text-2xl font-bold text-blue-600">{{ number_format($weeklyUtilization, 1) }}%</div>
      <div class="text-xs text-gray-600">Weekly Utilization</div>
    </div>
    <div class="text-center">
      <div class="text-2xl font-bold text-green-600">{{ $totalRemaining }}m</div>
      <div class="text-xs text-gray-600">Available Time</div>
    </div>
    <div class="text-center">
      <div class="text-2xl font-bold text-purple-600">{{ $totalSessions }}</div>
      <div class="text-xs text-gray-600">Scheduled Sessions</div>
    </div>
    <div class="text-center">
      <div class="text-2xl font-bold {{ $overloadedDays > 0 ? 'text-red-600' : 'text-gray-600' }}">{{ $overloadedDays }}</div>
      <div class="text-xs text-gray-600">Overloaded Days</div>
    </div>
  </div>

  @if($overloadedDays > 0 || $highLoadDays > 0)
  <!-- Capacity Warnings -->
  <div class="mt-4 p-3 {{ $overloadedDays > 0 ? 'bg-red-50 border-red-200' : 'bg-yellow-50 border-yellow-200' }} border rounded-md">
    <div class="flex items-start">
      <svg class="w-5 h-5 {{ $overloadedDays > 0 ? 'text-red-400' : 'text-yellow-400' }} mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
      </svg>
      <div class="flex-1">
        <h4 class="text-sm font-medium {{ $overloadedDays > 0 ? 'text-red-800' : 'text-yellow-800' }}">
          Capacity {{ $overloadedDays > 0 ? 'Exceeded' : 'Warning' }}
        </h4>
        <p class="mt-1 text-sm {{ $overloadedDays > 0 ? 'text-red-700' : 'text-yellow-700' }}">
          @if($overloadedDays > 0)
            {{ $overloadedDays }} day(s) are overloaded. Consider rescheduling some sessions or adding more time blocks.
          @else
            {{ $highLoadDays }} day(s) have high utilization (75%+). Monitor capacity closely.
          @endif
        </p>
      </div>
    </div>
  </div>
  @endif
</div>