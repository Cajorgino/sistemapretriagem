<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Demo dashboard (gestantes + consultas, telefones fictícios): php artisan db:seed --class=PresentationDataSeeder
        // Só consultas para gestantes já cadastradas sem consultas: php artisan db:seed --class=DemoConsultasForGestantesSeeder

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
