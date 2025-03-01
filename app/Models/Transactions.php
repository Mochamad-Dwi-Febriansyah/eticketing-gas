<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transactions extends Model
{
    use HasUuids, SoftDeletes;
    protected $table = 'transactions';
    protected $guarded = ['id'];
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // 🔹 Relasi ke User (Seorang user bisa melakukan banyak transaksi)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
