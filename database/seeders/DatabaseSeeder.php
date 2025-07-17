<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'telegram_id' => '546980644',
            'language' => "O'zbekcha",
            'full_name' => 'Admin',
        ]);
        User::create([
            'telegram_id' => '898426931',
            'language' => "O'zbekcha",
            'full_name' => 'Admin',
        ]);
        User::create([
            'telegram_id' => '51372095',
            'language' => "O'zbekcha",
            'full_name' => 'Admin',
        ]);
    }
}
