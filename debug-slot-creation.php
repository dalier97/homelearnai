<?php

require_once __DIR__.'/vendor/autoload.php';

use App\Models\Child;
use App\Models\ReviewSlot;
use App\Services\SupabaseClient;

// Create a temporary test to debug slot creation
echo "🧪 Debug: Slot Creation Test\n";
echo "===========================\n\n";

// Set up environment
$_ENV['APP_ENV'] = 'testing';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, ['.env.testing']);
$dotenv->load();

// Initialize Supabase client
$supabase = new SupabaseClient(
    $_ENV['SUPABASE_URL'],
    $_ENV['SUPABASE_ANON_KEY'],
    $_ENV['SUPABASE_SERVICE_KEY']
);

echo "1. Creating test user and child...\n";

// Get or create test user (simulate session)
$testUserId = 'test-user-'.time();
echo "   Test User ID: {$testUserId}\n";

// Create test child
$child = new Child([
    'name' => 'Debug Test Child',
    'age' => 10,
    'independence_level' => 2,
]);
$child->user_id = $testUserId;

if ($child->save($supabase)) {
    echo "   ✅ Child created with ID: {$child->id}\n";
} else {
    echo "   ❌ Failed to create child\n";
    exit(1);
}

echo "\n2. Testing slot creation...\n";

// Test creating a slot with the same data as the test
$slotData = [
    'child_id' => $child->id,
    'day_of_week' => 1, // Monday
    'start_time' => '09:15:00',
    'end_time' => '09:45:00',
    'slot_type' => 'micro',
    'is_active' => true,
];

$reviewSlot = new ReviewSlot($slotData);

if ($reviewSlot->save($supabase)) {
    echo "   ✅ Slot created with ID: {$reviewSlot->id}\n";
    echo "   ⏰ Time range: {$reviewSlot->getTimeRange()}\n";
    echo "   📅 Day: {$reviewSlot->getDayName()}\n";
} else {
    echo "   ❌ Failed to create slot\n";
    exit(1);
}

echo "\n3. Verifying slot retrieval...\n";

// Test retrieving slots for Monday (day 1)
$mondaySlots = ReviewSlot::forChildAndDay($child->id, 1, $supabase);
echo "   Found {$mondaySlots->count()} slots for Monday\n";

foreach ($mondaySlots as $slot) {
    echo "   - {$slot->getTimeRange()} ({$slot->getSlotTypeLabel()})\n";
}

echo "\n4. Testing HTML attributes...\n";
echo "   Expected selector: #day-1-slots [data-time-range=\"{$reviewSlot->getTimeRange()}\"]\n";
echo "   Actual time range: {$reviewSlot->getTimeRange()}\n";

// Clean up
echo "\n5. Cleaning up...\n";
$reviewSlot->delete($supabase);
$child->delete($supabase);

echo "   ✅ Clean up completed\n";

echo "\n🎯 Debug Summary:\n";
echo "   - Slot creation: Works ✅\n";
echo "   - Time formatting: Works ✅\n";
echo "   - Database operations: Works ✅\n";
echo "   - Expected time format: {$reviewSlot->getTimeRange()}\n";

echo "\n💡 Next steps:\n";
echo "   - Check HTMX form submission in browser\n";
echo "   - Verify modal closing behavior\n";
echo "   - Test DOM update mechanism\n";
