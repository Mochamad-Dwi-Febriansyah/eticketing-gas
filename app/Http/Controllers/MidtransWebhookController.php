<?php

namespace App\Http\Controllers;

use App\Models\Transactions;
use Illuminate\Http\Request;

class MidtransWebhookController extends Controller
{
    public function handle(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Webhook Midtrans: ', $request->all()); 
        $payload = $request->all();

        // 1. Validasi Signature Key
        $serverKey = config('midtrans.server_key');
        $expectedSignature = hash('sha512', 
            $payload['order_id'] .
            $payload['status_code'] .
            $payload['gross_amount'] .
            $serverKey
        );

        if ($payload['signature_key'] !== $expectedSignature) {
            \Illuminate\Support\Facades\Log::error('Invalid Signature', [
                'expected' => $expectedSignature,
                'received' => $payload['signature_key']
            ]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // 2. Cari transaksi
        $transaction = Transactions::where('midtrans_order_id', $payload['order_id'])->first();

        if (!$transaction) {
            \Illuminate\Support\Facades\Log::error('Transaction not found', ['order_id' => $payload['order_id']]);
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // 3. Update status transaksi
        $transactionStatus = $payload['transaction_status'];
        if ($transactionStatus === 'settlement') {
            $transaction->update(['status' => 'paid']);
            if ($transaction->order) {
                $transaction->order->update(['status' => 'completed']);
            }
        } elseif (in_array($transactionStatus, ['cancel', 'expire', 'failure'])) {
            $transaction->update(['status' => 'failed']);
        } elseif ($transactionStatus === 'pending') {
            $transaction->update(['status' => 'pending']);
        }

        \Illuminate\Support\Facades\Log::info('Transaction updated', [
            'transaction_id' => $transaction->id,
            'status' => $transaction->status
        ]);

        return response()->json(['message' => 'Webhook handled'], 200);
    }
}
