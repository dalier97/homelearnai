@extends('layouts.app') @section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
  <div class="max-w-md w-full space-y-8">
    <div class="text-center">
      <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Confirming Email...</h2>
      <p class="mt-2 text-sm text-gray-600">Please wait while we confirm your email address.</p>
    </div>

    <div id="message" class="hidden">
      <!-- Message will be inserted here -->
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
      // Get the hash fragment from the URL
      const hashParams = new URLSearchParams(window.location.hash.substring(1));
      const accessToken = hashParams.get('access_token');
      const tokenType = hashParams.get('token_type');
      const type = hashParams.get('type');

      const messageDiv = document.getElementById('message');

      if (accessToken && type === 'signup') {
          // Email confirmed successfully
          messageDiv.innerHTML = `
              <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative">
                  <p class="font-bold">Email Confirmed!</p>
                  <p>Your email has been confirmed successfully. You can now log in.</p>
              </div>
          `;
          messageDiv.classList.remove('hidden');

          // Redirect to login after 3 seconds
          setTimeout(() => {
              window.location.href = '{{ route('login') }}?confirmed=true';
          }, 3000);
      } else if (type === 'recovery') {
          // Password recovery flow
          messageDiv.innerHTML = `
              <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded relative">
                  <p>Password recovery link detected. Redirecting...</p>
              </div>
          `;
          messageDiv.classList.remove('hidden');
          // TODO: Handle password recovery
      } else {
          // Invalid or missing parameters
          messageDiv.innerHTML = `
              <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative">
                  <p>Invalid confirmation link. Please try registering again.</p>
              </div>
          `;
          messageDiv.classList.remove('hidden');

          setTimeout(() => {
              window.location.href = '{{ route('register') }}';
          }, 3000);
      }
  });
</script>
@endsection
