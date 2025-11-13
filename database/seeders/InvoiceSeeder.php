<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some pets and users
        $pets = Pet::take(10)->get();
        $cashiers = User::role('cashier')->get();
        
        if ($pets->isEmpty() || $cashiers->isEmpty()) {
            $this->command->warn('No pets or cashiers found. Run PetSeeder and UserSeeder first.');
            return;
        }
        
        foreach ($pets as $pet) {
            // Create 1-3 invoices per pet
            $invoiceCount = rand(1, 3);
            
            for ($i = 0; $i < $invoiceCount; $i++) {
                $invoice = Invoice::factory()->create([
                    'pet_id' => $pet->id,
                    'user_id' => $pet->user_id,
                    'created_by' => $cashiers->random()->id,
                ]);
                
                // Create 2-5 items per invoice
                $itemCount = rand(2, 5);
                
                for ($j = 0; $j < $itemCount; $j++) {
                    InvoiceItem::factory()->create([
                        'invoice_id' => $invoice->id,
                    ]);
                }
                
                // Recalculate invoice totals based on items
                $items = $invoice->items;
                $subtotal = $items->sum(fn($item) => $item->unit_price * $item->quantity);
                $taxAmount = $items->sum('tax_amount');
                $discountAmount = $items->sum('discount_amount');
                $totalAmount = $items->sum('total');
                
                $invoice->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                ]);
            }
        }
        
        $this->command->info('Invoices and invoice items created successfully.');
    }
}

