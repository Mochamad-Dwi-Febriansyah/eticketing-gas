<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'nik' => 'required|string|size:16|unique:users,nik',
            'kk' => 'required|string|size:16|unique:users,kk',
            'phone' => 'required|string|max:20|unique:users,phone', 
            'branch_id' => 'nullable|exists:branches,id',
            'street_address' => 'nullable|string|max:255',
            'subdistrict' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255', 
            'province' => 'nullable|string|max:255',
            'village' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate OTP
        $otp = rand(100000, 999999);
        $otp_expires_at = Carbon::now()->addMinutes(10);

        $user = User::create([ 
            'branch_id' => $request->branch_id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'nik' => $request->nik,
            'kk' => $request->kk,
            'phone' => $request->phone,  
            'street_address' => $request->street_address,
            'subdistrict' => $request->subdistrict,
            'district' => $request->district, 
            'province' => $request->province,
            'village' => $request->village,
            'postal_code' => $request->postal_code,
            'otp' => $otp,
            'otp_expires_at' => $otp_expires_at,
            'is_active' => false, // User tidak aktif sebelum verifikasi OTP
        ]);

        // Simulasikan pengiriman OTP (seharusnya pakai SMS/email API)
        Log::info("OTP untuk {$user->phone}: $otp");

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully, OTP sent',
            'result' => [
                'user_id' => $user->id,
                'otp' => 'Check log for testing' // Hapus di produksi
            ]
        ], 201);
    }

    // Verifikasi OTP
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nik' => 'required|string|size:16|exists:users,nik',
            'otp' => 'required|string|size:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('nik', $request->nik)->first();

        if (!$user || $user->otp !== $request->otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP',
                'errors' => null
            ], 401);
        }

        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'OTP expired',
                'errors' => null
            ], 401);
        }

        // OTP valid, aktifkan akun
        $user->update([
            'otp' => null,
            'otp_expires_at' => null,
            'is_active' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
            'result' => null
        ]);
    }

    // Login dengan OTP
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nik' => 'required|string|max:16',
            'password' => 'required|string|min:6',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }
    
        try {
            // Cari user berdasarkan NIK
            $user = User::where('nik', $request->nik)->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'errors' => null
            ], 404);
        }
    
        // Periksa password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid NIK or password',
                'errors' => null
            ], 401);
        }
    
        // Periksa apakah user sudah aktif
        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'User not verified. Please enter OTP',
                'errors' => null
            ], 403);
        }
    
        // Generate JWT Token
        $token = JWTAuth::fromUser($user);
    
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'result' => [
                'token' => $token,
                'user' => $user // Bisa dikembalikan jika diperlukan
            ]
        ]);
    }
    

    // Logout
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json([
            'success' => true,
            'message' => 'Logout successful',
            'result' => null
        ]);
    }
}
