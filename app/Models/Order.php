<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasUuids, SoftDeletes;
    protected $table = 'orders';
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔹 Relasi ke Branch (Sebuah pesanan hanya terkait dengan satu cabang)
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // 🔹 Relasi ke Transaction (Sebuah pesanan bisa memiliki banyak transaksi)
    public function transactions()
    {
        return $this->hasMany(Transactions::class);
    }
}
