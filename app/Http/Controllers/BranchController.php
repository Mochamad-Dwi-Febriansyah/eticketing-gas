<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
{
    // Get all branches
    public function index()
    {
        $branches = Branch::all();

        return response()->json([
            'success' => true,
            'message' => 'Branch list retrieved successfully',
            'result' => $branches
        ]);
    }

    // Get branch by ID
    public function show($id)
    {
        $branch = Branch::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Branch retrieved successfully',
            'result' => $branch
        ]);
    }

    // Create branch
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'village' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'postal_code' => 'required|string|max:10',
            'is_active' => 'boolean',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'result' => $validator->errors()
            ], 422);
        }

        $branch = Branch::create([ 
            'name' => $request->name,
            'address' => $request->address,
            'village' => $request->village,
            'district' => $request->district,
            'city' => $request->city,
            'province' => $request->province,
            'postal_code' => $request->postal_code,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branch created successfully',
            'result' => $branch
        ], 201);
    }

    // Update branch
    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'village' => 'sometimes|string|max:255',
            'district' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:255',
            'province' => 'sometimes|string|max:255',
            'postal_code' => 'sometimes|string|max:10',
            'is_active' => 'boolean',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'result' => $validator->errors()
            ], 422);
        }

        $branch->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Branch updated successfully',
            'result' => $branch
        ]);
    }

    // Soft delete branch
    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);
        $branch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Branch deleted successfully',
            'result' => null
        ]);
    }

    // Restore soft-deleted branch
    public function restore($id)
    {
        $branch = Branch::onlyTrashed()->findOrFail($id);
        $branch->restore();

        return response()->json([
            'success' => true,
            'message' => 'Branch restored successfully',
            'result' => $branch
        ]);
    }
}
