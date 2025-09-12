<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingChildrenTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function onboarding_children_endpoint_validates_required_fields()
    {
        // Mock authenticated user session
        $this->session([
            'user_id' => 'test-user-123',
            'supabase_token' => 'test-token',
        ]);

        // Test empty children array
        $response = $this->postJson('/onboarding/children', [
            'children' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['children']);
    }

    /** @test */
    public function onboarding_children_endpoint_validates_child_fields()
    {
        // Mock authenticated user session
        $this->session([
            'user_id' => 'test-user-123',
            'supabase_token' => 'test-token',
        ]);

        // Test missing required fields
        $response = $this->postJson('/onboarding/children', [
            'children' => [
                [
                    'name' => '', // Empty name
                    'grade' => '', // Empty grade
                    'independence_level' => '',
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'children.0.name',
            'children.0.grade',
            'children.0.independence_level',
        ]);
    }

    /** @test */
    public function onboarding_children_endpoint_validates_grade_options()
    {
        // Mock authenticated user session
        $this->session([
            'user_id' => 'test-user-123',
            'supabase_token' => 'test-token',
        ]);

        // Test invalid grade values
        $response = $this->postJson('/onboarding/children', [
            'children' => [
                [
                    'name' => 'Test Child',
                    'grade' => 'Invalid', // Invalid grade
                    'independence_level' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['children.0.grade']);

        // Test another invalid grade
        $response = $this->postJson('/onboarding/children', [
            'children' => [
                [
                    'name' => 'Test Child',
                    'grade' => '13th', // Invalid grade
                    'independence_level' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['children.0.grade']);
    }

    /** @test */
    public function onboarding_children_endpoint_validates_independence_level()
    {
        // Mock authenticated user session
        $this->session([
            'user_id' => 'test-user-123',
            'supabase_token' => 'test-token',
        ]);

        // Test invalid independence level
        $response = $this->postJson('/onboarding/children', [
            'children' => [
                [
                    'name' => 'Test Child',
                    'grade' => '3rd',
                    'independence_level' => 5, // Invalid level
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['children.0.independence_level']);
    }

    /** @test */
    public function onboarding_children_endpoint_validates_maximum_children()
    {
        // Mock authenticated user session
        $this->session([
            'user_id' => 'test-user-123',
            'supabase_token' => 'test-token',
        ]);

        // Create 6 children (exceeds max of 5)
        $children = [];
        for ($i = 1; $i <= 6; $i++) {
            $children[] = [
                'name' => "Child $i",
                'grade' => '3rd',
                'independence_level' => 1,
            ];
        }

        $response = $this->postJson('/onboarding/children', [
            'children' => $children,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['children']);
    }

    /** @test */
    public function onboarding_children_endpoint_requires_authentication()
    {
        // Test without session (no user_id) - should redirect to login
        $response = $this->postJson('/onboarding/children', [
            'children' => [
                [
                    'name' => 'Test Child',
                    'grade' => '3rd',
                    'independence_level' => 1,
                ],
            ],
        ]);

        // Should redirect to login page (302 status)
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /** @test */
    public function onboarding_view_loads_successfully()
    {
        // Mock authenticated user session
        $this->session([
            'user_id' => 'test-user-123',
            'supabase_token' => 'test-token',
        ]);

        $response = $this->get('/onboarding');

        $response->assertStatus(200);
        $response->assertViewIs('onboarding.index');
        $response->assertSeeText('Welcome to Homeschool Hub!');
        $response->assertSee('data-testid="step-1"', false);
        $response->assertSee('data-testid="step-2"', false);
        $response->assertSee('data-testid="children-form"', false);
    }

    /** @test */
    public function onboarding_form_includes_required_elements()
    {
        // Mock authenticated user session
        $this->session([
            'user_id' => 'test-user-123',
            'supabase_token' => 'test-token',
        ]);

        $response = $this->get('/onboarding');

        $response->assertStatus(200);

        // Check for Alpine.js function
        $response->assertSee('function onboardingWizard()', false);

        // Check for form elements
        $response->assertSee('data-testid="add-another-child"', false);
        $response->assertSee('data-testid="next-button"', false);
        $response->assertSee('data-testid="previous-button"', false);

        // Check for children form fields
        $response->assertSee('child-name-', false);
        $response->assertSee('child-grade-', false);
        $response->assertSee('child-independence-', false);
    }
}
