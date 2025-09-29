<?php

declare(strict_types=1);

namespace Tests\Support\Concerns;

use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait UsesInMemoryDatabase
{
    /**
     * Configure an isolated in-memory SQLite database and prepare baseline tables used across
     * the legacy application (settings/languages). Callers may provide an optional callback to
     * register additional tables required for a specific test case.
     */
    protected function useInMemoryDatabase(?Closure $additionalSchema = null): void
    {
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $this->createSettingsTable();
        $this->createLanguagesTable();
        $this->createLanguagePhrasesTable();
        $this->seedLanguageDefaults();

        if ($additionalSchema) {
            $additionalSchema();
        }
    }

    protected function createSettingsTable(): void
    {
        Schema::dropIfExists('settings');

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    protected function createLanguagesTable(): void
    {
        Schema::dropIfExists('languages');

        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function createLanguagePhrasesTable(): void
    {
        Schema::dropIfExists('language_phrases');

        Schema::create('language_phrases', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('language_id');
            $table->string('phrase');
            $table->text('translated')->nullable();
        });
    }

    protected function seedLanguageDefaults(): void
    {
        DB::table('languages')->delete();
        DB::table('settings')->delete();

        DB::table('languages')->insert([
            'id' => 1,
            'name' => 'english',
        ]);

        DB::table('settings')->insert([
            'type' => 'language',
            'description' => 'english',
        ]);
    }
}
