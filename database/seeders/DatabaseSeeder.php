<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Vaste dev-login, gelijk op al onze sites. Het bekende wachtwoord
        // mag nooit op productie staan.
        User::updateOrCreate(
            ['email' => 'admin@example.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make(app()->isProduction() ? Str::password(48) : 'password'),
                'email_verified_at' => now(),
            ],
        );
    }
}
