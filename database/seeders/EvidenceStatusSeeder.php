<?php

namespace Database\Seeders;

use App\Models\EvidenceStatus;
use Illuminate\Database\Seeder;

class EvidenceStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EvidenceStatus::updateOrCreate(
            ['id' => EvidenceStatus::REGISTRADA],
            ['name' => 'Registrada']
        );
    }
}
