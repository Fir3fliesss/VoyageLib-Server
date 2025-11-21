<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class VerifySupabaseUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supabase:verify-users {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify email for Supabase users created via seeder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $supabaseUrl = env('SUPABASE_URL');
        $serviceKey = env('SUPABASE_SERVICE_KEY');
        
        if (!$supabaseUrl || !$serviceKey) {
            $this->error('Supabase configuration not found in .env');
            return Command::FAILURE;
        }

        $emails = [
            'gheraldytarigan@gmail.com',
            'yaelahlal05@gmail.com', 
            'gheraldymoses19@gmail.com'
        ];

        // If specific email provided
        if ($email = $this->argument('email')) {
            $emails = [$email];
        }

        foreach ($emails as $email) {
            $this->info("Verifying email for: {$email}");
            
            try {
                // Get user by email using Admin API
                $response = Http::withHeaders([
                    'apikey' => $serviceKey,
                    'Authorization' => 'Bearer ' . $serviceKey,
                ])->get("{$supabaseUrl}/auth/v1/admin/users", [
                    'email' => $email
                ]);

                if ($response->successful()) {
                    $users = $response->json()['users'] ?? [];
                    
                    if (count($users) > 0) {
                        $userId = $users[0]['id'];
                        
                        // Update user to verify email
                        $updateResponse = Http::withHeaders([
                            'apikey' => $serviceKey,
                            'Authorization' => 'Bearer ' . $serviceKey,
                            'Content-Type' => 'application/json'
                        ])->put("{$supabaseUrl}/auth/v1/admin/users/{$userId}", [
                            'email_confirmed_at' => now()->toIso8601String()
                        ]);

                        if ($updateResponse->successful()) {
                            $this->info("✓ Email verified successfully for {$email}");
                        } else {
                            $this->error("Failed to verify email for {$email}");
                        }
                    } else {
                        $this->warn("User not found: {$email}");
                    }
                } else {
                    $this->error("Failed to fetch user: {$email}");
                }
            } catch (\Exception $e) {
                $this->error("Error verifying {$email}: " . $e->getMessage());
            }
        }

        $this->info('Email verification process completed!');
        return Command::SUCCESS;
    }
}
