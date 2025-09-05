<!-- Skip Day Modal -->
<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" id="skip-day-modal">
  <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 relative z-10">
    <!-- Modal Header -->
    <div class="flex items-center justify-between p-6 border-b border-gray-200">
      <h3 class="text-lg font-semibold text-gray-900">{{ __('skip_session_day') }}</h3>
      <button onclick="closeSkipDayModal()" 
              class="text-gray-400 hover:text-gray-600 transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Modal Body -->
    <div class="p-6">
      <form id="skip-day-form">
        <div class="mb-4">
          <label for="skip_date" class="block text-sm font-medium text-gray-700 mb-2">
            {{ __('date_to_skip') }}
          </label>
          <input type="date" 
                 id="skip_date" 
                 name="skip_date" 
                 class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                 required>
        </div>
        
        <div class="mb-6">
          <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
            {{ __('reason_optional') }}
          </label>
          <textarea id="reason" 
                    name="reason" 
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="{{ __('why_are_you_skipping_this_session') }}"></textarea>
        </div>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
          <div class="flex items-start">
            <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <div class="text-sm text-yellow-800">
              <p class="font-medium mb-1">{{ __('what_happens_when_you_skip') }}</p>
              <ul class="text-xs space-y-1">
                <li>• {{ __('session_moves_to_catch_up_lane') }}</li>
                <li>• {{ __('priority_set_based_on_commitment_type') }}</li>
                <li>• {{ __('auto_reschedule_suggestions_will_be_provided') }}</li>
                <li>• {{ __('you_can_manually_reschedule_later') }}</li>
              </ul>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- Modal Footer -->
    <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200 bg-gray-50">
      <button onclick="closeSkipDayModal()" 
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
        {{ __('cancel') }}
      </button>
      
      <button onclick="submitSkipDay()" 
              class="px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg transition-colors">
        {{ __('skip_day') }}
      </button>
    </div>
  </div>
</div>

<script>
let currentSkipSessionId = {{ $session->id }};

function openSkipDayModal(sessionId, defaultDate) {
  currentSkipSessionId = sessionId || {{ $session->id }};
  const skipDateInput = document.getElementById('skip_date');
  if (skipDateInput && defaultDate && defaultDate !== 'today') {
    skipDateInput.value = defaultDate;
  } else if (skipDateInput) {
    skipDateInput.value = new Date().toISOString().split('T')[0];
  }
  // Modal is already in DOM, just show it
}

// Initialize the modal with the correct session ID when loaded
document.addEventListener('DOMContentLoaded', function() {
  currentSkipSessionId = {{ $session->id }};
  const defaultDate = '{{ $defaultDate }}';
  const skipDateInput = document.getElementById('skip_date');
  if (skipDateInput && defaultDate && defaultDate !== 'today') {
    skipDateInput.value = defaultDate;
  } else if (skipDateInput) {
    skipDateInput.value = new Date().toISOString().split('T')[0];
  }
});

function closeSkipDayModal() {
  document.getElementById('skip-day-modal').remove();
  currentSkipSessionId = null;
}

function submitSkipDay() {
  if (!currentSkipSessionId) return;
  
  const skipDate = document.getElementById('skip_date').value;
  const reason = document.getElementById('reason').value;
  
  if (!skipDate) {
    alert(window.__('Please select a date to skip.'));
    return;
  }
  
  // Use HTMX to submit the skip day request
  htmx.ajax('POST', `/planning/sessions/${currentSkipSessionId}/skip-day`, {
    values: {
      skip_date: skipDate,
      reason: reason
    },
    target: '#planning-board',
    swap: 'innerHTML'
  });
  
  closeSkipDayModal();
}

// Close modal on background click
document.getElementById('skip-day-modal')?.addEventListener('click', function(e) {
  if (e.target === this) {
    closeSkipDayModal();
  }
});
</script>