<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Password recovery for deployments with no working mail transport (Render has
 * no MAIL_* variables, so /forgot-password writes the reset mail to the log).
 *
 * Run it from the Render dashboard's Shell tab:
 *     php artisan cashiko:reset-password you@example.com
 *
 * The password is read from a hidden prompt, hashed by the User model's
 * `hashed` cast, and never echoed, logged or passed as an argument.
 */
class ResetPassword extends Command
{
    protected $signature = 'cashiko:reset-password
                            {email : Email address of the account to unlock}';

    protected $description = 'Set a new password for an existing account (shell access required)';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));

        $user = User::whereRaw('lower(email) = ?', [$email])->first();

        if (! $user) {
            $this->error("No account exists for {$email}.");

            return self::FAILURE;
        }

        $password = $this->secret('New password (at least 8 characters)');

        if (! is_string($password) || strlen($password) < 8) {
            $this->error('Aborted: the password must be at least 8 characters.');

            return self::FAILURE;
        }

        if ($password !== $this->secret('Type the new password again')) {
            $this->error('Aborted: the passwords do not match.');

            return self::FAILURE;
        }

        // `password` is cast as hashed, so the plain value is hashed on
        // assignment. Rotating remember_token signs out other devices.
        $user->forceFill([
            'password' => $password,
            'remember_token' => Str::random(60),
        ])->save();

        $this->newLine();
        $this->info("Password updated for {$user->email}.");

        return self::SUCCESS;
    }
}
