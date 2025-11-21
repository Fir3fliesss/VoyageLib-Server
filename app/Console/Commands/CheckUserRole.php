<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CheckUserRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:check-role {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check user role and details by email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User not found: {$email}");
            $this->info("Tip: Run 'php artisan supabase:sync-users --email={$email}' to sync from Supabase");
            return Command::FAILURE;
        }
        
        $this->info("User Details:");
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $user->id],
                ['Name', $user->name],
                ['Email', $user->email],
                ['Role', $user->role],
                ['Phone', $user->phone ?? 'Not set'],
                ['Address', $user->address ?? 'Not set'],
                ['Supabase ID', $user->supabase_id ?? 'Not synced'],
                ['Created', $user->created_at],
            ]
        );
        
        // Show role capabilities
        $this->info("\nRole Capabilities for '{$user->role}':");
        switch ($user->role) {
            case 'admin':
                $this->line("✓ All user management");
                $this->line("✓ All book management");
                $this->line("✓ All promotion management");
                $this->line("✓ View all reports");
                $this->line("✓ Export reports");
                break;
            case 'staff':
                $this->line("✓ Book management (CRUD)");
                $this->line("✓ Promotion management (CRUD)");
                $this->line("✓ View borrowing reports");
                $this->line("✗ User management");
                $this->line("✗ Export reports");
                break;
            case 'user':
                $this->line("✓ View books");
                $this->line("✓ Borrow books");
                $this->line("✓ View promotions");
                $this->line("✗ Management features");
                break;
        }
        
        return Command::SUCCESS;
    }
}
