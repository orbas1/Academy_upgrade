<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Security\DataPrivacyService;
use Illuminate\Console\Command;

class EraseUserData extends Command
{
    protected $signature = 'compliance:erase-user {user : The user ID or email address} {--force : Skip confirmation prompt}';

    protected $description = 'Anonymize and revoke a user\'s personal data per GDPR erasure request';

    public function handle(DataPrivacyService $privacyService): int
    {
        $identifier = $this->argument('user');
        $user = $this->resolveUser($identifier);

        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm(sprintf('Erase personal data for %s (%d)?', $user->email, $user->id))) {
            $this->info('Operation cancelled.');

            return self::INVALID;
        }

        $privacyService->erase($user);

        $this->info(sprintf('Personal data for user %d has been anonymized.', $user->id));

        return self::SUCCESS;
    }

    private function resolveUser(string $identifier): ?User
    {
        $query = User::query();

        if (is_numeric($identifier)) {
            return $query->whereKey((int) $identifier)->first();
        }

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return $query->where('email', $identifier)->first();
        }

        return null;
    }
}
