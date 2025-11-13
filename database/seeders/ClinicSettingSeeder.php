<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClinicSetting;

class ClinicSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Clinic Information
            [
                'key' => 'clinic_name',
                'value' => 'Pawsitive Veterinary Clinic',
                'type' => 'string',
                'description' => 'The name of the veterinary clinic',
            ],
            [
                'key' => 'clinic_email',
                'value' => 'contact@pawsitive.com',
                'type' => 'string',
                'description' => 'Main contact email address',
            ],
            [
                'key' => 'clinic_phone',
                'value' => '+1-555-0100',
                'type' => 'string',
                'description' => 'Main contact phone number',
            ],
            [
                'key' => 'clinic_address',
                'value' => '123 Veterinary Street, Pet City, PC 12345',
                'type' => 'string',
                'description' => 'Physical address of the clinic',
            ],

            // Business Hours
            [
                'key' => 'business_hours_start',
                'value' => '08:00',
                'type' => 'string',
                'description' => 'Clinic opening time (24-hour format)',
            ],
            [
                'key' => 'business_hours_end',
                'value' => '18:00',
                'type' => 'string',
                'description' => 'Clinic closing time (24-hour format)',
            ],
            [
                'key' => 'business_days',
                'value' => 'Monday,Tuesday,Wednesday,Thursday,Friday',
                'type' => 'string',
                'description' => 'Days the clinic is open',
            ],

            // Appointment Settings
            [
                'key' => 'default_appointment_duration',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Default appointment duration in minutes',
            ],
            [
                'key' => 'appointment_buffer_time',
                'value' => '5',
                'type' => 'integer',
                'description' => 'Buffer time between appointments in minutes',
            ],
            [
                'key' => 'max_appointments_per_day',
                'value' => '20',
                'type' => 'integer',
                'description' => 'Maximum number of appointments per day',
            ],
            [
                'key' => 'allow_online_booking',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Allow clients to book appointments online',
            ],
            [
                'key' => 'require_appointment_confirmation',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Require staff to confirm appointments',
            ],

            // Notification Settings
            [
                'key' => 'send_appointment_reminders',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Send automatic appointment reminders',
            ],
            [
                'key' => 'appointment_reminder_hours',
                'value' => '24',
                'type' => 'integer',
                'description' => 'Hours before appointment to send reminder',
            ],
            [
                'key' => 'send_email_notifications',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable email notifications',
            ],
            [
                'key' => 'send_sms_notifications',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Enable SMS notifications',
            ],

            // Invoice Settings
            [
                'key' => 'default_tax_rate',
                'value' => '10.0',
                'type' => 'decimal',
                'description' => 'Default tax rate percentage',
            ],
            [
                'key' => 'invoice_due_days',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Default invoice due date in days',
            ],
            [
                'key' => 'late_payment_fee',
                'value' => '5.0',
                'type' => 'decimal',
                'description' => 'Late payment fee percentage',
            ],
            [
                'key' => 'invoice_prefix',
                'value' => 'INV',
                'type' => 'string',
                'description' => 'Prefix for invoice numbers',
            ],

            // System Settings
            [
                'key' => 'currency',
                'value' => 'USD',
                'type' => 'string',
                'description' => 'Currency code (ISO 4217)',
            ],
            [
                'key' => 'currency_symbol',
                'value' => '$',
                'type' => 'string',
                'description' => 'Currency symbol',
            ],
            [
                'key' => 'timezone',
                'value' => 'America/New_York',
                'type' => 'string',
                'description' => 'Clinic timezone',
            ],
            [
                'key' => 'date_format',
                'value' => 'Y-m-d',
                'type' => 'string',
                'description' => 'Date format for display',
            ],
            [
                'key' => 'time_format',
                'value' => 'H:i',
                'type' => 'string',
                'description' => 'Time format for display',
            ],

            // Inventory Settings
            [
                'key' => 'low_stock_threshold',
                'value' => '10',
                'type' => 'integer',
                'description' => 'Quantity threshold for low stock alerts',
            ],
            [
                'key' => 'enable_stock_alerts',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable low stock email alerts',
            ],

            // Medical Record Settings
            [
                'key' => 'default_weight_unit',
                'value' => 'kg',
                'type' => 'string',
                'description' => 'Default unit for pet weight (kg or lb)',
            ],
            [
                'key' => 'default_temperature_unit',
                'value' => 'celsius',
                'type' => 'string',
                'description' => 'Default temperature unit (celsius or fahrenheit)',
            ],
        ];

        foreach ($settings as $setting) {
            ClinicSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Clinic settings seeded successfully!');
    }
}
