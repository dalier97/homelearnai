<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\User;
use App\Services\SupabaseClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class KidsModeEnterExitTest extends TestCase
{
    use RefreshDatabase;

    protected $supabaseClient;

    protected $user;

    protected $child;

    protected $accessToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable session middleware but disable unnecessary middleware for testing
        $this->withoutMiddleware([
            \App\Http\Middleware\VerifyCsrfToken::class,
        ]);

        $this->supabaseClient = $this->app->make(SupabaseClient::class);

        // Create and authenticate user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Set up test access token
        $this->accessToken = 'test-access-token-123';

        // Create a test child using the user relationship
        $this->child = $this->user->children()->create([
            'name' => 'Test Child',
            'grade' => '3rd',
            'independence_level' => 2,
        ]);

        // Mock the SupabaseClient for testing
        $this->mockSupabaseClient();
    }

    protected function mockSupabaseClient(): void
    {
        $this->supabaseClient = $this->createMock(SupabaseClient::class);
        $this->app->instance(SupabaseClient::class, $this->supabaseClient);
    }

    /** @test */
    public function parent_dashboard_shows_pin_status_and_enter_kids_mode_buttons(): void
    {
        // Mock user preferences with PIN set
        $this->supabaseClient
            ->expects($this->any())
            ->method('from')
            ->willReturnSelf();

        $this->supabaseClient
            ->expects($this->any())
            ->method('select')
            ->willReturnSelf();

        $this->supabaseClient
            ->expects($this->any())
            ->method('eq')
            ->willReturnSelf();

        $this->supabaseClient
            ->expects($this->any())
            ->method('single')
            ->willReturn(['kids_mode_pin' => Hash::make('1234')]);

        // Children data is already set up in setUp() method via $this->child

        $response = $this->get(route('dashboard.parent'));

        $response->assertOk();
        $response->assertViewIs('dashboard.parent');
        $response->assertViewHas('pin_is_set', true);
        $response->assertSee('Enter Kids Mode');
        $response->assertSee('kids-mode-enter-btn');
    }

    /** @test */
    public function parent_dashboard_shows_set_pin_first_when_pin_not_set(): void
    {
        // Mock user preferences without PIN
        $this->supabaseClient
            ->expects($this->any())
            ->method('from')
            ->willReturnSelf();

        $this->supabaseClient
            ->expects($this->any())
            ->method('select')
            ->willReturnSelf();

        $this->supabaseClient
            ->expects($this->any())
            ->method('eq')
            ->willReturnSelf();

        $this->supabaseClient
            ->expects($this->any())
            ->method('single')
            ->willReturn(['kids_mode_pin' => null]);

        // Children data is already set up in setUp() method via $this->child

        $response = $this->get(route('dashboard.parent'));

        $response->assertOk();
        $response->assertViewHas('pin_is_set', false);
        $response->assertSee('Set PIN First');
        $response->assertSee(route('kids-mode.settings'));
    }

    /** @test */
    public function can_enter_kids_mode_when_pin_is_set(): void
    {
        // Mock finding the child
        $this->supabaseClient
            ->expects($this->once())
            ->method('setUserToken')
            ->with($this->accessToken);

        // Child will be found via database using real data

        $response = $this->post(route('kids-mode.enter', $this->child->id));

        // Check session data is set
        $this->assertTrue(Session::get('kids_mode_active'));
        $this->assertEquals($this->child->id, Session::get('kids_mode_child_id'));
        $this->assertEquals($this->child->name, Session::get('kids_mode_child_name'));
        $this->assertNotNull(Session::get('kids_mode_entered_at'));

        // Should redirect to child today view
        $response->assertRedirect(route('dashboard.child-today', $this->child->id));
        $response->assertSessionHas('success');
    }

    /** @test */
    public function cannot_enter_kids_mode_for_non_existent_child(): void
    {
        // Test with non-existent child ID (99999)

        $response = $this->post(route('kids-mode.enter', 99999));

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Child not found']);

        // Session should not be modified
        $this->assertNull(Session::get('kids_mode_active'));
    }

    /** @test */
    public function cannot_enter_kids_mode_for_other_users_child(): void
    {
        // Create a child for another user (unauthorized access test)
        $otherChild = Child::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Other Child',
            'grade' => '4th',
            'independence_level' => 2,
        ]);

        $response = $this->post(route('kids-mode.enter', $otherChild->id));

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Child not found']);

        // Session should not be modified
        $this->assertNull(Session::get('kids_mode_active'));
    }

    /** @test */
    public function kids_mode_enter_returns_json_for_ajax_requests(): void
    {
        // Child will be found via database using real data

        $response = $this->postJson(route('kids-mode.enter', $this->child->id));

        $response->assertOk();
        $response->assertJson([
            'message' => 'Kids mode activated for '.$this->child->name,
            'child_id' => $this->child->id,
            'child_name' => $this->child->name,
        ]);

        // Check session data is set
        $this->assertTrue(Session::get('kids_mode_active'));
        $this->assertEquals($this->child->name, Session::get('kids_mode_child_name'));
    }

    /** @test */
    public function kids_mode_indicator_shows_when_active(): void
    {
        // Set kids mode session
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', $this->child->id);
        Session::put('kids_mode_child_name', $this->child->name);
        Session::put('kids_mode_entered_at', now()->toISOString());

        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertSee('data-testid="kids-mode-indicator"', false);
        $response->assertSee('Kids Mode Active');
        $response->assertSee($this->child->name);
        $response->assertSee('Exit Kids Mode');
    }

    /** @test */
    public function kids_mode_indicator_hidden_when_not_active(): void
    {
        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('data-testid="kids-mode-indicator"', false);
        $response->assertDontSee('Kids Mode Active');
    }

    /** @test */
    public function navigation_is_restricted_in_kids_mode(): void
    {
        // Set kids mode session
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', $this->child->id);
        Session::put('kids_mode_child_name', $this->child->name);

        $response = $this->get('/dashboard');

        $response->assertOk();

        // Parent navigation should be hidden
        $response->assertDontSee('Parent Dashboard', false);
        $response->assertDontSee('Planning Board');

        // Child navigation should be visible
        $response->assertSee('My Today');
        $response->assertSee('My Reviews');
    }

    /** @test */
    public function can_exit_kids_mode_with_valid_pin(): void
    {
        // Set up kids mode session
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', $this->child->id);
        Session::put('kids_mode_child_name', $this->child->name);

        // Mock user preferences with PIN
        $hashedPin = Hash::make('1234');
        $this->supabaseClient
            ->expects($this->any())
            ->method('from')
            ->willReturnSelf();

        $this->supabaseClient
            ->expects($this->any())
            ->method('select')
            ->willReturnSelf();

        $this->supabaseClient
            ->expects($this->any())
            ->method('eq')
            ->willReturnSelf();

        $this->supabaseClient
            ->expects($this->any())
            ->method('single')
            ->willReturn([
                'kids_mode_pin' => $hashedPin,
                'kids_mode_pin_attempts' => 0,
                'kids_mode_pin_locked_until' => null,
            ]);

        // Mock update call
        $this->supabaseClient
            ->expects($this->any())
            ->method('update')
            ->willReturnSelf();

        $response = $this->postJson(route('kids-mode.exit.validate'), [
            'pin' => '1234',
        ]);

        $response->assertOk();
        $response->assertJson([
            'message' => 'Kids mode deactivated successfully',
            'redirect_url' => route('dashboard'),
        ]);

        // Session should be cleared
        $this->assertNull(Session::get('kids_mode_active'));
        $this->assertNull(Session::get('kids_mode_child_id'));
        $this->assertNull(Session::get('kids_mode_child_name'));
    }

    /** @test */
    public function cannot_exit_kids_mode_with_invalid_pin(): void
    {
        // Set up kids mode session
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', $this->child->id);

        // Mock user preferences with different PIN
        $hashedPin = Hash::make('5678'); // Different PIN
        $this->supabaseClient
            ->expects($this->any())
            ->method('from')
            ->willReturnSelf();

        $this->supabaseClient
            ->expects($this->any())
            ->method('select')
            ->willReturnSelf();

        $this->supabaseClient
            ->expects($this->any())
            ->method('eq')
            ->willReturnSelf();

        $this->supabaseClient
            ->expects($this->any())
            ->method('single')
            ->willReturn([
                'kids_mode_pin' => $hashedPin,
                'kids_mode_pin_attempts' => 0,
                'kids_mode_pin_locked_until' => null,
            ]);

        $response = $this->postJson(route('kids-mode.exit.validate'), [
            'pin' => '1234', // Wrong PIN
        ]);

        $response->assertStatus(400);
        $response->assertJsonStructure(['error', 'attempts_remaining']);

        // Session should remain active
        $this->assertTrue(Session::get('kids_mode_active'));
    }

    /** @test */
    public function pin_exit_screen_shows_correct_content(): void
    {
        // Set up kids mode session
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', $this->child->id);
        Session::put('kids_mode_child_name', $this->child->name);

        // Mock child and PIN setup
        // Child will be found via database using real data

        $this->supabaseClient
            ->expects($this->any())
            ->method('from')
            ->willReturnSelf();

        $this->supabaseClient
            ->expects($this->any())
            ->method('select')
            ->willReturnSelf();

        $this->supabaseClient
            ->expects($this->any())
            ->method('eq')
            ->willReturnSelf();

        $this->supabaseClient
            ->expects($this->any())
            ->method('single')
            ->willReturn([
                'kids_mode_pin' => Hash::make('1234'),
                'kids_mode_pin_attempts' => 0,
                'kids_mode_pin_locked_until' => null,
            ]);

        $response = $this->get(route('kids-mode.exit'));

        $response->assertOk();
        $response->assertViewIs('kids-mode.exit');
        $response->assertViewHas('child', $this->child);
        $response->assertViewHas('has_pin_setup', true);
        $response->assertViewHas('is_locked', false);
        $response->assertSee('Enter the 4-digit PIN');
    }

    /** @test */
    public function cannot_access_parent_only_routes_in_kids_mode(): void
    {
        // Set up kids mode session
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', $this->child->id);

        // Test blocked routes
        $blockedRoutes = [
            'dashboard.parent',
            'children.index',
            'planning.index',
            'subjects.index',
            'kids-mode.settings',
        ];

        foreach ($blockedRoutes as $route) {
            $response = $this->get(route($route));

            // Should redirect to child today view
            $response->assertRedirect(route('dashboard.child-today', $this->child->id));
            $response->assertSessionHas('error', 'Access denied in kids mode');
        }
    }

    /** @test */
    public function can_access_allowed_routes_in_kids_mode(): void
    {
        // Set up kids mode session
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', $this->child->id);

        // Test allowed routes - these should not redirect
        $allowedRoutes = [
            'kids-mode.exit',
            'locale.update',
        ];

        foreach ($allowedRoutes as $route) {
            try {
                $response = $this->get(route($route));
                // Should not redirect to child today view
                $this->assertNotEquals(
                    route('dashboard.child-today', $this->child->id),
                    $response->headers->get('Location')
                );
            } catch (\Exception $e) {
                // Some routes may have additional requirements, but they shouldn't be blocked
                $this->assertNotContains('Access denied in kids mode', $e->getMessage());
            }
        }
    }
}
