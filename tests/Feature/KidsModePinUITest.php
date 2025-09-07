<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class KidsModePinUITest extends TestCase
{
    private $userId = 'test-user-123';

    private $userToken = 'test-token-456';

    private $userEmail = 'test@example.com';

    protected function setUp(): void
    {
        parent::setUp();

        // Set up session data for authenticated user
        Session::put('user_id', $this->userId);
        Session::put('supabase_token', $this->userToken);
        Session::put('user', [
            'id' => $this->userId,
            'email' => $this->userEmail,
        ]);
    }

    /** @test */
    public function it_displays_pin_settings_page_correctly_when_no_pin_is_set()
    {
        // Mock the SupabaseClient to return no PIN setup
        $this->mock(\App\Services\SupabaseClient::class, function ($mock) {
            $mock->shouldReceive('setUserToken')->once();
            $mock->shouldReceive('from')
                ->with('user_preferences')
                ->andReturnSelf();
            $mock->shouldReceive('select')
                ->with('kids_mode_pin, kids_mode_pin_salt, kids_mode_pin_attempts, kids_mode_pin_locked_until')
                ->andReturnSelf();
            $mock->shouldReceive('eq')
                ->with('user_id', $this->userId)
                ->andReturnSelf();
            $mock->shouldReceive('single')
                ->andReturn([
                    'kids_mode_pin' => null,
                    'kids_mode_pin_salt' => null,
                    'kids_mode_pin_attempts' => 0,
                    'kids_mode_pin_locked_until' => null,
                ]);
        });

        $response = $this->get(route('kids-mode.settings'));

        $response->assertStatus(200);
        $response->assertSee(__('kids_mode_settings'));
        $response->assertSee(__('pin_not_set'));
        $response->assertSee(__('set_kids_mode_pin'));
        $response->assertDontSee(__('reset_pin'));
    }

    /** @test */
    public function it_displays_pin_settings_page_correctly_when_pin_is_set()
    {
        // Mock the SupabaseClient to return existing PIN setup
        $this->mock(\App\Services\SupabaseClient::class, function ($mock) {
            $mock->shouldReceive('setUserToken')->once();
            $mock->shouldReceive('from')
                ->with('user_preferences')
                ->andReturnSelf();
            $mock->shouldReceive('select')
                ->with('kids_mode_pin, kids_mode_pin_salt, kids_mode_pin_attempts, kids_mode_pin_locked_until')
                ->andReturnSelf();
            $mock->shouldReceive('eq')
                ->with('user_id', $this->userId)
                ->andReturnSelf();
            $mock->shouldReceive('single')
                ->andReturn([
                    'kids_mode_pin' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                    'kids_mode_pin_salt' => 'test-salt',
                    'kids_mode_pin_attempts' => 0,
                    'kids_mode_pin_locked_until' => null,
                ]);
        });

        $response = $this->get(route('kids-mode.settings'));

        $response->assertStatus(200);
        $response->assertSee(__('kids_mode_settings'));
        $response->assertSee(__('pin_is_set'));
        $response->assertSee(__('update_kids_mode_pin'));
        $response->assertSee(__('reset_pin'));
    }

    /** @test */
    public function it_can_set_new_pin_with_valid_data()
    {
        // Mock the SupabaseClient for PIN setting
        $this->mock(\App\Services\SupabaseClient::class, function ($mock) {
            $mock->shouldReceive('setUserToken')->once();

            // Mock checking for existing preferences
            $mock->shouldReceive('from')
                ->with('user_preferences')
                ->andReturnSelf();
            $mock->shouldReceive('select')
                ->with('id')
                ->andReturnSelf();
            $mock->shouldReceive('eq')
                ->with('user_id', $this->userId)
                ->andReturnSelf();
            $mock->shouldReceive('single')
                ->andReturn(null); // No existing preferences

            // Mock inserting new preferences
            $mock->shouldReceive('from')
                ->with('user_preferences')
                ->andReturnSelf();
            $mock->shouldReceive('insert')
                ->once()
                ->andReturn(true);
        });

        $response = $this->post(route('kids-mode.pin.update'), [
            'pin' => '1234',
            'pin_confirmation' => '1234',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('kids-mode.settings'));
        $response->assertSessionHas('success', __('Kids mode PIN has been set successfully'));
    }

    /** @test */
    public function it_validates_pin_format_correctly()
    {
        $response = $this->post(route('kids-mode.pin.update'), [
            'pin' => '12a4', // Invalid: contains letter
            'pin_confirmation' => '12a4',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('pin');
    }

    /** @test */
    public function it_validates_pin_confirmation_match()
    {
        $response = $this->post(route('kids-mode.pin.update'), [
            'pin' => '1234',
            'pin_confirmation' => '5678', // Different PIN
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('pin_confirmation');
    }

    /** @test */
    public function it_can_reset_existing_pin()
    {
        // Mock the SupabaseClient for PIN reset
        $this->mock(\App\Services\SupabaseClient::class, function ($mock) {
            $mock->shouldReceive('setUserToken')->once();

            // Mock checking for existing preferences
            $mock->shouldReceive('from')
                ->with('user_preferences')
                ->andReturnSelf();
            $mock->shouldReceive('select')
                ->with('id')
                ->andReturnSelf();
            $mock->shouldReceive('eq')
                ->with('user_id', $this->userId)
                ->andReturnSelf();
            $mock->shouldReceive('single')
                ->andReturn(['id' => 1]); // Existing preferences

            // Mock updating preferences to clear PIN
            $mock->shouldReceive('from')
                ->with('user_preferences')
                ->andReturnSelf();
            $mock->shouldReceive('update')
                ->once()
                ->andReturn(true);
        });

        $response = $this->post(route('kids-mode.pin.reset'));

        $response->assertStatus(302);
        $response->assertRedirect(route('kids-mode.settings'));
        $response->assertSessionHas('success', __('Kids mode PIN has been reset successfully'));
    }

    /** @test */
    public function it_displays_exit_screen_correctly_when_no_pin_is_set()
    {
        // Set kids mode active
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 1);

        // Mock child data and PIN check
        $this->mock(\App\Services\SupabaseClient::class, function ($mock) {
            $mock->shouldReceive('setUserToken')->once();

            // Mock child lookup
            \App\Models\Child::shouldReceive('find')
                ->with(1, \Mockery::type(\App\Services\SupabaseClient::class))
                ->andReturn((object) [
                    'id' => 1,
                    'name' => 'Test Child',
                    'user_id' => $this->userId,
                ]);

            // Mock user preferences lookup (no PIN)
            $mock->shouldReceive('from')
                ->with('user_preferences')
                ->andReturnSelf();
            $mock->shouldReceive('select')
                ->with('kids_mode_pin, kids_mode_pin_salt, kids_mode_pin_attempts, kids_mode_pin_locked_until')
                ->andReturnSelf();
            $mock->shouldReceive('eq')
                ->with('user_id', $this->userId)
                ->andReturnSelf();
            $mock->shouldReceive('single')
                ->andReturn([
                    'kids_mode_pin' => null,
                    'kids_mode_pin_salt' => null,
                    'kids_mode_pin_attempts' => 0,
                    'kids_mode_pin_locked_until' => null,
                ]);
        });

        $response = $this->get(route('kids-mode.exit'));

        $response->assertStatus(200);
        $response->assertSee(__('PIN Not Set'));
        $response->assertSee(__('A parent needs to set up a PIN first'));
        $response->assertDontSee('pin-entry-container');
    }

    /** @test */
    public function it_displays_exit_screen_with_pin_entry_when_pin_is_set()
    {
        // Set kids mode active
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 1);

        // Mock child data and PIN check
        $this->mock(\App\Services\SupabaseClient::class, function ($mock) {
            $mock->shouldReceive('setUserToken')->once();

            // Mock child lookup
            \App\Models\Child::shouldReceive('find')
                ->with(1, \Mockery::type(\App\Services\SupabaseClient::class))
                ->andReturn((object) [
                    'id' => 1,
                    'name' => 'Test Child',
                    'user_id' => $this->userId,
                ]);

            // Mock user preferences lookup (PIN set)
            $mock->shouldReceive('from')
                ->with('user_preferences')
                ->andReturnSelf();
            $mock->shouldReceive('select')
                ->with('kids_mode_pin, kids_mode_pin_salt, kids_mode_pin_attempts, kids_mode_pin_locked_until')
                ->andReturnSelf();
            $mock->shouldReceive('eq')
                ->with('user_id', $this->userId)
                ->andReturnSelf();
            $mock->shouldReceive('single')
                ->andReturn([
                    'kids_mode_pin' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                    'kids_mode_pin_salt' => 'test-salt',
                    'kids_mode_pin_attempts' => 0,
                    'kids_mode_pin_locked_until' => null,
                ]);
        });

        $response = $this->get(route('kids-mode.exit'));

        $response->assertStatus(200);
        $response->assertSee(__('enter_parent_pin'));
        $response->assertSee('pin-entry-container');
        $response->assertSee(__('exit_kids_mode'));
        $response->assertSee(__('back_to_learning'));
    }

    /** @test */
    public function it_displays_lockout_message_when_account_is_locked()
    {
        // Set kids mode active
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 1);

        $lockoutTime = now()->addMinutes(3);

        // Mock child data and locked PIN check
        $this->mock(\App\Services\SupabaseClient::class, function ($mock) use ($lockoutTime) {
            $mock->shouldReceive('setUserToken')->once();

            // Mock child lookup
            \App\Models\Child::shouldReceive('find')
                ->with(1, \Mockery::type(\App\Services\SupabaseClient::class))
                ->andReturn((object) [
                    'id' => 1,
                    'name' => 'Test Child',
                    'user_id' => $this->userId,
                ]);

            // Mock user preferences lookup (locked account)
            $mock->shouldReceive('from')
                ->with('user_preferences')
                ->andReturnSelf();
            $mock->shouldReceive('select')
                ->with('kids_mode_pin, kids_mode_pin_salt, kids_mode_pin_attempts, kids_mode_pin_locked_until')
                ->andReturnSelf();
            $mock->shouldReceive('eq')
                ->with('user_id', $this->userId)
                ->andReturnSelf();
            $mock->shouldReceive('single')
                ->andReturn([
                    'kids_mode_pin' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                    'kids_mode_pin_salt' => 'test-salt',
                    'kids_mode_pin_attempts' => 5,
                    'kids_mode_pin_locked_until' => $lockoutTime->toISOString(),
                ]);
        });

        $response = $this->get(route('kids-mode.exit'));

        $response->assertStatus(200);
        $response->assertSee(__('Account Locked'));
        $response->assertSee(__('too_many_attempts'));
        $response->assertDontSee('pin-entry-container');
    }

    /** @test */
    public function it_shows_attempts_remaining_when_there_are_failed_attempts()
    {
        // Set kids mode active
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 1);

        // Mock child data and PIN check with failed attempts
        $this->mock(\App\Services\SupabaseClient::class, function ($mock) {
            $mock->shouldReceive('setUserToken')->once();

            // Mock child lookup
            \App\Models\Child::shouldReceive('find')
                ->with(1, \Mockery::type(\App\Services\SupabaseClient::class))
                ->andReturn((object) [
                    'id' => 1,
                    'name' => 'Test Child',
                    'user_id' => $this->userId,
                ]);

            // Mock user preferences lookup (3 failed attempts)
            $mock->shouldReceive('from')
                ->with('user_preferences')
                ->andReturnSelf();
            $mock->shouldReceive('select')
                ->with('kids_mode_pin, kids_mode_pin_salt, kids_mode_pin_attempts, kids_mode_pin_locked_until')
                ->andReturnSelf();
            $mock->shouldReceive('eq')
                ->with('user_id', $this->userId)
                ->andReturnSelf();
            $mock->shouldReceive('single')
                ->andReturn([
                    'kids_mode_pin' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                    'kids_mode_pin_salt' => 'test-salt',
                    'kids_mode_pin_attempts' => 3,
                    'kids_mode_pin_locked_until' => null,
                ]);
        });

        $response = $this->get(route('kids-mode.exit'));

        $response->assertStatus(200);
        $response->assertSee(__('Attempts remaining'));
        $response->assertSee('2'); // 5 - 3 = 2 attempts remaining
    }

    /** @test */
    public function it_redirects_when_kids_mode_is_not_active_on_exit_screen()
    {
        // Don't set kids_mode_active in session

        $response = $this->get(route('kids-mode.exit'));

        $response->assertStatus(302);
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', __('Kids mode is not active'));
    }

    /** @test */
    public function it_validates_pin_submission_format()
    {
        // Set kids mode active
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 1);

        $response = $this->postJson(route('kids-mode.exit.validate'), [
            'pin' => '12a4', // Invalid: contains letter
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('pin');
    }

    /** @test */
    public function it_requires_authentication_for_pin_operations()
    {
        // Clear session to simulate unauthenticated user
        Session::flush();

        $response = $this->get(route('kids-mode.settings'));
        $response->assertRedirect(route('login'));

        $response = $this->post(route('kids-mode.pin.update'), [
            'pin' => '1234',
            'pin_confirmation' => '1234',
        ]);
        $response->assertStatus(401);

        $response = $this->post(route('kids-mode.pin.reset'));
        $response->assertStatus(401);
    }

    /** @test */
    public function it_blocks_pin_settings_access_when_in_kids_mode()
    {
        // Set kids mode active
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 1);

        $response = $this->get(route('kids-mode.settings'));

        // Should be blocked by NotInKidsMode middleware
        $response->assertStatus(302);
        $response->assertRedirect(route('dashboard.child-today', ['child_id' => 1]));
    }

    /** @test */
    public function pin_settings_form_includes_required_elements()
    {
        // Mock no PIN setup
        $this->mock(\App\Services\SupabaseClient::class, function ($mock) {
            $mock->shouldReceive('setUserToken')->once();
            $mock->shouldReceive('from')
                ->with('user_preferences')
                ->andReturnSelf();
            $mock->shouldReceive('select')
                ->with('kids_mode_pin, kids_mode_pin_salt, kids_mode_pin_attempts, kids_mode_pin_locked_until')
                ->andReturnSelf();
            $mock->shouldReceive('eq')
                ->with('user_id', $this->userId)
                ->andReturnSelf();
            $mock->shouldReceive('single')
                ->andReturn([]);
        });

        $response = $this->get(route('kids-mode.settings'));

        $response->assertStatus(200);

        // Check for form elements
        $response->assertSee('name="pin"', false);
        $response->assertSee('name="pin_confirmation"', false);
        $response->assertSee('type="password"', false);
        $response->assertSee('maxlength="4"', false);
        $response->assertSee('pattern="[0-9]{4}"', false);
        $response->assertSee('autocomplete="off"', false);

        // Check for security information
        $response->assertSee(__('Security Information'));
        $response->assertSee(__('Your PIN is encrypted and securely stored'));
        $response->assertSee(__('After 5 failed attempts, PIN entry is locked for 5 minutes'));
    }

    /** @test */
    public function exit_screen_includes_required_elements_when_pin_is_set()
    {
        // Set kids mode active
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 1);

        // Mock child data and PIN check
        $this->mock(\App\Services\SupabaseClient::class, function ($mock) {
            $mock->shouldReceive('setUserToken')->once();

            \App\Models\Child::shouldReceive('find')
                ->with(1, \Mockery::type(\App\Services\SupabaseClient::class))
                ->andReturn((object) [
                    'id' => 1,
                    'name' => 'Test Child',
                    'user_id' => $this->userId,
                ]);

            $mock->shouldReceive('from')
                ->with('user_preferences')
                ->andReturnSelf();
            $mock->shouldReceive('select')
                ->with('kids_mode_pin, kids_mode_pin_salt, kids_mode_pin_attempts, kids_mode_pin_locked_until')
                ->andReturnSelf();
            $mock->shouldReceive('eq')
                ->with('user_id', $this->userId)
                ->andReturnSelf();
            $mock->shouldReceive('single')
                ->andReturn([
                    'kids_mode_pin' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                    'kids_mode_pin_salt' => 'test-salt',
                    'kids_mode_pin_attempts' => 0,
                    'kids_mode_pin_locked_until' => null,
                ]);
        });

        $response = $this->get(route('kids-mode.exit'));

        $response->assertStatus(200);

        // Check for keypad elements
        $response->assertSee('class="pin-digit"', false);
        $response->assertSee('data-digit="1"', false);
        $response->assertSee('data-digit="0"', false);
        $response->assertSee('id="clear-btn"', false);
        $response->assertSee('id="backspace-btn"', false);
        $response->assertSee('id="submit-pin-btn"', false);

        // Check for child-friendly design elements
        $response->assertSee('Test Child');
        $response->assertSee(__('Learning time with :name', ['name' => 'Test Child']));
        $response->assertSee(__('back_to_learning'));
    }
}
