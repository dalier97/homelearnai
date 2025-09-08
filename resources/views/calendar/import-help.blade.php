@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold">{{ __('ICS Import Help') }}</h1>
                <p class="text-gray-600 mt-2">{{ __('Learn how to import calendar events') }}</p>
            </div>
            <div>
                <a href="{{ route('calendar.index') }}" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition-colors">
                    {{ __('Back to Calendar') }}
                </a>
            </div>
        </div>

        <div class="space-y-8">
            <!-- What is ICS Import -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-xl font-semibold mb-4">{{ __('What is ICS Import?') }}</h2>
                <p class="text-gray-600 mb-4">
                    {{ __('ICS (Internet Calendar Subscription) import allows you to bring in events from external calendars into your homeschool planning system. This is perfect for importing:') }}
                </p>
                <ul class="list-disc list-inside space-y-2 text-gray-600">
                    <li>{{ __('Classes and lessons from external providers') }}</li>
                    <li>{{ __('Field trips and activities') }}</li>
                    <li>{{ __('Co-op schedules') }}</li>
                    <li>{{ __('Sports and extracurricular activities') }}</li>
                    <li>{{ __('Doctor appointments and other fixed commitments') }}</li>
                </ul>
            </div>

            <!-- How to Get ICS Files -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-xl font-semibold mb-4">{{ __('How to Get ICS Files') }}</h2>
                <div class="space-y-4">
                    <div>
                        <h3 class="font-medium mb-2">{{ __('From Google Calendar:') }}</h3>
                        <ol class="list-decimal list-inside space-y-1 text-gray-600 text-sm">
                            <li>{{ __('Open Google Calendar') }}</li>
                            <li>{{ __('Click the three dots next to the calendar you want to export') }}</li>
                            <li>{{ __('Select "Settings and sharing"') }}</li>
                            <li>{{ __('Scroll to "Integrate calendar" section') }}</li>
                            <li>{{ __('Copy the "Public URL to this calendar" or use the secret address in iCal format') }}</li>
                        </ol>
                    </div>

                    <div>
                        <h3 class="font-medium mb-2">{{ __('From Outlook/Office 365:') }}</h3>
                        <ol class="list-decimal list-inside space-y-1 text-gray-600 text-sm">
                            <li>{{ __('Open Outlook Calendar') }}</li>
                            <li>{{ __('Click "File" > "Save Calendar"') }}</li>
                            <li>{{ __('Choose iCalendar format (.ics)') }}</li>
                            <li>{{ __('Save the file to your computer') }}</li>
                        </ol>
                    </div>

                    <div>
                        <h3 class="font-medium mb-2">{{ __('From Apple Calendar:') }}</h3>
                        <ol class="list-decimal list-inside space-y-1 text-gray-600 text-sm">
                            <li>{{ __('Open Calendar app') }}</li>
                            <li>{{ __('Select the calendar in the sidebar') }}</li>
                            <li>{{ __('File > Export > Export...') }}</li>
                            <li>{{ __('Save as .ics file') }}</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Import Process -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-xl font-semibold mb-4">{{ __('Import Process') }}</h2>
                <ol class="list-decimal list-inside space-y-2 text-gray-600">
                    <li>{{ __('Choose to import from a file or URL') }}</li>
                    <li>{{ __('Preview the events that will be imported') }}</li>
                    <li>{{ __('Review any scheduling conflicts') }}</li>
                    <li>{{ __('Confirm the import') }}</li>
                    <li>{{ __('Events are added as time blocks in your calendar') }}</li>
                </ol>
            </div>

            <!-- Tips and Best Practices -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-xl font-semibold mb-4">{{ __('Tips & Best Practices') }}</h2>
                <ul class="list-disc list-inside space-y-2 text-gray-600">
                    <li>{{ __('Import events for one child at a time for better organization') }}</li>
                    <li>{{ __('Review conflicts carefully - the system will warn you about scheduling overlaps') }}</li>
                    <li>{{ __('Use URL imports for calendars that update frequently') }}</li>
                    <li>{{ __('File imports are better for one-time event imports') }}</li>
                    <li>{{ __('Recurring events are automatically expanded for the next 6 months') }}</li>
                    <li>{{ __('Imported events are marked as "fixed" commitments by default') }}</li>
                </ul>
            </div>

            <!-- Troubleshooting -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-xl font-semibold mb-4">{{ __('Troubleshooting') }}</h2>
                <div class="space-y-3">
                    <div>
                        <h3 class="font-medium text-red-600">{{ __('No events found in file') }}</h3>
                        <p class="text-sm text-gray-600">{{ __('Make sure the file is in .ics format and contains calendar events. Some export options only include empty calendar structure.') }}</p>
                    </div>
                    
                    <div>
                        <h3 class="font-medium text-red-600">{{ __('URL import failed') }}</h3>
                        <p class="text-sm text-gray-600">{{ __('Check that the URL is public and accessible. Private calendar URLs may require authentication.') }}</p>
                    </div>
                    
                    <div>
                        <h3 class="font-medium text-red-600">{{ __('Import partially failed') }}</h3>
                        <p class="text-sm text-gray-600">{{ __('Some events may have formatting issues. Check the results page for details on which events were skipped.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection