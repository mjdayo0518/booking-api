<?php

namespace Database\Seeders;

use App\Models\Resource;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Resource::create(['name' => 'Meeting Room A', 'type' => 'room']);
        Resource::create(['name' => 'Meeting Room B', 'type' => 'room']);
        Resource::create(['name' => 'Projector', 'type' => 'equipment']);
    }
}
