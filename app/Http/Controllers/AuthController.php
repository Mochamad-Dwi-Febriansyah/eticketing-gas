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
            'role' => 'required|in:super_admin,admin_cabang,user',
            'branch_id' => 'nullable|exists:branches,id',
            'street_address' => 'nullable|string|max:255',
            'subdistrict' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'village' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'result' => $validator->errors()
            ], 422);
        }

        // Generate OTP
        $otp = rand(100000, 999999);
        $otp_expires_at = Carbon::now()->addMinutes(10);

        $user = User::create([ 
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'nik' => $request->nik,
            'kk' => $request->kk,
            'phone' => $request->phone,
            'role' => $request->role,
            'branch_id' => $request->branch_id,
            'street_address' => $request->street_address,
            'subdistrict' => $request->subdistrict,
            'district' => $request->district,
            'city' => $request->city,
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
            'phone' => 'required|string|max:20|exists:users,phone',
            'otp' => 'required|string|size:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'result' => $validator->errors()
            ], 422);
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user || $user->otp !== $request->otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP',
                'result' => null
            ], 401);
        }

        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'OTP expired',
                'result' => null
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
            'phone' => 'required|string|max:20|exists:users,phone',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'result' => $validator->errors()
            ], 422);
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number or password',
                'result' => null
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'User not verified. Please enter OTP',
                'result' => null
            ], 403);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'result' => [
                'token' => $token,
                // 'user' => $user
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
