<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Security\DataPrivacyService;
use Illuminate\Console\Command;

class ExportUserData extends Command
{
    protected $signature = 'compliance:export-user {user : The user ID or email address} {--path=}';

    protected $description = "Generate an encrypted compliance export for the given user";

    public function handle(DataPrivacyService $privacyService): int
    {
        $identifier = $this->argument('user');
        $user = $this->resolveUser($identifier);

        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $path = $privacyService->exportToDisk($user, $this->option('path'));

        $this->info(sprintf('Export for %s stored at %s', $user->email, $path));

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
