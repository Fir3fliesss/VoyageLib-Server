<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $supabaseUrl = env('SUPABASE_URL');
        $serviceKey = env('SUPABASE_SERVICE_KEY');
        
        $users = [
            [
                'name' => 'Gheraldy Moses Tarigam',
                'email' => 'gheraldytarigan@gmail.com',
                'password' => 'admin123',
                'role' => 'admin',
                'phone' => '081234567890',
                'address' => 'VoyageLib Library Office'
            ],
            [
                'name' => 'Gheraldy',
                'email' => 'yaelahlal05@gmail.com',
                'password' => 'staff123',
                'role' => 'staff',
                'phone' => '081234567891',
                'address' => 'VoyageLib Library Desk'
            ],
            [
                'name' => 'Gheraldy',
                'email' => 'gheraldymoses19@gmail.com',
                'password' => 'user123',
                'role' => 'user',
                'phone' => '081234567892',
                'address' => 'Jl. Example No. 123'
            ]
        ];

        foreach ($users as $userData) {
            // Check if user already exists
            if (User::where('email', $userData['email'])->exists()) {
                $this->command->info("User {$userData['email']} already exists, skipping...");
                continue;
            }

            $supabaseId = null;

            // Create user in Supabase with verified email
            if ($supabaseUrl && $serviceKey) {
                try {
                    // Create user in Supabase with admin API (auto-verified)
                    $response = Http::withHeaders([
                        'apikey' => $serviceKey,
                        'Authorization' => 'Bearer ' . $serviceKey,
                        'Content-Type' => 'application/json'
                    ])->post("{$supabaseUrl}/auth/v1/admin/users", [
                        'email' => $userData['email'],
                        'password' => $userData['password'],
                        'email_confirm' => true,
                        'user_metadata' => [
                            'name' => $userData['name'],
                            'role' => $userData['role']
                        ]
                    ]);

                    if ($response->successful()) {
                        $supabaseUser = $response->json()['user'] ?? $response->json();
                        $supabaseId = $supabaseUser['id'] ?? null;
                        $this->command->info("✓ Supabase user created: {$userData['email']}");
                    } else {
                        $this->command->warn("Failed to create Supabase user: {$userData['email']}");
                        $this->command->warn("Response: " . $response->body());
                    }
                } catch (\Exception $e) {
                    $this->command->error("Error creating Supabase user: " . $e->getMessage());
                }
            }

            // Create user in local database
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'role' => $userData['role'],
                'phone' => $userData['phone'],
                'address' => $userData['address'],
                'supabase_id' => $supabaseId
            ]);

            $this->command->info("✓ Local user created: {$userData['email']}");
        }

        $this->command->info('');
        $this->command->info('=== Seeder completed successfully ===');
        $this->command->info('Users created with verified emails:');
        $this->command->info('Admin: gheraldytarigan@gmail.com / admin123');
        $this->command->info('Staff: yaelahlal05@gmail.com / staff123'); 
        $this->command->info('User: gheraldymoses19@gmail.com / user123');
    }
}
