<?php

namespace App\Http\Controllers;

use App\Models\GasStocks;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrdersController extends Controller
{
    public function index()
    {
        $orders = Order::with(['user', 'branch'])->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'List of orders',
            'result' => $orders
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:branches,id',
            'gas_type' => 'required|in:3kg,5kg,12kg',
            'quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
            'pickup_date' => 'nullable|date|after:today',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'result' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Cek stok gas di cabang
            $gasStock = GasStocks::where('branch_id', $request->branch_id)
                ->where('gas_type', $request->gas_type)
                ->first();

            if (!$gasStock || $gasStock->stock < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock at this branch',
                ], 400);
            }

            // Kurangi stok gas
            $gasStock->decrement('stock', $request->quantity);

            // Buat order baru
            $order = Order::create([ 
                'user_id' => $request->user_id,
                'branch_id' => $request->branch_id,
                'status' => 'pending',
                'quantity' => $request->quantity,
                'total_price' => $request->total_price,
                'pickup_date' => $request->pickup_date,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'result' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $order = Order::with(['user', 'branch'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Order details',
            'result' => $order
        ]);
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,approved,rejected,completed',
            'pickup_date' => 'nullable|date|after:today',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'result' => $validator->errors()
            ], 422);
        }

        $order->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'result' => $order
        ]);
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully'
        ]);
    }

    // admin cabang
        // 🔹 Lihat daftar pesanan di cabang sendiri
        public function indexByBranch()
        {
            $user = Auth::user();
            $orders = Order::where('branch_id', $user->branch_id)->get();
    
            return response()->json([
                'success' => true,
                'message' => 'Orders for this branch',
                'result' => $orders
            ]);
        }
    
        // 🔹 Update status pesanan di cabang sendiri
        public function updateStatus(Request $request, $id)
        {
            $user = Auth::user();
            $order = Order::where('id', $id)->where('branch_id', $user->branch_id)->first();
    
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized or order not found'
                ], 403);
            }
    
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:approved,rejected,completed',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'result' => $validator->errors()
                ], 422);
            }
            $order->update(['status' => $request->status]);
    
            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'result' => $order
            ]);
        }

        //user 
        public function storeByUser(Request $request)
        {
            $user = Auth::user();
    
            $validator = Validator::make($request->all(), [
                'branch_id' => 'required|exists:branches,id',
                'quantity' => 'required|integer|min:1',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'result' => $validator->errors()
                ], 422);
            }
    
            $order = Order::create([
                'user_id' => $user->id,
                'branch_id' => $request->branch_id,
                'status' => 'pending',
                'quantity' => $request->quantity,
                'total_price' => $request->quantity * 20000, // Contoh harga per tabung
                'pickup_date' => null,
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'result' => $order
            ], 201);
        }
    
        // 🔹 User melihat semua pesanan mereka
        public function indexByUser()
        {
            $user = Auth::user();
            $orders = Order::where('user_id', $user->id)->get();
    
            return response()->json([
                'success' => true,
                'message' => 'Orders retrieved successfully',
                'result' => $orders
            ]);
        }
    
        // 🔹 User melihat detail pesanan mereka
        public function showByUser($id)
        {
            $user = Auth::user();
            $order = Order::where('id', $id)->where('user_id', $user->id)->first();
    
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Order details retrieved successfully',
                'result' => $order
            ]);
        }
}
