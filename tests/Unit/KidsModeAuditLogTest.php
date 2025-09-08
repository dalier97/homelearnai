<?php

namespace Tests\Unit;

use App\Models\KidsModeAuditLog;
use App\Services\SupabaseClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class KidsModeAuditLogTest extends TestCase
{
    private $userId;

    private $childId;

    private $ipAddress;

    private $userAgent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userId = 'test-user-'.uniqid();
        $this->childId = 123;
        $this->ipAddress = '192.168.1.100';
        $this->userAgent = 'Mozilla/5.0 (Test Browser)';
    }

    /** @test */
    public function it_can_log_security_events()
    {
        $insertedData = null;

        // Mock SupabaseClient with flexible return type
        $this->mock(SupabaseClient::class, function ($mock) use (&$insertedData) {
            $mock->shouldReceive('setServiceKey')->once();

            // Create a mock object that has both from() and insert() methods
            $mockQueryBuilder = \Mockery::mock();
            $mockQueryBuilder->shouldReceive('insert')
                ->once()
                ->andReturnUsing(function ($data) use (&$insertedData) {
                    $insertedData = $data;

                    return true;
                });

            $mock->shouldReceive('from')->with('kids_mode_audit_logs')->andReturn($mockQueryBuilder);
        });

        $metadata = [
            'session_duration_minutes' => 45,
            'previous_failed_attempts' => 2,
        ];

        KidsModeAuditLog::logEvent(
            'exit_success',
            $this->userId,
            $this->childId,
            $this->ipAddress,
            $this->userAgent,
            $metadata
        );

        // Verify the correct data was logged
        $this->assertNotNull($insertedData);
        $this->assertEquals('exit_success', $insertedData['action']);
        $this->assertEquals($this->userId, $insertedData['user_id']);
        $this->assertEquals($this->childId, $insertedData['child_id']);
        $this->assertEquals($this->ipAddress, $insertedData['ip_address']);
        $this->assertEquals($this->userAgent, $insertedData['user_agent']);
        $this->assertEquals(json_encode($metadata), $insertedData['metadata']);
        $this->assertNotNull($insertedData['created_at']);
        $this->assertNotNull($insertedData['updated_at']);
    }

    /** @test */
    public function it_handles_null_values_gracefully()
    {
        $insertedData = null;

        $this->mock(SupabaseClient::class, function ($mock) use (&$insertedData) {
            $mock->shouldReceive('setServiceKey')->once();
            $mock->shouldReceive('from')->with('kids_mode_audit_logs')->andReturn($queryBuilder);
            $mock->shouldReceive('insert')
                ->once()
                ->andReturnUsing(function ($data) use (&$insertedData) {
                    $insertedData = $data;

                    return true;
                });
        });

        KidsModeAuditLog::logEvent(
            'pin_failed',
            $this->userId,
            null, // No child ID
            null, // No IP address
            null, // No user agent
            []    // Empty metadata
        );

        $this->assertEquals('pin_failed', $insertedData['action']);
        $this->assertEquals($this->userId, $insertedData['user_id']);
        $this->assertNull($insertedData['child_id']);
        $this->assertNull($insertedData['ip_address']);
        $this->assertNull($insertedData['user_agent']);
        $this->assertNull($insertedData['metadata']);
    }

    /** @test */
    public function it_handles_database_errors_without_failing()
    {
        // Mock SupabaseClient to throw an exception
        $this->mock(SupabaseClient::class, function ($mock) {
            $mock->shouldReceive('setServiceKey')->once();
            $mock->shouldReceive('from')->with('kids_mode_audit_logs')->andReturn($queryBuilder);
            $mock->shouldReceive('insert')->once()->andThrow(new \Exception('Database error'));
        });

        // Mock Log to verify error is logged
        Log::shouldReceive('error')
            ->once()
            ->with('Failed to create kids mode audit log', \Mockery::type('array'));

        // This should not throw an exception
        KidsModeAuditLog::logEvent(
            'test_event',
            $this->userId,
            $this->childId,
            $this->ipAddress,
            $this->userAgent,
            ['test' => 'data']
        );

        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_retrieve_user_audit_logs()
    {
        $mockLogs = [
            [
                'id' => 1,
                'user_id' => $this->userId,
                'child_id' => $this->childId,
                'action' => 'enter',
                'ip_address' => $this->ipAddress,
                'user_agent' => $this->userAgent,
                'metadata' => '{"child_name":"Test Child"}',
                'created_at' => now()->toISOString(),
            ],
            [
                'id' => 2,
                'user_id' => $this->userId,
                'child_id' => $this->childId,
                'action' => 'exit_success',
                'ip_address' => $this->ipAddress,
                'user_agent' => $this->userAgent,
                'metadata' => '{"session_duration_minutes":30}',
                'created_at' => now()->subHour()->toISOString(),
            ],
        ];

        $this->mock(SupabaseClient::class, function ($mock) use ($mockLogs) {
            $mock->shouldReceive('setServiceKey')->once();
            $mock->shouldReceive('from')->with('kids_mode_audit_logs')->andReturn($queryBuilder);
            $mock->shouldReceive('select')->with('*')->andReturnSelf();
            $mock->shouldReceive('eq')->with('user_id', $this->userId)->andReturnSelf();
            $mock->shouldReceive('order')->with('created_at', 'desc')->andReturnSelf();
            $mock->shouldReceive('limit')->with(50)->andReturnSelf();
            $mock->shouldReceive('get')->once()->andReturn($mockLogs);
        });

        $logs = KidsModeAuditLog::forUser($this->userId);

        $this->assertCount(2, $logs);
        $this->assertEquals('enter', $logs[0]['action']);
        $this->assertEquals('exit_success', $logs[1]['action']);
    }

    /** @test */
    public function it_can_count_recent_failed_attempts()
    {
        $mockFailedLogs = [
            ['id' => 1], ['id' => 2], ['id' => 3], // 3 failed attempts
        ];

        $this->mock(SupabaseClient::class, function ($mock) use ($mockFailedLogs) {
            $mock->shouldReceive('setServiceKey')->once();
            $mock->shouldReceive('from')->with('kids_mode_audit_logs')->andReturn($queryBuilder);
            $mock->shouldReceive('select')->with('id')->andReturnSelf();
            $mock->shouldReceive('eq')->with('user_id', $this->userId)->andReturnSelf();
            $mock->shouldReceive('eq')->with('action', 'pin_failed')->andReturnSelf();
            $mock->shouldReceive('gte')->with('created_at', \Mockery::type('string'))->andReturnSelf();
            $mock->shouldReceive('get')->once()->andReturn($mockFailedLogs);
        });

        $count = KidsModeAuditLog::getRecentFailedAttempts($this->userId, 60);

        $this->assertEquals(3, $count);
    }

    /** @test */
    public function it_can_count_failed_attempts_by_ip()
    {
        $mockIpLogs = [
            ['id' => 1], ['id' => 2], // 2 failed attempts from IP
        ];

        $this->mock(SupabaseClient::class, function ($mock) use ($mockIpLogs) {
            $mock->shouldReceive('setServiceKey')->once();
            $mock->shouldReceive('from')->with('kids_mode_audit_logs')->andReturn($queryBuilder);
            $mock->shouldReceive('select')->with('id')->andReturnSelf();
            $mock->shouldReceive('eq')->with('ip_address', $this->ipAddress)->andReturnSelf();
            $mock->shouldReceive('eq')->with('action', 'pin_failed')->andReturnSelf();
            $mock->shouldReceive('gte')->with('created_at', \Mockery::type('string'))->andReturnSelf();
            $mock->shouldReceive('get')->once()->andReturn($mockIpLogs);
        });

        $count = KidsModeAuditLog::getFailedAttemptsByIP($this->ipAddress, 60);

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function it_handles_query_errors_gracefully()
    {
        // Mock SupabaseClient to throw an exception on query
        $this->mock(SupabaseClient::class, function ($mock) {
            $mock->shouldReceive('setServiceKey')->once();
            $mock->shouldReceive('from')->with('kids_mode_audit_logs')->andReturn($queryBuilder);
            $mock->shouldReceive('select')->andReturnSelf();
            $mock->shouldReceive('eq')->andReturnSelf();
            $mock->shouldReceive('order')->andReturnSelf();
            $mock->shouldReceive('limit')->andReturnSelf();
            $mock->shouldReceive('get')->once()->andThrow(new \Exception('Query error'));
        });

        // Mock Log to verify error is logged
        Log::shouldReceive('error')
            ->once()
            ->with('Failed to fetch kids mode audit logs', \Mockery::type('array'));

        $logs = KidsModeAuditLog::forUser($this->userId);

        // Should return empty array on error
        $this->assertEquals([], $logs);
    }

    /** @test */
    public function it_validates_time_ranges_for_failed_attempts()
    {
        $this->mock(SupabaseClient::class, function ($mock) {
            $mock->shouldReceive('setServiceKey')->once();
            $mock->shouldReceive('from')->with('kids_mode_audit_logs')->andReturn($queryBuilder);
            $mock->shouldReceive('select')->with('id')->andReturnSelf();
            $mock->shouldReceive('eq')->with('user_id', $this->userId)->andReturnSelf();
            $mock->shouldReceive('eq')->with('action', 'pin_failed')->andReturnSelf();
            $mock->shouldReceive('gte')
                ->with('created_at', \Mockery::on(function ($timestamp) {
                    // Verify the timestamp is approximately 30 minutes ago
                    $expected = Carbon::now()->subMinutes(30);
                    $actual = Carbon::parse($timestamp);

                    return $actual->diffInMinutes($expected) <= 1;
                }))
                ->andReturnSelf();
            $mock->shouldReceive('get')->once()->andReturn([]);
        });

        KidsModeAuditLog::getRecentFailedAttempts($this->userId, 30);
    }

    /** @test */
    public function it_properly_encodes_metadata()
    {
        $insertedData = null;

        $this->mock(SupabaseClient::class, function ($mock) use (&$insertedData) {
            $mock->shouldReceive('setServiceKey')->once();
            $mock->shouldReceive('from')->with('kids_mode_audit_logs')->andReturn($queryBuilder);
            $mock->shouldReceive('insert')
                ->once()
                ->andReturnUsing(function ($data) use (&$insertedData) {
                    $insertedData = $data;

                    return true;
                });
        });

        $complexMetadata = [
            'attempts' => 5,
            'locked' => true,
            'lockout_minutes' => 15,
            'user_failed_attempts_1h' => 3,
            'ip_failed_attempts_1h' => 8,
            'nested_data' => [
                'browser' => 'Chrome',
                'version' => '120.0.0.0',
            ],
        ];

        KidsModeAuditLog::logEvent(
            'pin_failed',
            $this->userId,
            $this->childId,
            $this->ipAddress,
            $this->userAgent,
            $complexMetadata
        );

        $this->assertEquals(json_encode($complexMetadata), $insertedData['metadata']);
        $this->assertTrue(is_string($insertedData['metadata']));
    }
}
