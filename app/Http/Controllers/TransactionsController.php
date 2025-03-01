<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Transactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransactionsController extends Controller
{
     // Get all transactions
     public function index()
     {
         $transactions = Transactions::with('order', 'user')->get();
 
         return response()->json([
             'success' => true,
             'message' => 'List of transactions',
             'result' => $transactions
         ]);
     }
 
     // Get transaction detail by ID
     public function show($id)
     {
         $transaction = Transactions::with('order', 'user')->find($id);
 
         if (!$transaction) {
             return response()->json([
                 'success' => false,
                 'message' => 'Transaction not found'
             ], 404);
         }
 
         return response()->json([
             'success' => true,
             'message' => 'Transaction details',
             'result' => $transaction
         ]);
     }
 
     // Store a new transaction
     public function store(Request $request)
     {
        $validator = Validator::make($request->all(), [
             'order_id' => 'required|exists:orders,id',
             'user_id' => 'required|exists:users,id',
             'payment_method' => 'required|in:cash,bank_transfer,ewallet',
             'amount_paid' => 'required|numeric|min:0',
         ]);
         if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'result' => $validator->errors()
            ], 422);
        }
 
         $order = Order::findOrFail($request->order_id);
 
         // Cek apakah jumlah pembayaran sesuai dengan total harga pesanan
         if ($request->amount_paid < $order->total_price) {
             return response()->json([
                 'success' => false,
                 'message' => 'Amount paid is less than total order price'
             ], 400);
         }
 
         DB::beginTransaction();
         try {
             $transaction = Transactions::create([
                 'order_id' => $request->order_id,
                 'user_id' => $request->user_id,
                 'payment_method' => $request->payment_method,
                 'status' => 'paid',
                 'amount_paid' => $request->amount_paid,
             ]);
 
             // Update status order menjadi completed jika transaksi sukses
             $order->update(['status' => 'completed']);
 
             DB::commit();
 
             return response()->json([
                 'success' => true,
                 'message' => 'Transaction created successfully',
                 'result' => $transaction
             ], 201);
         } catch (\Exception $e) {
             DB::rollBack();
 
             return response()->json([
                 'success' => false,
                 'message' => 'Transaction failed',
                 'error' => $e->getMessage()
             ], 500);
         }
     }
 
     // Update transaction (e.g. update status)
     public function update(Request $request, $id)
     {
         $transaction = Transactions::findOrFail($id);
 
        $validator = Validator::make($request->all(), [
             'status' => 'required|in:pending,paid,failed',
         ]);
         if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'result' => $validator->errors()
            ], 422);
        }
 
         $transaction->update(['status' => $request->status]);
 
         return response()->json([
             'success' => true,
             'message' => 'Transaction updated successfully',
             'result' => $transaction
         ]);
     }
 
     // Soft delete transaction
     public function destroy($id)
     {
         $transaction = Transactions::findOrFail($id);
         $transaction->delete();
 
         return response()->json([
             'success' => true,
             'message' => 'Transaction deleted successfully'
         ]);
     }

     // admin cabang
     public function indexByBranch()
     {
         $user = Auth::user();
         $transactions = Transactions::whereHas('order', function ($query) use ($user) {
             $query->where('branch_id', $user->branch_id);
         })->get();
 
         return response()->json([
             'success' => true,
             'message' => 'Transactions for this branch',
             'result' => $transactions
         ]);
     }
     
     // user
     public function indexByUser()
     {
         $user = Auth::user();
         $transactions = Transactions::where('user_id', $user->id)->get();
 
         return response()->json([
             'success' => true,
             'message' => 'Transactions retrieved successfully',
             'result' => $transactions
         ]);
     }
 
     // 🔹 User melihat detail transaksi mereka
     public function showByUser($id)
     {
         $user = Auth::user();
         $transaction = Transactions::where('id', $id)->where('user_id', $user->id)->first();
 
         if (!$transaction) {
             return response()->json([
                 'success' => false,
                 'message' => 'Transaction not found'
             ], 404);
         }
 
         return response()->json([
             'success' => true,
             'message' => 'Transaction details retrieved successfully',
             'result' => $transaction
         ]);
     }
 
     // 🔹 User melakukan pembayaran (contoh: unggah bukti pembayaran)
     public function pay(Request $request, $id)
     {
         $user = Auth::user();
         $transaction = Transactions::where('id', $id)->where('user_id', $user->id)->first();
 
         if (!$transaction) {
             return response()->json([
                 'success' => false,
                 'message' => 'Transaction not found'
             ], 404);
         }
 
         if ($transaction->status !== 'pending') {
             return response()->json([
                 'success' => false,
                 'message' => 'Payment has already been processed'
             ], 400);
         }
 
        $validator = Validator::make($request->all(), [
             'payment_method' => 'required|in:cash,bank_transfer,ewallet',
             'amount_paid' => 'required|numeric|min:1',
         ]);
         if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'result' => $validator->errors()
            ], 422);
        }
 
         $transaction->update([
             'payment_method' => $request->payment_method,
             'amount_paid' => $request->amount_paid,
             'status' => 'paid',
         ]);
 
         return response()->json([
             'success' => true,
             'message' => 'Payment successful',
             'result' => $transaction
         ]);
     }
}
