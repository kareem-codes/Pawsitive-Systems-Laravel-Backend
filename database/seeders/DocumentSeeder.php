<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Pet;
use App\Models\MedicalRecord;
use App\Models\Vaccination;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some pets, medical records, and vaccinations
        $pets = Pet::take(5)->get();
        $medicalRecords = MedicalRecord::take(5)->get();
        $vaccinations = Vaccination::take(5)->get();

        // Create documents for pets
        foreach ($pets as $pet) {
            Document::factory()
                ->count(rand(1, 3))
                ->create([
                    'documentable_type' => Pet::class,
                    'documentable_id' => $pet->id,
                ]);
        }

        // Create documents for medical records
        foreach ($medicalRecords as $record) {
            Document::factory()
                ->count(rand(1, 2))
                ->forMedicalRecord()
                ->create([
                    'documentable_id' => $record->id,
                ]);
        }

        // Create documents for vaccinations
        foreach ($vaccinations as $vaccination) {
            Document::factory()
                ->forVaccination()
                ->create([
                    'documentable_id' => $vaccination->id,
                ]);
        }
    }
}

