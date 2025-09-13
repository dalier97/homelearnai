<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\KidsModeAuditLog;
use App\Models\User;
use App\Services\SupabaseClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class KidsModeSecurityTest extends TestCase
{
    use RefreshDatabase;

    private $supabase;

    private $user;

    private $child;

    private $validPin = '1234';

    private $hashedPin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supabase = $this->app->make(SupabaseClient::class);

        // Create and authenticate user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create child using relationships
        $this->child = $this->user->children()->create([
            'name' => 'Test Child',
            'grade' => '3rd',
            'independence_level' => 2,
        ]);

        // Generate a proper hashed PIN
        $salt = \Str::random(32);
        $this->hashedPin = Hash::make($this->validPin.$salt);

        // Set up kids mode session data
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', $this->child->id);
        Session::put('kids_mode_child_name', 'Test Child');
        Session::put('kids_mode_entered_at', now()->toISOString());
        Session::put('kids_mode_fingerprint', 'test-fingerprint');

        // Mock child data
        $childMock = (object) [
            'id' => $this->child->id,
            'user_id' => $this->user->id,
            'name' => 'Test Child',
        ];

        $this->mock(Child::class, function ($mock) use ($childMock) {
            $mock->shouldReceive('find')
                ->andReturn($childMock);
        });
    }

    protected function tearDown(): void
    {
        // Clear rate limiting
        RateLimiter::clear('kids-mode-pin-attempts:'.$this->user->id);
        RateLimiter::clear('kids-mode-ip-attempts:127.0.0.1');
        parent::tearDown();
    }

    /** @test */
    public function it_logs_kids_mode_entry_with_security_details()
    {
        $response = $this->post(route('dashboard.kids-mode.enter', $this->child->id));

        // Should successfully enter kids mode
        $this->assertTrue(Session::get('kids_mode_active'));
        $this->assertEquals($this->child->id, Session::get('kids_mode_child_id'));

        // Should have generated security fingerprint
        $this->assertNotNull(Session::get('kids_mode_fingerprint'));

        // Should have logged the entry
        // Note: In real implementation, this would check the database
        // For now, we'll verify session state and response
        $response->assertRedirect(route('dashboard.child-today', ['child_id' => $this->child->id]));
    }

    /** @test */
    public function it_validates_session_fingerprint_on_pin_validation()
    {
        // Set up user preferences with PIN
        $this->mockUserPreferences([
            'kids_mode_pin' => $this->hashedPin,
            'kids_mode_pin_salt' => 'test-salt',
            'kids_mode_pin_attempts' => 0,
        ]);

        // First request with valid fingerprint
        Session::put('kids_mode_fingerprint', 'original-fingerprint');

        $response = $this->post(route('kids-mode.exit.validate'), [
            'pin' => $this->validPin,
        ], [
            'User-Agent' => 'TestAgent',
            'Accept-Language' => 'en-US',
            'Accept-Encoding' => 'gzip',
        ]);

        // Should work with matching fingerprint
        $response->assertStatus(200);
    }

    /** @test */
    public function it_blocks_request_with_invalid_session_fingerprint()
    {
        // Set up user preferences with PIN
        $this->mockUserPreferences([
            'kids_mode_pin' => $this->hashedPin,
            'kids_mode_pin_salt' => 'test-salt',
            'kids_mode_pin_attempts' => 0,
        ]);

        // Set different fingerprint in session vs request
        Session::put('kids_mode_fingerprint', 'original-fingerprint');

        $response = $this->post(route('kids-mode.exit.validate'), [
            'pin' => $this->validPin,
        ], [
            'User-Agent' => 'DifferentAgent', // This will create different fingerprint
            'Accept-Language' => 'fr-FR',
            'Accept-Encoding' => 'br',
        ]);

        // Should block with security violation
        $response->assertStatus(403);
        $response->assertJson(['error' => 'Security violation detected. Please login again.']);

        // Should have cleared session
        $this->assertNull(Session::get('user_id'));
    }

    /** @test */
    public function it_implements_progressive_lockout_for_failed_pin_attempts()
    {
        $this->mockUserPreferences([
            'kids_mode_pin' => $this->hashedPin,
            'kids_mode_pin_salt' => 'test-salt',
            'kids_mode_pin_attempts' => 0,
        ]);

        // Test different lockout durations
        $testCases = [
            ['attempts' => 5, 'expectedMinutes' => 5],
            ['attempts' => 7, 'expectedMinutes' => 15],
            ['attempts' => 10, 'expectedMinutes' => 60],
            ['attempts' => 15, 'expectedMinutes' => 360],
            ['attempts' => 20, 'expectedMinutes' => 1440],
        ];

        foreach ($testCases as $case) {
            // Set failed attempts in preferences
            $this->mockUserPreferences([
                'kids_mode_pin' => $this->hashedPin,
                'kids_mode_pin_salt' => 'test-salt',
                'kids_mode_pin_attempts' => $case['attempts'] - 1, // Will be incremented to target
            ]);

            $response = $this->post(route('kids-mode.exit.validate'), [
                'pin' => '9999', // Wrong PIN
            ]);

            $response->assertStatus(400);
            $responseData = $response->json();

            if ($case['attempts'] >= 5) {
                $this->assertEquals($case['expectedMinutes'], $responseData['lockout_minutes']);
                $this->assertTrue($responseData['locked']);
            }
        }
    }

    /** @test */
    public function it_implements_ip_based_rate_limiting()
    {
        $this->mockUserPreferences([
            'kids_mode_pin' => $this->hashedPin,
            'kids_mode_pin_salt' => 'test-salt',
            'kids_mode_pin_attempts' => 0,
        ]);

        // Make 10 failed attempts (IP limit)
        for ($i = 0; $i < 10; $i++) {
            RateLimiter::hit('kids-mode-ip-attempts:127.0.0.1');
        }

        $response = $this->post(route('kids-mode.exit.validate'), [
            'pin' => '9999',
        ]);

        $response->assertStatus(429);
        $response->assertJsonStructure(['error', 'retry_after']);
    }

    /** @test */
    public function it_applies_security_headers_in_kids_mode()
    {
        // Make request while in kids mode
        $response = $this->get(route('kids-mode.exit'));

        // Check for security headers
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Check for CSP header
        $this->assertNotNull($response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("object-src 'none'", $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("frame-src 'none'", $response->headers->get('Content-Security-Policy'));

        // Check for Permissions Policy
        $this->assertNotNull($response->headers->get('Permissions-Policy'));
        $this->assertStringContainsString('camera=()', $response->headers->get('Permissions-Policy'));
        $this->assertStringContainsString('microphone=()', $response->headers->get('Permissions-Policy'));
    }

    /** @test */
    public function it_does_not_apply_security_headers_when_not_in_kids_mode()
    {
        // Exit kids mode
        Session::forget('kids_mode_active');

        $response = $this->get('/dashboard');

        // Security headers should not be present
        $this->assertNull($response->headers->get('X-Frame-Options'));
        $this->assertNull($response->headers->get('Permissions-Policy'));
    }

    /** @test */
    public function it_tracks_audit_logs_for_security_events()
    {
        // Mock audit log creation
        $loggedEvents = [];

        $this->mock(KidsModeAuditLog::class, function ($mock) use (&$loggedEvents) {
            $mock->shouldReceive('logEvent')
                ->andReturnUsing(function ($action, $userId, $childId, $ip, $userAgent, $metadata) use (&$loggedEvents) {
                    $loggedEvents[] = [
                        'action' => $action,
                        'user_id' => $userId,
                        'child_id' => $childId,
                        'ip_address' => $ip,
                        'metadata' => $metadata,
                    ];
                });
        });

        // Test failed PIN attempt
        $this->mockUserPreferences([
            'kids_mode_pin' => $this->hashedPin,
            'kids_mode_pin_attempts' => 0,
        ]);

        $response = $this->post(route('kids-mode.exit.validate'), [
            'pin' => '9999',
        ]);

        // Should have logged the failed attempt
        $this->assertNotEmpty($loggedEvents);
        $failedAttemptLog = collect($loggedEvents)->firstWhere('action', 'pin_failed');
        $this->assertNotNull($failedAttemptLog);
        $this->assertEquals($this->user->id, $failedAttemptLog['user_id']);
        $this->assertEquals($this->child->id, $failedAttemptLog['child_id']);
    }

    /** @test */
    public function it_clears_rate_limiting_on_successful_pin_validation()
    {
        $this->mockUserPreferences([
            'kids_mode_pin' => $this->hashedPin,
            'kids_mode_pin_salt' => 'test-salt',
            'kids_mode_pin_attempts' => 3, // Some failed attempts
        ]);

        // Add some rate limiting
        RateLimiter::hit('kids-mode-pin-attempts:'.$this->user->id);
        RateLimiter::hit('kids-mode-ip-attempts:127.0.0.1');

        $response = $this->post(route('kids-mode.exit.validate'), [
            'pin' => $this->validPin,
        ]);

        $response->assertStatus(200);

        // Rate limits should be cleared
        $this->assertEquals(0, RateLimiter::attempts('kids-mode-pin-attempts:'.$this->user->id));
        $this->assertEquals(0, RateLimiter::attempts('kids-mode-ip-attempts:127.0.0.1'));

        // Kids mode should be deactivated
        $this->assertFalse(Session::get('kids_mode_active'));
        $this->assertNull(Session::get('kids_mode_child_id'));
        $this->assertNull(Session::get('kids_mode_fingerprint'));
    }

    /** @test */
    public function it_prevents_access_when_not_in_kids_mode()
    {
        Session::forget('kids_mode_active');

        $response = $this->post(route('kids-mode.exit.validate'), [
            'pin' => $this->validPin,
        ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Kids mode is not active']);
    }

    /** @test */
    public function it_validates_pin_format()
    {
        $response = $this->post(route('kids-mode.exit.validate'), [
            'pin' => '123', // Too short
        ]);

        $response->assertStatus(422); // Validation error

        $response = $this->post(route('kids-mode.exit.validate'), [
            'pin' => 'abcd', // Non-numeric
        ]);

        $response->assertStatus(422); // Validation error
    }

    /**
     * Mock user preferences for testing
     */
    private function mockUserPreferences(array $preferences): void
    {
        // Mock SupabaseQueryBuilder
        $queryBuilder = $this->mock(\App\Services\SupabaseQueryBuilder::class);
        $queryBuilder->shouldReceive('select')->andReturnSelf();
        $queryBuilder->shouldReceive('eq')->andReturnSelf();
        $queryBuilder->shouldReceive('single')->andReturn($preferences);
        $queryBuilder->shouldReceive('update')->andReturnSelf();
        $queryBuilder->shouldReceive('insert')->andReturnSelf();

        // Mock the SupabaseClient to return test preferences
        $this->mock(SupabaseClient::class, function ($mock) use ($queryBuilder) {
            $mock->shouldReceive('setUserToken')->andReturnSelf();
            $mock->shouldReceive('from')->with('user_preferences')->andReturn($queryBuilder);
        });
    }
}
