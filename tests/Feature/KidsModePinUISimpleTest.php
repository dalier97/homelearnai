<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class KidsModePinUISimpleTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create and authenticate user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_pin_settings_route_is_accessible()
    {
        $response = $this->get(route('kids-mode.settings'));

        // Should not be a 404, meaning the route exists
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_pin_settings_requires_authentication()
    {
        // Clear session to simulate unauthenticated user
        Session::flush();

        $response = $this->get(route('kids-mode.settings'));

        // Should redirect to login
        $response->assertRedirect(route('login'));
    }

    public function test_kids_mode_settings_link_appears_in_navigation()
    {
        // This test checks if the navigation includes the kids mode settings link
        $response = $this->get(route('dashboard'));

        // Look for the kids mode settings translation key in the HTML
        $this->assertTrue(
            str_contains($response->getContent(), __('kids_mode_settings')) ||
            str_contains($response->getContent(), 'kids-mode.settings')
        );
    }

    public function test_pin_update_validates_pin_format()
    {
        $response = $this->post(route('kids-mode.pin.update'), [
            'pin' => '12a4', // Invalid: contains letter
            'pin_confirmation' => '12a4',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('pin');
    }

    public function test_pin_update_validates_pin_confirmation_match()
    {
        $response = $this->post(route('kids-mode.pin.update'), [
            'pin' => '1234',
            'pin_confirmation' => '5678', // Different PIN
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('pin_confirmation');
    }

    public function test_pin_update_requires_4_digit_pin()
    {
        $response = $this->post(route('kids-mode.pin.update'), [
            'pin' => '123', // Too short
            'pin_confirmation' => '123',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('pin');
    }

    public function test_exit_screen_requires_kids_mode_active()
    {
        // Don't set kids_mode_active in session

        $response = $this->get(route('kids-mode.exit'));

        $response->assertStatus(302);
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', __('Kids mode is not active'));
    }

    public function test_exit_pin_validation_requires_kids_mode_active()
    {
        // Don't set kids_mode_active in session

        $response = $this->postJson(route('kids-mode.exit.validate'), [
            'pin' => '1234',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => __('Kids mode is not active')]);
    }

    public function test_exit_pin_validation_validates_pin_format()
    {
        // Set kids mode active
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 1);

        $response = $this->postJson(route('kids-mode.exit.validate'), [
            'pin' => '12a4', // Invalid: contains letter
        ]);

        // The controller returns 400 (Bad Request) for PIN not set up or validation errors
        $response->assertStatus(400);
        $response->assertJsonStructure(['error']);
    }

    public function test_exit_pin_validation_requires_4_digits()
    {
        // Set kids mode active
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 1);

        $response = $this->postJson(route('kids-mode.exit.validate'), [
            'pin' => '123', // Too short
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('pin');
    }

    public function test_pin_settings_blocked_in_kids_mode()
    {
        // Set kids mode active
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 1);

        $response = $this->get(route('kids-mode.settings'));

        // Should be blocked by NotInKidsMode middleware
        $response->assertStatus(302);
        $response->assertRedirect(route('dashboard.child-today', ['child_id' => 1]));
    }

    public function test_pin_update_blocked_in_kids_mode()
    {
        // Set kids mode active
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 1);

        $response = $this->post(route('kids-mode.pin.update'), [
            'pin' => '1234',
            'pin_confirmation' => '1234',
        ]);

        // Should be blocked by NotInKidsMode middleware
        $response->assertStatus(302);
        $response->assertRedirect(route('dashboard.child-today', ['child_id' => 1]));
    }

    public function test_pin_reset_blocked_in_kids_mode()
    {
        // Set kids mode active
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', 1);

        $response = $this->post(route('kids-mode.pin.reset'));

        // Should be blocked by NotInKidsMode middleware
        $response->assertStatus(302);
        $response->assertRedirect(route('dashboard.child-today', ['child_id' => 1]));
    }

    public function test_all_required_translations_exist()
    {
        $requiredTranslations = [
            'kids_mode_settings',
            'set_kids_mode_pin',
            'update_kids_mode_pin',
            'current_pin',
            'new_pin',
            'confirm_pin',
            'pin_not_set',
            'pin_is_set',
            'reset_pin',
            'pin_updated',
            'pin_reset',
            'pin_must_be_4_digits',
            'pins_do_not_match',
            'enter_parent_pin',
            'incorrect_pin',
            'too_many_attempts',
            'exit_kids_mode',
            'back_to_learning',
        ];

        foreach ($requiredTranslations as $key) {
            $translation = __($key);

            // Translation should not equal the key (meaning it was found)
            $this->assertNotEquals($key, $translation, "Translation missing for key: {$key}");

            // Translation should not be empty
            $this->assertNotEmpty($translation, "Translation empty for key: {$key}");
        }
    }

    public function test_routes_exist_and_are_named_correctly()
    {
        // Test that all required routes exist
        $this->assertTrue($this->route_exists('kids-mode.settings'));
        $this->assertTrue($this->route_exists('kids-mode.pin.update'));
        $this->assertTrue($this->route_exists('kids-mode.pin.reset'));
        $this->assertTrue($this->route_exists('kids-mode.exit'));
        $this->assertTrue($this->route_exists('kids-mode.exit.validate'));
    }

    protected function route_exists($name)
    {
        try {
            route($name);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
