<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class GasStocks extends Model
{
    use HasUuids, SoftDeletes;
    protected $table = 'gas_stocks';
    protected $guarded = ['id'];
}
