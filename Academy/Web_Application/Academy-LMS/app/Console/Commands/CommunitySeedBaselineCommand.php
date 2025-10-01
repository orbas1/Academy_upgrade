<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\Communities\CommunityFoundationSeeder;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class CommunitySeedBaselineCommand extends Command
{
    protected $signature = 'community:seed-baseline {--force : Run without interactive confirmation}';

    protected $description = 'Seed baseline community categories, levels, and points rules.';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Seed baseline community configuration?')) {
            $this->comment('Command aborted.');

            return self::FAILURE;
        }

        /** @var Seeder $seeder */
        $seeder = App::make(CommunityFoundationSeeder::class);
        $seeder->run();

        $this->info('Baseline community categories, levels, and points rules have been seeded.');

        return self::SUCCESS;
    }
}
