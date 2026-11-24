<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResetPasswordCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_is_reset_for_an_existing_account(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->artisan('cashiko:reset-password', ['email' => $user->email])
            ->expectsQuestion('New password (at least 8 characters)', 'new-password-123')
            ->expectsQuestion('Type the new password again', 'new-password-123')
            ->assertExitCode(0);

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertFalse(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_email_match_is_case_insensitive(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->artisan('cashiko:reset-password', ['email' => mb_strtoupper($user->email)])
            ->expectsQuestion('New password (at least 8 characters)', 'new-password-123')
            ->expectsQuestion('Type the new password again', 'new-password-123')
            ->assertExitCode(0);

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_unknown_email_is_rejected(): void
    {
        $this->artisan('cashiko:reset-password', ['email' => 'nobody@example.com'])
            ->assertExitCode(1);
    }

    public function test_short_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->artisan('cashiko:reset-password', ['email' => $user->email])
            ->expectsQuestion('New password (at least 8 characters)', 'short')
            ->assertExitCode(1);

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_mismatched_confirmation_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->artisan('cashiko:reset-password', ['email' => $user->email])
            ->expectsQuestion('New password (at least 8 characters)', 'new-password-123')
            ->expectsQuestion('Type the new password again', 'something-else-123')
            ->assertExitCode(1);

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}
