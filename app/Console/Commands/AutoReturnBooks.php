<?php

namespace App\Console\Commands;

use App\Models\Borrowing;
use Illuminate\Console\Command;
use Exception;

class AutoReturnBooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'books:auto-return';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically return overdue books';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting auto-return process...');
        
        $count = 0;
        
        // Find all overdue borrowings
        $overdueBorrowings = Borrowing::where('status', 'dipinjam')
            ->where('return_date', '<=', now())
            ->get();
        
        $this->info("Found {$overdueBorrowings->count()} overdue borrowings.");

        foreach ($overdueBorrowings as $borrowing) {
            try {
                $borrowing->markAsReturned();
                $count++;
                $this->info("Returned book ID {$borrowing->book_id} for user ID {$borrowing->user_id}");
            } catch (Exception $e) {
                $this->error("Failed to return book ID {$borrowing->book_id}: " . $e->getMessage());
                continue;
            }
        }

        $this->info("Auto-return completed. Returned {$count} books.");
        
        return Command::SUCCESS;
    }
}
