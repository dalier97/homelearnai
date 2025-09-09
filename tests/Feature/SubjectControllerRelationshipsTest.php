<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class SubjectControllerRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that subjects index uses Eloquent relationships
     */
    public function test_subjects_index_uses_eloquent_relationships()
    {
        // Create user and child with subjects using relationships
        $user = User::factory()->create();
        $child = $user->children()->create([
            'name' => 'Test Child',
            'age' => 8,
            'independence_level' => 2,
        ]);

        $subject = $child->subjects()->create([
            'name' => 'Mathematics',
            'color' => '#3b82f6',
            'user_id' => $user->id,
        ]);

        // Set up session for authentication (mimicking Supabase auth)
        Session::put('user_id', $user->id);
        Session::put('user_name', $user->name);
        Session::put('user_email', $user->email);
        Session::put('supabase_token', 'fake-token-for-testing');
        Session::put('supabase_token', 'fake-token-for-testing');

        // Test the subjects index with child_id parameter
        $response = $this->get('/subjects?child_id='.$child->id);

        $response->assertStatus(200);
        $response->assertViewIs('subjects.index');
        $response->assertViewHas('subjects');
        $response->assertViewHas('selectedChild');

        $subjects = $response->original->getData()['subjects'];
        $this->assertCount(1, $subjects);
        $this->assertEquals('Mathematics', $subjects->first()->name);
        $this->assertEquals($child->id, $subjects->first()->child_id);
    }

    /**
     * Test that creating subjects works with relationships
     */
    public function test_subject_creation_uses_relationships()
    {
        $user = User::factory()->create();
        $child = $user->children()->create([
            'name' => 'Test Child',
            'age' => 8,
            'independence_level' => 2,
        ]);

        Session::put('user_id', $user->id);
        Session::put('user_name', $user->name);
        Session::put('user_email', $user->email);
        Session::put('supabase_token', 'fake-token-for-testing');

        $subjectData = [
            'name' => 'Science',
            'color' => '#10b981',
            'child_id' => $child->id,
        ];

        $response = $this->post('/subjects', $subjectData);

        $response->assertRedirect('/subjects');

        // Verify the subject was created using relationships
        $this->assertEquals(1, $child->subjects()->count());
        $subject = $child->subjects()->first();
        $this->assertEquals('Science', $subject->name);
        $this->assertEquals('#10b981', $subject->color);
        $this->assertEquals($user->id, $subject->user_id);
        $this->assertEquals($child->id, $subject->child_id);
    }

    /**
     * Test that quick start subjects creation uses relationships
     */
    public function test_quick_start_uses_relationships()
    {
        $user = User::factory()->create();
        $child = $user->children()->create([
            'name' => 'Test Child',
            'age' => 8,
            'independence_level' => 2,
        ]);

        Session::put('user_id', $user->id);
        Session::put('user_name', $user->name);
        Session::put('user_email', $user->email);
        Session::put('supabase_token', 'fake-token-for-testing');

        $quickStartData = [
            'grade_level' => 'elementary',
            'subjects' => ['Mathematics', 'Science', 'Reading'],
            'child_id' => $child->id,
        ];

        $response = $this->post('/subjects/quick-start', $quickStartData);

        $response->assertRedirect('/subjects');

        // Verify subjects were created using relationships
        $this->assertEquals(3, $child->subjects()->count());

        $subjectNames = $child->subjects()->pluck('name')->toArray();
        $this->assertContains('Mathematics', $subjectNames);
        $this->assertContains('Science', $subjectNames);
        $this->assertContains('Reading', $subjectNames);

        // Verify all subjects belong to the correct child and user
        foreach ($child->subjects as $subject) {
            $this->assertEquals($user->id, $subject->user_id);
            $this->assertEquals($child->id, $subject->child_id);
        }
    }

    /**
     * Test data integrity - subjects are properly scoped to their child
     */
    public function test_subject_access_security_with_relationships()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $child1 = $user1->children()->create([
            'name' => 'Child 1',
            'age' => 8,
            'independence_level' => 2,
        ]);

        $child2 = $user2->children()->create([
            'name' => 'Child 2',
            'age' => 10,
            'independence_level' => 3,
        ]);

        $subject1 = $child1->subjects()->create([
            'name' => 'Math for Child 1',
            'color' => '#3b82f6',
            'user_id' => $user1->id,
        ]);

        $subject2 = $child2->subjects()->create([
            'name' => 'Math for Child 2',
            'color' => '#10b981',
            'user_id' => $user2->id,
        ]);

        // Test that user1 can only see their child's subjects
        Session::put('user_id', $user1->id);
        Session::put('user_name', $user1->name);
        Session::put('user_email', $user1->email);

        $response = $this->get('/subjects?child_id='.$child1->id);
        $response->assertStatus(200);

        $subjects = $response->original->getData()['subjects'];
        $this->assertCount(1, $subjects);
        $this->assertEquals('Math for Child 1', $subjects->first()->name);

        // Test that user1 cannot see user2's child subjects
        $response = $this->get('/subjects?child_id='.$child2->id);
        $response->assertStatus(200);

        $subjects = $response->original->getData()['subjects'];
        $this->assertCount(0, $subjects);
    }
}
