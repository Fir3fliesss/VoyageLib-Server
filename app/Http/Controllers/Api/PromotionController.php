<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class PromotionController extends Controller
{
    /**
     * Display a listing of active promotions
     */
    public function index(Request $request)
    {
        try {
            $query = Promotion::with('uploader');
            
            // Filter by active status (default: only active)
            if ($request->active !== 'all') {
                $query->active();
            }
            
            $promotions = $query->orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $promotions
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching promotions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created promotion (Staff/Admin only)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'required|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $promotion = Promotion::create([
                'title' => $request->title,
                'description' => $request->description,
                'image_url' => $request->image_url,
                'uploaded_by' => $request->user()->id,
                'is_active' => $request->is_active ?? true
            ]);

            $promotion->load('uploader');

            return response()->json([
                'success' => true,
                'message' => 'Promotion created successfully',
                'data' => $promotion
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating promotion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified promotion
     */
    public function show(string $id)
    {
        try {
            $promotion = Promotion::with('uploader')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $promotion
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Promotion not found'
            ], 404);
        }
    }

    /**
     * Update the specified promotion (Staff/Admin only)
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'sometimes|required|url',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $promotion = Promotion::findOrFail($id);
            $promotion->update($request->all());
            $promotion->load('uploader');

            return response()->json([
                'success' => true,
                'message' => 'Promotion updated successfully',
                'data' => $promotion
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating promotion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified promotion (Staff/Admin only)
     */
    public function destroy(string $id)
    {
        try {
            $promotion = Promotion::findOrFail($id);
            $promotion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Promotion deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting promotion: ' . $e->getMessage()
            ], 500);
        }
    }
}
