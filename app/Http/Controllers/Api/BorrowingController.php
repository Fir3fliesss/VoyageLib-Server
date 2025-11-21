<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Exception;

class BorrowingController extends Controller
{
    /**
     * Display a listing of borrowings (filtered by user role)
     */
    public function index(Request $request)
    {
        try {
            $query = Borrowing::with(['user', 'book']);

            // Filter by user if not staff/admin
            if ($request->user() && $request->user()->role === 'user') {
                $query->where('user_id', $request->user()->id);
            }

            // Filter by status
            if ($request->status) {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->from_date) {
                $query->where('borrow_date', '>=', $request->from_date);
            }
            if ($request->to_date) {
                $query->where('borrow_date', '<=', $request->to_date);
            }

            $borrowings = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $borrowings
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching borrowings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created borrowing (User pinjam buku)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_id' => 'required|uuid|exists:books,id',
            'borrow_duration_days' => 'required|integer|min:1|max:30'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            $book = Book::findOrFail($request->book_id);
            
            // Check if book is available
            if (!$book->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Book is not available for borrowing (out of stock)'
                ], 400);
            }

            // Check if it's a Gutendex book
            if ($book->source === 'gutendex' || $book->can_read_online) {
                return response()->json([
                    'success' => false,
                    'message' => 'Online books cannot be borrowed, they can only be read online'
                ], 400);
            }

            // Check if user already has active borrowing for this book
            $existingBorrowing = Borrowing::where('user_id', $request->user()->id)
                ->where('book_id', $request->book_id)
                ->where('status', 'dipinjam')
                ->exists();

            if ($existingBorrowing) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an active borrowing for this book'
                ], 400);
            }

            // Create borrowing record
            $borrowing = Borrowing::create([
                'user_id' => $request->user()->id,
                'book_id' => $request->book_id,
                'borrow_duration_days' => $request->borrow_duration_days,
                'borrow_date' => now(),
                'return_date' => now()->addDays($request->borrow_duration_days),
                'status' => 'dipinjam',
                'notes' => $request->notes ?? null
            ]);

            // Decrease book stock
            $book->decrement('stock');

            DB::commit();

            $borrowing->load(['book', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Book borrowed successfully',
                'data' => $borrowing
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating borrowing: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified borrowing
     */
    public function show(Request $request, string $id)
    {
        try {
            $borrowing = Borrowing::with(['user', 'book'])->findOrFail($id);

            // Check access rights
            if ($request->user()->role === 'user' && $borrowing->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $borrowing
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Borrowing not found'
            ], 404);
        }
    }

    /**
     * Get user's own borrowings
     */
    public function myBorrowings(Request $request)
    {
        try {
            $borrowings = Borrowing::with(['book'])
                ->where('user_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $borrowings
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching borrowings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Return a book (manual return by user or staff)
     */
    public function returnBook(Request $request, string $id)
    {
        DB::beginTransaction();
        
        try {
            $borrowing = Borrowing::findOrFail($id);

            // Check if already returned
            if ($borrowing->status === 'dikembalikan') {
                return response()->json([
                    'success' => false,
                    'message' => 'Book already returned'
                ], 400);
            }

            // Check access rights
            if ($request->user()->role === 'user' && $borrowing->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            // Mark as returned
            $borrowing->markAsReturned();

            DB::commit();

            $borrowing->load(['book', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Book returned successfully',
                'data' => $borrowing
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error returning book: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto return overdue books (called by scheduler)
     */
    public function autoReturn()
    {
        $count = 0;
        
        // Find all overdue borrowings
        $overdueBorrowings = Borrowing::where('status', 'dipinjam')
            ->where('return_date', '<=', now())
            ->get();

        foreach ($overdueBorrowings as $borrowing) {
            try {
                $borrowing->markAsReturned();
                $count++;
            } catch (Exception $e) {
                // Log error but continue with other borrowings
                continue;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Auto-returned {$count} overdue books"
        ]);
    }
}
