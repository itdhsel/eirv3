<?php

namespace Database\Seeders;

use App\Models\Ethnic;
use Illuminate\Database\Seeder;

class EthnicSeeder extends Seeder
{
    public function run(): void
    {
        $ethnics = [
            'Tiada Maklumat', 'Melayu', 'Bugis', 'Banjar', 'Jawa', 'Jawi Pekan',
            'Minangkabau', 'Cina', 'India', 'Malayali', 'Punjabi', 'Sikh', 'Tamil',
            'Telegu', 'India Muslim', 'Pakistani', 'Sri Lanka', 'Tamil Sri Lanka',
            'Melayu Sri Lanka', 'Sinhalese', 'Indonesian', 'Bumiputera Sabah',
            'Bajau', 'Dusun', 'Murut'
        ];

        foreach ($ethnics as $ethnic) {
            Ethnic::firstOrCreate(['name' => $ethnic]);
        }
    }
}