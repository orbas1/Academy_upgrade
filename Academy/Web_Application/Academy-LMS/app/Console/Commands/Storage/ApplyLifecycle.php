<?php

namespace App\Console\Commands\Storage;

use App\Services\Storage\LifecycleManager;
use Illuminate\Console\Command;

class ApplyLifecycle extends Command
{
    protected $signature = 'storage:lifecycle {profile=media : The lifecycle profile defined in storage_lifecycle.php}';

    protected $description = 'Applies the configured S3 lifecycle policy to the target bucket.';

    public function __construct(private readonly LifecycleManager $lifecycleManager)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $profile = $this->argument('profile');

        $configuration = $this->lifecycleManager->apply($profile);

        $this->info(sprintf('Lifecycle policy applied to profile [%s].', $profile));
        $this->line(json_encode($configuration, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
