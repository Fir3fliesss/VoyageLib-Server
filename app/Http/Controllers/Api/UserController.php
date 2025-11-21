<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SupabaseAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Exception;

class UserController extends Controller
{
    protected $supabaseAuth;

    public function __construct(SupabaseAuthService $supabaseAuth)
    {
        $this->supabaseAuth = $supabaseAuth;
    }

    /**
     * Display a listing of all users (Admin only)
     */
    public function index(Request $request)
    {
        try {
            $query = User::query();
            
            // Filter by role
            if ($request->role) {
                $query->where('role', $request->role);
            }
            
            // Search by name or email
            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            }
            
            $users = $query->select('id', 'name', 'email', 'role', 'phone', 'address', 'supabase_id', 'created_at', 'updated_at')
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Add role summary
            $roleSummary = [
                'total' => $users->count(),
                'admins' => $users->where('role', 'admin')->count(),
                'staff' => $users->where('role', 'staff')->count(),
                'users' => $users->where('role', 'user')->count()
            ];
            
            return response()->json([
                'success' => true,
                'summary' => $roleSummary,
                'data' => $users
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created user (Admin only - for creating staff/admin)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:user,staff,admin',
            'phone' => 'nullable|string',
            'address' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create user in Supabase
            $supabaseUser = $this->supabaseAuth->register(
                $request->email,
                $request->password,
                ['name' => $request->name, 'role' => $request->role]
            );

            // Create user in local database
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'phone' => $request->phone,
                'address' => $request->address,
                'supabase_id' => $supabaseUser->id ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified user
     */
    public function show(string $id)
    {
        try {
            $user = User::with(['borrowings.book', 'promotions'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $user
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }

    /**
     * Update the specified user (Admin only)
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|in:user,staff,admin',
            'phone' => 'nullable|string',
            'address' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::findOrFail($id);
            
            $updateData = $request->except('password');
            if ($request->has('password')) {
                $updateData['password'] = Hash::make($request->password);
            }
            
            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified user (Admin only)
     */
    public function destroy(string $id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Prevent self-deletion
            if ($user->id === request()->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete your own account'
                ], 400);
            }
            
            // Check if user has active borrowings
            $activeBorrowings = $user->borrowings()
                ->where('status', 'dipinjam')
                ->count();
            
            if ($activeBorrowings > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete user with active borrowings'
                ], 400);
            }
            
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting user: ' . $e->getMessage()
            ], 500);
        }
    }
}
