<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OnboardingFullWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_onboarding_workflow_with_laravel_auth()
    {
        // Step 1: Create and authenticate a user
        $user = User::factory()->create([
            'name' => 'Test Parent',
            'email' => 'parent@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Authenticate the user
        $this->actingAs($user);

        // Step 2: Access onboarding page
        $response = $this->get('/onboarding');
        $response->assertStatus(200);
        $response->assertViewIs('onboarding.index');

        // Step 3: Submit children data (the critical test - this was failing before)
        $childrenData = [
            'children' => [
                [
                    'name' => 'Emma Thompson',
                    'age' => 8,
                    'independence_level' => 2,
                ],
                [
                    'name' => 'Oliver Thompson',
                    'age' => 12,
                    'independence_level' => 3,
                ],
            ],
        ];

        $response = $this->postJson('/onboarding/children', $childrenData);

        // This should succeed now with Laravel auth and Eloquent models
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify children were created in the database
        $this->assertCount(2, $user->children);

        $emma = $user->children()->where('name', 'Emma Thompson')->first();
        $this->assertNotNull($emma);
        $this->assertEquals(8, $emma->age);
        $this->assertEquals(2, $emma->independence_level);

        $oliver = $user->children()->where('name', 'Oliver Thompson')->first();
        $this->assertNotNull($oliver);
        $this->assertEquals(12, $oliver->age);
        $this->assertEquals(3, $oliver->independence_level);

        // Step 4: Submit subjects data
        $subjectsData = [
            'subjects' => [
                [
                    'name' => 'Mathematics',
                    'child_id' => $emma->id,
                    'color' => '#FF6B6B',
                ],
                [
                    'name' => 'Reading',
                    'child_id' => $emma->id,
                    'color' => '#4ECDC4',
                ],
                [
                    'name' => 'Science',
                    'child_id' => $oliver->id,
                    'color' => '#45B7D1',
                ],
                [
                    'name' => 'History',
                    'child_id' => $oliver->id,
                    'color' => '#96CEB4',
                ],
            ],
        ];

        $response = $this->postJson('/onboarding/subjects', $subjectsData);

        // This should also succeed now
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify subjects were created
        $this->assertCount(4, Subject::all());
        $this->assertCount(2, $emma->subjects);
        $this->assertCount(2, $oliver->subjects);

        // Step 5: Complete onboarding
        $response = $this->postJson('/onboarding/complete');
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify onboarding completion was recorded
        $userPrefs = DB::table('user_preferences')->where('user_id', $user->id)->first();
        $this->assertNotNull($userPrefs);
        $this->assertTrue($userPrefs->onboarding_completed);

        // Step 6: Verify dashboard access
        $response = $this->get('/dashboard');
        $response->assertStatus(200);

        // Verify children appear on dashboard
        $response->assertSee('Emma Thompson');
        $response->assertSee('Oliver Thompson');
    }

    public function test_onboarding_children_requires_authentication()
    {
        // Unauthenticated request should be blocked by auth middleware
        $response = $this->postJson('/onboarding/children', [
            'children' => [
                ['name' => 'Test Child', 'age' => 8, 'independence_level' => 1],
            ],
        ]);

        // Laravel auth middleware blocks with 401 and standard message
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_onboarding_subjects_requires_authentication()
    {
        // Unauthenticated request should be blocked by auth middleware
        $response = $this->postJson('/onboarding/subjects', [
            'subjects' => [
                ['name' => 'Math', 'child_id' => 1, 'color' => '#FF6B6B'],
            ],
        ]);

        // Laravel auth middleware blocks with 401 and standard message
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_onboarding_prevents_children_creation_for_wrong_user()
    {
        // Create two users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create a child for user1
        $child = Child::factory()->create(['user_id' => $user1->id]);

        // Authenticate as user2 and try to create subjects for user1's child
        $this->actingAs($user2);

        $response = $this->postJson('/onboarding/subjects', [
            'subjects' => [
                [
                    'name' => 'Math',
                    'child_id' => $child->id, // This is user1's child
                    'color' => '#FF6B6B',
                ],
            ],
        ]);

        // Should fail with validation error
        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid child selected']);
    }

    public function test_onboarding_validation_works_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Test children validation
        $response = $this->postJson('/onboarding/children', [
            'children' => [
                [
                    'name' => '', // Empty name should fail
                    'age' => 8,
                    'independence_level' => 2,
                ],
            ],
        ]);

        $response->assertStatus(422);

        // Test age validation
        $response = $this->postJson('/onboarding/children', [
            'children' => [
                [
                    'name' => 'Valid Name',
                    'age' => 2, // Too young, should fail
                    'independence_level' => 2,
                ],
            ],
        ]);

        $response->assertStatus(422);

        // Test subjects validation
        $child = Child::factory()->create(['user_id' => $user->id]);

        $response = $this->postJson('/onboarding/subjects', [
            'subjects' => [
                [
                    'name' => '', // Empty name should fail
                    'child_id' => $child->id,
                    'color' => '#FF6B6B',
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_user_data_isolation_works()
    {
        // Create two users with their own data
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $child1 = Child::factory()->create(['user_id' => $user1->id, 'name' => 'User1 Child']);
        $child2 = Child::factory()->create(['user_id' => $user2->id, 'name' => 'User2 Child']);

        $subject1 = Subject::factory()->create(['user_id' => $user1->id, 'child_id' => $child1->id, 'name' => 'User1 Subject']);
        $subject2 = Subject::factory()->create(['user_id' => $user2->id, 'child_id' => $child2->id, 'name' => 'User2 Subject']);

        // Test User1 isolation
        $this->actingAs($user1);

        // User1 should only see their own children
        $this->assertCount(1, $user1->children);
        $this->assertEquals('User1 Child', $user1->children->first()->name);

        // User1 should only see their own subjects
        $userSubjects = Subject::where('user_id', $user1->id)->get();
        $this->assertCount(1, $userSubjects);
        $this->assertEquals('User1 Subject', $userSubjects->first()->name);

        // Dashboard should only show User1's data
        $response = $this->get('/dashboard');
        $response->assertSee('User1 Child');
        $response->assertDontSee('User2 Child');

        // Test User2 isolation
        $this->actingAs($user2);

        $this->assertCount(1, $user2->children);
        $this->assertEquals('User2 Child', $user2->children->first()->name);

        $response = $this->get('/dashboard');
        $response->assertSee('User2 Child');
        $response->assertDontSee('User1 Child');
    }
}
