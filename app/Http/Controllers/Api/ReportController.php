<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;
use App\Models\Book;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;

class ReportController extends Controller
{
    /**
     * Get borrowing reports (Staff/Admin only)
     */
    public function borrowings(Request $request)
    {
        try {
            $query = Borrowing::with(['user', 'book']);
            
            // Filter by date range
            if ($request->from_date) {
                $query->where('borrow_date', '>=', Carbon::parse($request->from_date)->startOfDay());
            }
            
            if ($request->to_date) {
                $query->where('borrow_date', '<=', Carbon::parse($request->to_date)->endOfDay());
            }
            
            // Filter by status
            if ($request->status) {
                $query->where('status', $request->status);
            }
            
            // Filter by user
            if ($request->user_id) {
                $query->where('user_id', $request->user_id);
            }
            
            // Filter by book
            if ($request->book_id) {
                $query->where('book_id', $request->book_id);
            }
            
            $borrowings = $query->orderBy('borrow_date', 'desc')->get();
            
            // Calculate statistics
            $statistics = [
                'total_borrowings' => $borrowings->count(),
                'active_borrowings' => $borrowings->where('status', 'dipinjam')->count(),
                'returned_borrowings' => $borrowings->where('status', 'dikembalikan')->count(),
                'overdue_borrowings' => $borrowings->filter(function ($b) {
                    return $b->status === 'dipinjam' && Carbon::now()->greaterThan($b->return_date);
                })->count()
            ];
            
            return response()->json([
                'success' => true,
                'statistics' => $statistics,
                'data' => $borrowings
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating report: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Export borrowings report (Admin only) 
     * Note: PDF generation requires additional package installation
     */
    public function export(Request $request)
    {
        try {
            // For MVP, return JSON data that can be formatted client-side
            // For full PDF export, install package like DomPDF or TCPDF
            
            $query = Borrowing::with(['user', 'book']);
            
            // Apply same filters as borrowings method
            if ($request->from_date) {
                $query->where('borrow_date', '>=', Carbon::parse($request->from_date)->startOfDay());
            }
            
            if ($request->to_date) {
                $query->where('borrow_date', '<=', Carbon::parse($request->to_date)->endOfDay());
            }
            
            if ($request->status) {
                $query->where('status', $request->status);
            }
            
            $borrowings = $query->orderBy('borrow_date', 'desc')->get();
            
            // Format data for export
            $exportData = $borrowings->map(function ($borrowing) {
                return [
                    'borrowing_id' => $borrowing->id,
                    'user_name' => $borrowing->user->name,
                    'user_email' => $borrowing->user->email,
                    'book_title' => $borrowing->book->title,
                    'book_author' => $borrowing->book->author,
                    'borrow_date' => $borrowing->borrow_date->format('Y-m-d H:i'),
                    'return_date' => $borrowing->return_date->format('Y-m-d H:i'),
                    'actual_return_date' => $borrowing->actual_return_date ? $borrowing->actual_return_date->format('Y-m-d H:i') : null,
                    'duration_days' => $borrowing->borrow_duration_days,
                    'status' => $borrowing->status,
                    'is_overdue' => $borrowing->isOverdue()
                ];
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Export data generated successfully',
                'report_title' => 'Borrowing Report - ' . now()->format('Y-m-d'),
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'total_records' => $exportData->count(),
                'data' => $exportData
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting report: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get dashboard statistics
     */
    public function dashboard(Request $request)
    {
        try {
            $totalBooks = Book::count();
            $totalUsers = User::count();
            $activeBorrowings = Borrowing::where('status', 'dipinjam')->count();
            $overdueBorrowings = Borrowing::where('status', 'dipinjam')
                ->where('return_date', '<', now())
                ->count();
            
            // Popular books
            $popularBooks = Borrowing::with('book')
                ->select('book_id')
                ->selectRaw('COUNT(*) as borrow_count')
                ->groupBy('book_id')
                ->orderBy('borrow_count', 'desc')
                ->limit(5)
                ->get();
            
            // Active borrowers
            $activeBorrowers = Borrowing::with('user')
                ->where('status', 'dipinjam')
                ->select('user_id')
                ->selectRaw('COUNT(*) as active_count')
                ->groupBy('user_id')
                ->orderBy('active_count', 'desc')
                ->limit(5)
                ->get();
            
            return response()->json([
                'success' => true,
                'statistics' => [
                    'total_books' => $totalBooks,
                    'total_users' => $totalUsers,
                    'active_borrowings' => $activeBorrowings,
                    'overdue_borrowings' => $overdueBorrowings
                ],
                'popular_books' => $popularBooks,
                'active_borrowers' => $activeBorrowers
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }
}
