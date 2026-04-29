<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'product_name',
        'product_price',
        'quantity',
        'total_price',
        'status',
    ];
}
