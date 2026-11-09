<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed a working installation: one account per role, the demonstration
     * registry, and the three evaluation registries.
     *
     * Every seeder is idempotent, so `php artisan db:seed` is safe to run
     * against a database that already holds data.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            FeatureRegistrySeeder::class,
            EvaluationRegistrySeeder::class,
        ]);
    }
}
