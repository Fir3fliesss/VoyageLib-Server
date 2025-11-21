<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class SyncSupabaseUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supabase:sync-users {--email= : Specific email to sync} {--role= : Set role for the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Supabase users with local database and optionally set role';

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

        $email = $this->option('email');
        $role = $this->option('role');

        if ($email) {
            // Sync specific user
            $this->syncUser($email, $role, $supabaseUrl, $serviceKey);
        } else {
            // Sync all users from Supabase
            $this->syncAllUsers($supabaseUrl, $serviceKey);
        }

        return Command::SUCCESS;
    }

    private function syncUser($email, $role, $supabaseUrl, $serviceKey)
    {
        $this->info("Syncing user: {$email}");

        try {
            // Get user from Supabase
            $response = Http::withHeaders([
                'apikey' => $serviceKey,
                'Authorization' => 'Bearer ' . $serviceKey,
            ])->get("{$supabaseUrl}/auth/v1/admin/users", [
                'email' => $email
            ]);

            if ($response->successful()) {
                $users = $response->json()['users'] ?? [];
                
                if (count($users) > 0) {
                    $supabaseUser = $users[0];
                    $supabaseId = $supabaseUser['id'];
                    
                    // Check if user exists in local database
                    $localUser = User::where('email', $email)
                        ->orWhere('supabase_id', $supabaseId)
                        ->first();
                    
                    if (!$localUser) {
                        // Create user in local database
                        $localUser = User::create([
                            'name' => $supabaseUser['user_metadata']['name'] ?? $email,
                            'email' => $email,
                            'supabase_id' => $supabaseId,
                            'password' => bcrypt(uniqid()),
                            'role' => $role ?? $supabaseUser['user_metadata']['role'] ?? 'user',
                            'phone' => $supabaseUser['user_metadata']['phone'] ?? null,
                            'address' => $supabaseUser['user_metadata']['address'] ?? null,
                        ]);
                        $this->info("✓ User created in local database: {$email}");
                    } else {
                        // Update user
                        $updateData = ['supabase_id' => $supabaseId];
                        
                        if ($role) {
                            $updateData['role'] = $role;
                        }
                        
                        $localUser->update($updateData);
                        $this->info("✓ User updated: {$email}");
                    }

                    // Update role in Supabase metadata if specified
                    if ($role) {
                        $this->updateSupabaseUserMetadata($supabaseId, ['role' => $role], $supabaseUrl, $serviceKey);
                        $this->info("✓ Role updated to '{$role}' in Supabase");
                    }
                    
                    $this->table(
                        ['Field', 'Value'],
                        [
                            ['Email', $localUser->email],
                            ['Name', $localUser->name],
                            ['Role', $localUser->role],
                            ['Supabase ID', $localUser->supabase_id],
                        ]
                    );
                } else {
                    $this->warn("User not found in Supabase: {$email}");
                }
            } else {
                $this->error("Failed to fetch user from Supabase");
            }
        } catch (\Exception $e) {
            $this->error("Error syncing user: " . $e->getMessage());
        }
    }

    private function syncAllUsers($supabaseUrl, $serviceKey)
    {
        $this->info("Syncing all users from Supabase...");

        try {
            // Get all users from Supabase
            $response = Http::withHeaders([
                'apikey' => $serviceKey,
                'Authorization' => 'Bearer ' . $serviceKey,
            ])->get("{$supabaseUrl}/auth/v1/admin/users");

            if ($response->successful()) {
                $users = $response->json()['users'] ?? [];
                $count = 0;

                foreach ($users as $supabaseUser) {
                    $email = $supabaseUser['email'];
                    $supabaseId = $supabaseUser['id'];
                    
                    $localUser = User::where('email', $email)
                        ->orWhere('supabase_id', $supabaseId)
                        ->first();
                    
                    if (!$localUser) {
                        User::create([
                            'name' => $supabaseUser['user_metadata']['name'] ?? $email,
                            'email' => $email,
                            'supabase_id' => $supabaseId,
                            'password' => bcrypt(uniqid()),
                            'role' => $supabaseUser['user_metadata']['role'] ?? 'user',
                            'phone' => $supabaseUser['user_metadata']['phone'] ?? null,
                            'address' => $supabaseUser['user_metadata']['address'] ?? null,
                        ]);
                        $count++;
                        $this->info("✓ Synced: {$email}");
                    }
                }

                $this->info("Sync completed! {$count} users added to local database.");
            } else {
                $this->error("Failed to fetch users from Supabase");
            }
        } catch (\Exception $e) {
            $this->error("Error syncing users: " . $e->getMessage());
        }
    }

    private function updateSupabaseUserMetadata($userId, $metadata, $supabaseUrl, $serviceKey)
    {
        try {
            Http::withHeaders([
                'apikey' => $serviceKey,
                'Authorization' => 'Bearer ' . $serviceKey,
                'Content-Type' => 'application/json'
            ])->put("{$supabaseUrl}/auth/v1/admin/users/{$userId}", [
                'user_metadata' => $metadata
            ]);
        } catch (\Exception $e) {
            $this->error("Error updating Supabase metadata: " . $e->getMessage());
        }
    }
}
