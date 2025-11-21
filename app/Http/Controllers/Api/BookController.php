<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Exception;

class BookController extends Controller
{
    /**
     * Display a listing of books (local + Gutendex)
     */
    public function index(Request $request)
    {
        try {
            // Get local books
            $localBooks = Book::query()
                ->when($request->search, function ($query) use ($request) {
                    $query->where('title', 'like', '%' . $request->search . '%')
                        ->orWhere('author', 'like', '%' . $request->search . '%')
                        ->orWhere('category', 'like', '%' . $request->search . '%');
                })
                ->when($request->category, function ($query) use ($request) {
                    $query->where('category', $request->category);
                })
                ->get();

            // Get books from Gutendex API
            $gutendexBooks = [];
            if ($request->include_online !== 'false') {
                $gutendexBooks = $this->fetchGutendexBooks($request->search);
            }

            // Combine both sources
            $allBooks = $localBooks->toArray();
            foreach ($gutendexBooks as $book) {
                $allBooks[] = $book;
            }

            return response()->json([
                'success' => true,
                'data' => $allBooks,
                'count' => count($allBooks)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching books: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created book (Staff/Admin only)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'stock' => 'required|integer|min:0',
            'isbn' => 'nullable|string|unique:books,isbn',
            'description' => 'nullable|string',
            'cover_url' => 'nullable|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $book = Book::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Book created successfully',
                'data' => $book
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating book: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified book
     */
    public function show(string $id)
    {
        try {
            // Try to find local book first
            $book = Book::find($id);
            
            if (!$book) {
                // If not found locally, check if it's a Gutendex book ID
                if (strpos($id, 'gutendex-') === 0) {
                    $gutendexId = str_replace('gutendex-', '', $id);
                    $book = $this->fetchGutendexBookById($gutendexId);
                    
                    if (!$book) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Book not found'
                        ], 404);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Book not found'
                    ], 404);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $book
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching book: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified book (Staff/Admin only)
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'author' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string|max:100',
            'stock' => 'sometimes|required|integer|min:0',
            'isbn' => 'sometimes|nullable|string|unique:books,isbn,' . $id,
            'description' => 'nullable|string',
            'cover_url' => 'nullable|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $book = Book::findOrFail($id);
            $book->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Book updated successfully',
                'data' => $book
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating book: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified book (Staff/Admin only)
     */
    public function destroy(string $id)
    {
        try {
            $book = Book::findOrFail($id);
            
            // Check if book has active borrowings
            $activeBorrowings = $book->borrowings()
                ->where('status', 'dipinjam')
                ->count();
            
            if ($activeBorrowings > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete book with active borrowings'
                ], 400);
            }

            $book->delete();

            return response()->json([
                'success' => true,
                'message' => 'Book deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting book: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch books from Gutendex API
     */
    private function fetchGutendexBooks($search = null)
    {
        try {
            $url = 'https://gutendex.com/books';
            $params = [];
            
            if ($search) {
                $params['search'] = $search;
            }
            
            $response = Http::get($url, $params);
            
            if ($response->successful()) {
                $data = $response->json();
                $books = [];
                
                foreach ($data['results'] ?? [] as $item) {
                    $books[] = [
                        'id' => 'gutendex-' . $item['id'],
                        'title' => $item['title'],
                        'author' => $this->extractAuthors($item['authors']),
                        'description' => $item['subjects'] ? implode(', ', $item['subjects']) : '',
                        'cover_url' => $item['formats']['image/jpeg'] ?? null,
                        'category' => 'Online Book',
                        'stock' => null,
                        'can_read_online' => true,
                        'source' => 'gutendex',
                        'formats' => $item['formats'] ?? []
                    ];
                }
                
                return $books;
            }
            
            return [];
        } catch (Exception $e) {
            // Log error but return empty array to not break the main flow
            return [];
        }
    }

    /**
     * Fetch single book from Gutendex API by ID
     */
    private function fetchGutendexBookById($id)
    {
        try {
            $response = Http::get("https://gutendex.com/books/{$id}");
            
            if ($response->successful()) {
                $item = $response->json();
                
                return [
                    'id' => 'gutendex-' . $item['id'],
                    'title' => $item['title'],
                    'author' => $this->extractAuthors($item['authors']),
                    'description' => $item['subjects'] ? implode(', ', $item['subjects']) : '',
                    'cover_url' => $item['formats']['image/jpeg'] ?? null,
                    'category' => 'Online Book',
                    'stock' => null,
                    'can_read_online' => true,
                    'source' => 'gutendex',
                    'formats' => $item['formats'] ?? []
                ];
            }
            
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Extract authors from Gutendex API response
     */
    private function extractAuthors($authors)
    {
        if (empty($authors)) {
            return 'Unknown Author';
        }
        
        $authorNames = array_map(function ($author) {
            return $author['name'] ?? 'Unknown';
        }, $authors);
        
        return implode(', ', $authorNames);
    }
}
