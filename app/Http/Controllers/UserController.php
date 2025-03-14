<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
     // Get all users
     public function index()
     {
         $users = User::all();
 
         return response()->json([
             'success' => true,
             'message' => 'User list retrieved successfully',
             'result' => $users
         ]);
     }
 
     // Get user by ID
     public function show($id)
     {
         $user = User::findOrFail($id);
 
         return response()->json([
             'success' => true,
             'message' => 'User retrieved successfully',
             'result' => $user
         ]);
     }
 
     // Create new user
     public function store(Request $request)
     {
        $validator = Validator::make($request->all(), [
             'role' => 'required|in:super_admin,admin_cabang,user',
             'name' => 'required|string|max:255',
             'nik' => 'required|string|size:16|unique:users,nik',
             'kk' => 'required|string|size:16|unique:users,kk',
             'phone' => 'required|string|max:20|unique:users,phone',
             'email' => 'required|string|email|unique:users,email',
             'password' => 'required|string|min:8',
             'branch_id' => 'nullable|exists:branches,id',
             'street_address' => 'required|string|max:255',
             'village' => 'required|string|max:255',
             'district' => 'required|string|max:255',
             'city' => 'required|string|max:255',
             'province' => 'required|string|max:255',
             'postal_code' => 'required|string|max:10',
         ]);
         if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'result' => $validator->errors()
            ], 422);
        }
 
         $otp = rand(100000, 999999);
 
         $user = User::create([  
             'role' => $request->role,
             'name' => $request->name,
             'nik' => $request->nik,
             'kk' => $request->kk,
             'phone' => $request->phone,
             'email' => $request->email,
             'password' => Hash::make($request->password),
             'branch_id' => $request->branch_id,
             'otp' => $otp,
             'otp_expires_at' => now()->addMinutes(10),
             'street_address' => $request->street_address,
             'village' => $request->village,
             'district' => $request->district,
             'city' => $request->city,
             'province' => $request->province,
             'postal_code' => $request->postal_code,
         ]);
 
         return response()->json([
             'success' => true,
             'message' => 'User created successfully. Please verify OTP.',
             'result' => $user
         ], 201);
     }
 
     // Update user
     public function update(Request $request, $id)
     {
         $user = User::findOrFail($id);
 
         $validator = Validator::make($request->all(), [
             'role' => 'sometimes|in:super_admin,admin_cabang,user',
             'name' => 'sometimes|string|max:255',
             'nik' => 'sometimes|string|size:16|unique:users,nik,' . $id,
             'kk' => 'sometimes|string|size:16|unique:users,kk,' . $id,
             'phone' => 'sometimes|string|max:20|unique:users,phone,' . $id,
             'email' => 'sometimes|string|email|unique:users,email,' . $id,
             'password' => 'sometimes|string|min:8',
             'branch_id' => 'nullable|exists:branches,id',
             'street_address' => 'sometimes|string|max:255',
             'village' => 'sometimes|string|max:255',
             'district' => 'sometimes|string|max:255',
             'city' => 'sometimes|string|max:255',
             'province' => 'sometimes|string|max:255',
             'postal_code' => 'sometimes|string|max:10',
         ]);
         if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'result' => $validator->errors()
            ], 422);
        }
 
         if ($request->has('password')) {
             $request->merge(['password' => Hash::make($request->password)]);
         }
 
         $user->update($request->all());
 
         return response()->json([
             'success' => true,
             'message' => 'User updated successfully',
             'result' => $user
         ]);
     }
 
     // Delete user (Soft Delete)
     public function destroy($id)
     {
         $user = User::findOrFail($id);
         $user->delete();
 
         return response()->json([
             'success' => true,
             'message' => 'User deleted successfully',
             'result' => null
         ]);
     }
 
     // Restore soft-deleted user
     public function restore($id)
     {
         $user = User::onlyTrashed()->findOrFail($id);
         $user->restore();
 
         return response()->json([
             'success' => true,
             'message' => 'User restored successfully',
             'result' => $user
         ]);
     }

     // user
     public function indexByBranch(Request $request)
{
    $user = Auth::user();

    if ($user->role !== 'admin_cabang') {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized',
            'result' => null
        ], 403);
    }

    $users = User::where('branch_id', $user->branch_id)->where('role','user')->get();

    return response()->json([
        'success' => true,
        'message' => 'List of users in branch',
        'result' => $users
    ]);
}

}
