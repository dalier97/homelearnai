<?php

namespace App\Services;

use App\Models\CatchUpSession;
use App\Models\Child;
use App\Models\Session;
use Carbon\Carbon;

class SchedulingEngine
{
    public function __construct(
        private SupabaseClient $supabase
    ) {}

    /**
     * Skip a session for a specific day and create catch-up suggestions
     */
    public function skipSessionDay(Session $session, Carbon $originalDate, ?string $reason = null): array
    {
        // Create catch-up session
        $catchUpSession = $session->skipDay($originalDate, $reason, $this->supabase);

        // Get suggestions for redistributing the session
        $suggestions = $this->generateRescheduleSuggestions($session, $originalDate);

        return [
            'catch_up_session' => $catchUpSession,
            'reschedule_suggestions' => $suggestions,
            'auto_reschedule_applied' => false,
        ];
    }

    /**
     * Generate suggestions for rescheduling a session
     */
    public function generateRescheduleSuggestions(Session $session, Carbon $originalDate): array
    {
        $child = $session->child($this->supabase);
        if (! $child) {
            return [];
        }

        $suggestions = [];
        $sessionDuration = $session->estimated_minutes;

        // Find available slots in the next 2 weeks
        $startDate = $originalDate->copy()->addDay();
        $endDate = $originalDate->copy()->addWeeks(2);

        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $dayOfWeek = $date->dayOfWeekIso; // 1=Monday, 7=Sunday

            // Get existing sessions for this day
            $existingSessions = Session::forChildAndDay($child->id, $dayOfWeek, $this->supabase);

            // Calculate available capacity
            $totalScheduledMinutes = $existingSessions->sum('estimated_minutes');
            $availableSlots = $this->findAvailableTimeSlots(
                $child,
                $dayOfWeek,
                $sessionDuration,
                $totalScheduledMinutes
            );

            foreach ($availableSlots as $slot) {
                $suggestions[] = [
                    'date' => $date->format('Y-m-d'),
                    'day_name' => $date->format('l'),
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'capacity_used' => $slot['capacity_percentage'],
                    'difficulty' => $this->calculateRescheduleDifficulty($session, $date, $slot),
                    'recommended' => $slot['capacity_percentage'] < 80, // Don't overload days
                ];
            }
        }

        // Sort by difficulty (easiest first) and limit to top 10
        usort($suggestions, fn ($a, $b) => $a['difficulty'] <=> $b['difficulty']);

        return array_slice($suggestions, 0, 10);
    }

    /**
     * Find available time slots for a specific day
     */
    private function findAvailableTimeSlots(Child $child, int $dayOfWeek, int $durationMinutes, int $existingMinutes): array
    {
        // For now, we'll create simple suggestions based on time blocks
        // In a real implementation, this would be more sophisticated

        $slots = [];

        // Typical school hours: 9 AM to 3 PM (360 minutes total)
        $schoolDayCapacity = 360;
        $remainingCapacity = $schoolDayCapacity - $existingMinutes;

        if ($remainingCapacity >= $durationMinutes) {
            $capacityPercentage = (($existingMinutes + $durationMinutes) / $schoolDayCapacity) * 100;

            // Suggest common time slots
            $commonSlots = [
                ['start' => '09:00', 'end' => '09:30'],
                ['start' => '10:00', 'end' => '10:30'],
                ['start' => '11:00', 'end' => '11:30'],
                ['start' => '13:00', 'end' => '13:30'],
                ['start' => '14:00', 'end' => '14:30'],
                ['start' => '15:00', 'end' => '15:30'],
            ];

            foreach ($commonSlots as $slot) {
                $startTime = Carbon::createFromFormat('H:i', $slot['start']);
                $endTime = Carbon::createFromFormat('H:i', $slot['end']);
                $slotDuration = $endTime->diffInMinutes($startTime);

                if ($slotDuration >= $durationMinutes) {
                    $slots[] = [
                        'start_time' => $slot['start'].':00',
                        'end_time' => $startTime->addMinutes($durationMinutes)->format('H:i:s'),
                        'capacity_percentage' => $capacityPercentage,
                    ];
                }
            }
        }

        return $slots;
    }

    /**
     * Calculate difficulty of rescheduling to a specific slot
     */
    private function calculateRescheduleDifficulty(Session $session, Carbon $date, array $slot): int
    {
        $difficulty = 0;

        // Base difficulty based on how far in the future
        $daysFromOriginal = $date->diffInDays(Carbon::now());
        $difficulty += $daysFromOriginal; // Each day adds 1 point

        // Difficulty based on commitment type
        switch ($session->commitment_type) {
            case 'fixed':
                $difficulty += 10; // Very hard to reschedule
                break;
            case 'preferred':
                $difficulty += 3; // Moderate difficulty
                break;
            case 'flexible':
                $difficulty += 1; // Easy to reschedule
                break;
        }

        // Difficulty based on capacity
        if ($slot['capacity_percentage'] > 90) {
            $difficulty += 5; // Very busy day
        } elseif ($slot['capacity_percentage'] > 70) {
            $difficulty += 2; // Busy day
        }

        return (int) $difficulty;
    }

    /**
     * Auto-reschedule flexible sessions when needed
     */
    public function autoRescheduleFlexibleSessions(Child $child, Carbon $fromDate, array $sessionIds = []): array
    {
        $rescheduled = [];

        // Get sessions to reschedule
        if (empty($sessionIds)) {
            $sessions = Session::forChild($child->id, $this->supabase)
                ->where('commitment_type', 'flexible')
                ->where('status', 'scheduled');
        } else {
            // Fetch sessions by IDs individually
            $sessions = collect();
            foreach ($sessionIds as $sessionId) {
                $session = Session::find((string) $sessionId, $this->supabase);
                if ($session) {
                    $sessions->push($session);
                }
            }
        }

        foreach ($sessions as $session) {
            if (! $session->canBeRescheduled()) {
                continue;
            }

            $suggestions = $this->generateRescheduleSuggestions($session, $fromDate);

            // Auto-apply the best suggestion if it's easy enough
            $bestSuggestion = $suggestions[0] ?? null;
            if ($bestSuggestion && $bestSuggestion['difficulty'] <= 5 && $bestSuggestion['recommended']) {
                $session->scheduleToTimeSlot(
                    $bestSuggestion['day_of_week'],
                    $bestSuggestion['start_time'],
                    $bestSuggestion['end_time'],
                    $this->supabase,
                    Carbon::parse($bestSuggestion['date'])
                );

                $rescheduled[] = [
                    'session' => $session,
                    'new_date' => $bestSuggestion['date'],
                    'new_time' => $bestSuggestion['start_time'].' - '.$bestSuggestion['end_time'],
                ];
            }
        }

        return $rescheduled;
    }

    /**
     * Redistribute catch-up sessions across available slots
     */
    public function redistributeCatchUpSessions(Child $child, int $maxSessions = 5): array
    {
        // Get pending catch-up sessions, prioritized
        $catchUpSessions = CatchUpSession::pending($child->id, $this->supabase);
        $redistributed = [];

        // Process high-priority items first
        $prioritized = $catchUpSessions->sortBy('priority')->take($maxSessions);

        foreach ($prioritized as $catchUpSession) {
            $originalSession = $catchUpSession->originalSession($this->supabase);
            if (! $originalSession) {
                continue;
            }

            // Find best available slot
            $suggestions = $this->generateRescheduleSuggestions(
                $originalSession,
                $catchUpSession->missed_date
            );

            $bestSuggestion = $suggestions[0] ?? null;
            if ($bestSuggestion && $bestSuggestion['recommended']) {
                // Create new session for this catch-up
                $newSession = new Session([
                    'topic_id' => $catchUpSession->topic_id,
                    'child_id' => $catchUpSession->child_id,
                    'estimated_minutes' => $catchUpSession->estimated_minutes,
                    'status' => 'scheduled',
                    'commitment_type' => 'flexible', // Catch-up sessions are flexible by default
                ]);

                $newSession->scheduleToTimeSlot(
                    $bestSuggestion['day_of_week'],
                    $bestSuggestion['start_time'],
                    $bestSuggestion['end_time'],
                    $this->supabase,
                    Carbon::parse($bestSuggestion['date'])
                );

                // Mark catch-up as reassigned
                $catchUpSession->reassignToSession($newSession->id, $this->supabase);

                $redistributed[] = [
                    'catch_up_session' => $catchUpSession,
                    'new_session' => $newSession,
                    'scheduled_date' => $bestSuggestion['date'],
                    'scheduled_time' => $bestSuggestion['start_time'].' - '.$bestSuggestion['end_time'],
                ];
            }
        }

        return $redistributed;
    }

    /**
     * Get capacity analysis for a child's schedule
     */
    public function analyzeCapacity(Child $child): array
    {
        $analysis = [];

        // Analyze each day of the week
        for ($dayOfWeek = 1; $dayOfWeek <= 7; $dayOfWeek++) {
            $sessions = Session::forChildAndDay($child->id, $dayOfWeek, $this->supabase);
            $totalMinutes = $sessions->sum('estimated_minutes');
            $sessionCount = $sessions->count();

            // Calculate capacity metrics
            $maxCapacity = 360; // 6 hours typical school day
            $utilizationPercent = ($totalMinutes / $maxCapacity) * 100;

            $analysis[$dayOfWeek] = [
                'day_name' => $this->getDayName($dayOfWeek),
                'session_count' => $sessionCount,
                'total_minutes' => $totalMinutes,
                'total_hours' => round($totalMinutes / 60, 1),
                'utilization_percent' => round($utilizationPercent, 1),
                'available_minutes' => max(0, $maxCapacity - $totalMinutes),
                'status' => $this->getCapacityStatus($utilizationPercent),
                'can_add_session' => $utilizationPercent < 90,
            ];
        }

        return $analysis;
    }

    /**
     * Get day name from day of week number
     */
    private function getDayName(int $dayOfWeek): string
    {
        $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
            5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

        return $days[$dayOfWeek] ?? 'Unknown';
    }

    /**
     * Get capacity status label
     */
    private function getCapacityStatus(float $utilizationPercent): string
    {
        if ($utilizationPercent >= 90) {
            return 'overloaded';
        }
        if ($utilizationPercent >= 70) {
            return 'busy';
        }
        if ($utilizationPercent >= 40) {
            return 'moderate';
        }

        return 'light';
    }

    /**
     * Suggest optimal scheduling patterns
     */
    public function suggestOptimalScheduling(Child $child): array
    {
        $capacity = $this->analyzeCapacity($child);
        $suggestions = [];

        // Find overloaded days
        $overloadedDays = array_filter($capacity, fn ($day) => $day['status'] === 'overloaded');

        // Find underutilized days
        $lightDays = array_filter($capacity, fn ($day) => $day['status'] === 'light');

        if (! empty($overloadedDays) && ! empty($lightDays)) {
            $suggestions[] = [
                'type' => 'balance_load',
                'message' => 'Consider moving some sessions from busy days to lighter days',
                'overloaded_days' => array_keys($overloadedDays),
                'available_days' => array_keys($lightDays),
            ];
        }

        // Check for gaps in the schedule
        $activeDays = array_filter($capacity, fn ($day) => $day['session_count'] > 0);
        if (count($activeDays) < 5) {
            $suggestions[] = [
                'type' => 'utilize_more_days',
                'message' => 'Consider spreading sessions across more days for better balance',
                'unused_days' => array_keys(array_filter($capacity, fn ($day) => $day['session_count'] === 0)),
            ];
        }

        return $suggestions;
    }
}
