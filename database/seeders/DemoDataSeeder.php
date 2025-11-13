<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Pet;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Vaccination;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\WeightRecord;
use App\Models\CommunicationLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // Ensure roles exist (in case RolePermissionSeeder hasn't been run)
            $this->ensureRolesExist();

            // Create 3 veterinarians
            $vets = collect();
            $vetNames = [
                ['first_name' => 'Sarah', 'last_name' => 'Johnson', 'email' => 'vet1@pawsitive.com'],
                ['first_name' => 'Michael', 'last_name' => 'Chen', 'email' => 'vet2@pawsitive.com'],
                ['first_name' => 'Emily', 'last_name' => 'Rodriguez', 'email' => 'vet3@pawsitive.com'],
            ];

            foreach ($vetNames as $vetData) {
                $vet = User::factory()->veterinarian()->create([
                    'email' => $vetData['email'],
                    'password' => Hash::make('password'),
                    'first_name' => $vetData['first_name'],
                    'last_name' => $vetData['last_name'],
                    'name' => $vetData['first_name'] . ' ' . $vetData['last_name'],
                    'phone' => fake()->phoneNumber(),
                    'is_active' => true,
                ]);
                $vet->assignRole('veterinarian');
                $vets->push($vet);
            }

            // Create 2 receptionists
            $receptionist1 = User::factory()->receptionist()->create([
                'email' => 'receptionist@pawsitive.com',
                'password' => Hash::make('password'),
                'first_name' => 'Lisa',
                'last_name' => 'Brown',
                'name' => 'Lisa Brown',
                'phone' => fake()->phoneNumber(),
                'is_active' => true,
            ]);
            $receptionist1->assignRole('receptionist');

            $receptionist2 = User::factory()->receptionist()->create([
                'email' => 'receptionist2@pawsitive.com',
                'password' => Hash::make('password'),
                'first_name' => 'Karen',
                'last_name' => 'Martinez',
                'name' => 'Karen Martinez',
                'phone' => fake()->phoneNumber(),
                'is_active' => true,
            ]);
            $receptionist2->assignRole('receptionist');

            // Create cashier
            $cashier = User::factory()->cashier()->create([
                'email' => 'cashier@pawsitive.com',
                'password' => Hash::make('password'),
                'first_name' => 'David',
                'last_name' => 'Wilson',
                'name' => 'David Wilson',
                'phone' => fake()->phoneNumber(),
                'is_active' => true,
            ]);
            $cashier->assignRole('cashier');

            // Create products - using only valid categories from the enum
            $products = collect();
            
            // Medications and products (valid categories: food, medicine, accessories, toys, grooming, other)
            $productData = [
                // Medicines
                ['name' => 'Antibiotics - 30 tablets', 'category' => 'medicine', 'price' => 35.00, 'stock' => 50],
                ['name' => 'Pain Relief - 20 tablets', 'category' => 'medicine', 'price' => 25.00, 'stock' => 40],
                ['name' => 'Flea & Tick Prevention', 'category' => 'medicine', 'price' => 45.00, 'stock' => 30],
                ['name' => 'Heartworm Prevention', 'category' => 'medicine', 'price' => 40.00, 'stock' => 35],
                ['name' => 'Eye Drops', 'category' => 'medicine', 'price' => 18.00, 'stock' => 25],
                ['name' => 'Ear Infection Treatment', 'category' => 'medicine', 'price' => 22.00, 'stock' => 30],
                
                // Food
                ['name' => 'Premium Dog Food - 15kg', 'category' => 'food', 'price' => 65.00, 'stock' => 20],
                ['name' => 'Premium Cat Food - 10kg', 'category' => 'food', 'price' => 55.00, 'stock' => 25],
                ['name' => 'Puppy Food - 5kg', 'category' => 'food', 'price' => 35.00, 'stock' => 18],
                ['name' => 'Senior Dog Food - 10kg', 'category' => 'food', 'price' => 58.00, 'stock' => 15],
                ['name' => 'Kitten Food - 3kg', 'category' => 'food', 'price' => 28.00, 'stock' => 22],
                
                // Toys
                ['name' => 'Dog Toy - Rubber Ball', 'category' => 'toys', 'price' => 12.00, 'stock' => 60],
                ['name' => 'Interactive Cat Toy', 'category' => 'toys', 'price' => 15.00, 'stock' => 45],
                ['name' => 'Chew Bone - Large', 'category' => 'toys', 'price' => 8.00, 'stock' => 50],
                ['name' => 'Rope Toy', 'category' => 'toys', 'price' => 10.00, 'stock' => 40],
                
                // Accessories
                ['name' => 'Cat Scratching Post', 'category' => 'accessories', 'price' => 35.00, 'stock' => 15],
                ['name' => 'Dog Collar - Medium', 'category' => 'accessories', 'price' => 18.00, 'stock' => 35],
                ['name' => 'Leash - 6ft', 'category' => 'accessories', 'price' => 22.00, 'stock' => 30],
                ['name' => 'Pet Carrier - Small', 'category' => 'accessories', 'price' => 45.00, 'stock' => 12],
                ['name' => 'Food Bowl Set', 'category' => 'accessories', 'price' => 16.00, 'stock' => 28],
                
                // Grooming
                ['name' => 'Pet Shampoo', 'category' => 'grooming', 'price' => 18.00, 'stock' => 45],
                ['name' => 'Nail Clippers', 'category' => 'grooming', 'price' => 15.00, 'stock' => 30],
                ['name' => 'Brush - Slicker', 'category' => 'grooming', 'price' => 12.00, 'stock' => 35],
                ['name' => 'Dental Care Kit', 'category' => 'grooming', 'price' => 25.00, 'stock' => 20],
                
                // Other (medical services/procedures)
                ['name' => 'General Checkup Fee', 'category' => 'other', 'price' => 50.00, 'stock' => 0, 'track' => false],
                ['name' => 'Vaccination Administration', 'category' => 'other', 'price' => 25.00, 'stock' => 0, 'track' => false],
                ['name' => 'Dental Cleaning Service', 'category' => 'other', 'price' => 120.00, 'stock' => 0, 'track' => false],
                ['name' => 'X-Ray Service', 'category' => 'other', 'price' => 75.00, 'stock' => 0, 'track' => false],
                ['name' => 'Blood Test Service', 'category' => 'other', 'price' => 60.00, 'stock' => 0, 'track' => false],
                ['name' => 'Emergency Visit Fee', 'category' => 'other', 'price' => 150.00, 'stock' => 0, 'track' => false],
                ['name' => 'Surgery Fee', 'category' => 'other', 'price' => 350.00, 'stock' => 0, 'track' => false],
            ];

            foreach ($productData as $prodData) {
                $product = Product::create([
                    'name' => $prodData['name'],
                    'sku' => strtoupper(substr($prodData['category'], 0, 3)) . '-' . strtoupper(substr(md5($prodData['name']), 0, 6)),
                    'category' => $prodData['category'],
                    'price' => $prodData['price'],
                    'cost' => $prodData['price'] * 0.6,
                    'quantity_in_stock' => $prodData['stock'] ?? 0,
                    'reorder_threshold' => 10,
                    'low_stock_threshold' => 15,
                    'track_stock' => $prodData['track'] ?? true,
                    'is_active' => true,
                    'description' => fake()->optional(0.6)->sentence(),
                ]);
                $products->push($product);
            }            // Create 15-20 pet owners with pets
            $owners = collect();
            for ($i = 0; $i < 18; $i++) {
                $owner = User::factory()->owner()->create([
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'address' => fake()->streetAddress(),
                    'city' => fake()->city(),
                    'state' => fake()->state(),
                    'postal_code' => fake()->postcode(),
                    'emergency_contact_name' => fake()->name(),
                    'emergency_contact_phone' => fake()->phoneNumber(),
                    'phone_secondary' => fake()->optional(0.5)->phoneNumber(),
                    'notes' => fake()->optional(0.3)->sentence(),
                ]);
                $owner->assignRole('owner');
                $owners->push($owner);

                // Each owner has 1-3 pets
                $numPets = rand(1, 3);
                for ($j = 0; $j < $numPets; $j++) {
                    $species = fake()->randomElement(['dog', 'cat', 'bird', 'rabbit']);
                    $pet = Pet::factory()->create([
                        'user_id' => $owner->id,
                        'species' => $species,
                        'birth_date' => fake()->dateTimeBetween('-12 years', '-3 months'),
                    ]);

                    // Add some weight records for each pet
                    $weightCount = rand(2, 5);
                    for ($k = 0; $k < $weightCount; $k++) {
                        WeightRecord::create([
                            'pet_id' => $pet->id,
                            'weight' => fake()->randomFloat(2, 2, 50),
                            'measured_at' => fake()->dateTimeBetween('-2 years', 'now'),
                            'notes' => fake()->optional(0.3)->sentence(),
                        ]);
                    }

                    // Create appointments for each pet
                    $appointmentCount = rand(2, 6);
                    for ($k = 0; $k < $appointmentCount; $k++) {
                        $appointmentDate = fake()->dateTimeBetween('-6 months', '+2 months');
                        $isPast = $appointmentDate < now();
                        $status = $isPast 
                            ? fake()->randomElement(['completed', 'completed', 'completed', 'no_show', 'cancelled'])
                            : fake()->randomElement(['pending', 'confirmed', 'confirmed']);

                        $appointment = Appointment::create([
                            'pet_id' => $pet->id,
                            'user_id' => $owner->id,
                            'veterinarian_id' => $vets->random()->id,
                            'appointment_date' => $appointmentDate,
                            'duration_minutes' => fake()->randomElement([30, 45, 60, 90]),
                            'type' => fake()->randomElement(['checkup', 'vaccination', 'surgery', 'emergency', 'grooming']),
                            'status' => $status,
                            'reason' => fake()->sentence(),
                            'notes' => fake()->optional(0.4)->paragraph(),
                        ]);

                        // Create medical records for completed appointments
                        if ($status === 'completed') {
                            $medicalRecord = MedicalRecord::create([
                                'pet_id' => $pet->id,
                                'veterinarian_id' => $appointment->veterinarian_id,
                                'appointment_id' => $appointment->id,
                                'visit_date' => $appointment->appointment_date,
                                'weight' => fake()->randomFloat(2, 2, 50),
                                'temperature' => fake()->randomFloat(1, 37.5, 39.5),
                                'diagnosis' => fake()->sentence(),
                                'treatment' => fake()->paragraph(),
                                'prescriptions' => fake()->optional(0.6)->sentence(),
                                'procedures' => fake()->optional(0.4)->sentence(),
                                'notes' => fake()->optional(0.5)->paragraph(),
                                'next_visit_date' => fake()->optional(0.5)->dateTimeBetween('now', '+6 months'),
                            ]);

                            // Add vaccinations to some medical records
                            if (fake()->boolean(60)) {
                                $vaccineTypes = ['Rabies', 'DHPP', 'Bordetella', 'Leptospirosis', 'Feline Leukemia'];
                                $numVaccines = rand(1, 2);
                                
                                for ($v = 0; $v < $numVaccines; $v++) {
                                    Vaccination::create([
                                        'pet_id' => $pet->id,
                                        'medical_record_id' => $medicalRecord->id,
                                        'veterinarian_id' => $medicalRecord->veterinarian_id,
                                        'vaccine_name' => fake()->randomElement($vaccineTypes),
                                        'administered_date' => $medicalRecord->visit_date,
                                        'batch_number' => 'BATCH-' . fake()->bothify('??###'),
                                        'next_due_date' => fake()->dateTimeBetween('+6 months', '+18 months'),
                                        'notes' => fake()->optional(0.3)->sentence(),
                                    ]);
                                }
                            }

                            // Create invoice for completed appointment
                            $invoiceNumber = 'INV-' . now()->format('Y') . '-' . str_pad(Invoice::count() + 1, 6, '0', STR_PAD_LEFT);
                            $subtotal = 0;

                            $invoice = Invoice::create([
                                'invoice_number' => $invoiceNumber,
                                'user_id' => $owner->id,
                                'pet_id' => $pet->id,
                                'appointment_id' => $appointment->id,
                                'created_by' => $vets->random()->id,
                                'invoice_date' => $appointment->appointment_date,
                                'due_date' => now()->addDays(30),
                                'subtotal' => 0,
                                'tax_amount' => 0,
                                'discount_amount' => 0,
                                'total_amount' => 0,
                                'paid_amount' => 0,
                                'status' => 'pending',
                                'notes' => fake()->optional(0.2)->sentence(),
                            ]);

                            // Add invoice items
                            $itemCount = rand(1, 4);
                            $selectedProducts = $products->random(min($itemCount, $products->count()));
                            
                            foreach ($selectedProducts as $product) {
                                $quantity = rand(1, 3);
                                $price = $product->price;
                                $itemTotal = $quantity * $price;
                                
                                InvoiceItem::create([
                                    'invoice_id' => $invoice->id,
                                    'product_id' => $product->id,
                                    'type' => $product->category === 'other' ? 'service' : 'product',
                                    'item_name' => $product->name,
                                    'description' => $product->description,
                                    'quantity' => $quantity,
                                    'unit_price' => $price,
                                    'total' => $itemTotal,
                                ]);
                                
                                $subtotal += $itemTotal;
                            }

                            // Update invoice totals
                            $taxAmount = $subtotal * 0.1; // 10% tax
                            $discountAmount = fake()->boolean(20) ? $subtotal * 0.05 : 0; // 5% discount for 20% of invoices
                            $totalAmount = $subtotal + $taxAmount - $discountAmount;

                            $invoice->update([
                                'subtotal' => $subtotal,
                                'tax_amount' => $taxAmount,
                                'discount_amount' => $discountAmount,
                                'total_amount' => $totalAmount,
                            ]);

                            // Create payments for some invoices
                            $paymentChance = fake()->numberBetween(1, 100);
                            if ($paymentChance <= 70) { // 70% paid in full
                                $paymentNumber = 'PAY-' . now()->format('Y') . '-' . str_pad(\App\Models\Payment::count() + 1, 6, '0', STR_PAD_LEFT);
                                $payment = Payment::create([
                                    'invoice_id' => $invoice->id,
                                    'payment_number' => $paymentNumber,
                                    'amount' => $totalAmount,
                                    'payment_date' => fake()->dateTimeBetween($invoice->invoice_date, 'now'),
                                    'payment_method' => fake()->randomElement(['cash', 'credit_card', 'debit_card', 'bank_transfer']),
                                    'transaction_reference' => fake()->bothify('TXN-??###-####'),
                                    'received_by' => $vets->random()->id,
                                    'notes' => fake()->optional(0.2)->sentence(),
                                ]);

                                $invoice->update([
                                    'paid_amount' => $totalAmount,
                                    'status' => 'paid',
                                ]);
                            } elseif ($paymentChance <= 85) { // 15% partially paid
                                $partialAmount = $totalAmount * fake()->randomFloat(2, 0.3, 0.8);
                                $paymentNumber = 'PAY-' . now()->format('Y') . '-' . str_pad(\App\Models\Payment::count() + 1, 6, '0', STR_PAD_LEFT);
                                
                                $payment = Payment::create([
                                    'invoice_id' => $invoice->id,
                                    'payment_number' => $paymentNumber,
                                    'amount' => $partialAmount,
                                    'payment_date' => fake()->dateTimeBetween($invoice->invoice_date, 'now'),
                                    'payment_method' => fake()->randomElement(['cash', 'credit_card', 'debit_card', 'bank_transfer']),
                                    'transaction_reference' => fake()->bothify('TXN-??###-####'),
                                    'received_by' => $cashier->id,
                                    'notes' => 'Partial payment',
                                ]);

                                $invoice->update([
                                    'paid_amount' => $partialAmount,
                                    'status' => 'partially_paid',
                                ]);
                            }
                            // 15% remain unpaid
                        }

                        // Add some communication logs
                        if (fake()->boolean(40)) {
                            CommunicationLog::create([
                                'user_id' => $owner->id,
                                'staff_id' => $vets->random()->id,
                                'type' => fake()->randomElement(['call', 'email', 'whatsapp', 'sms']),
                                'direction' => fake()->randomElement(['inbound', 'outbound']),
                                'subject' => fake()->sentence(4),
                                'notes' => fake()->paragraph(),
                                'contacted_at' => fake()->dateTimeBetween('-30 days', 'now'),
                                'duration_minutes' => fake()->optional(0.5)->numberBetween(2, 30),
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            $this->command->info('Demo data seeded successfully!');
            $this->command->info('');
            $this->command->info('Demo Login Credentials:');
            $this->command->info('------------------------');
            $this->command->info('Admin: admin@pawsitive.com / password');
            $this->command->info('Veterinarian 1: vet1@pawsitive.com / password');
            $this->command->info('Veterinarian 2: vet2@pawsitive.com / password');
            $this->command->info('Veterinarian 3: vet3@pawsitive.com / password');
            $this->command->info('Receptionist 1: receptionist@pawsitive.com / password');
            $this->command->info('Receptionist 2: receptionist2@pawsitive.com / password');
            $this->command->info('Cashier: cashier@pawsitive.com / password');
            $this->command->info('');
            $this->command->info("Created: {$owners->count()} pet owners with pets, appointments, medical records, invoices, and more!");
            $this->command->info("Total products: {$products->count()}");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error seeding demo data: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Ensure all required roles exist before seeding
     */
    private function ensureRolesExist(): void
    {
        $this->command->info('Checking if roles exist...');
        
        $requiredRoles = ['admin', 'veterinarian', 'receptionist', 'cashier', 'owner'];
        $missingRoles = [];
        
        foreach ($requiredRoles as $roleName) {
            try {
                \Spatie\Permission\Models\Role::findByName($roleName, 'api');
            } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $e) {
                $missingRoles[] = $roleName;
            }
        }
        
        if (!empty($missingRoles)) {
            $this->command->error('The following roles are missing: ' . implode(', ', $missingRoles));
            $this->command->error('Please run RolePermissionSeeder first:');
            $this->command->error('php artisan db:seed --class=RolePermissionSeeder');
            throw new \Exception('Required roles not found. Run RolePermissionSeeder first.');
        }
        
        $this->command->info('All required roles exist. Proceeding...');
    }
}

