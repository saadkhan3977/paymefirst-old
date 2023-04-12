<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function order_detail()
    {
        return $this->belongsTo(OrderDetail::class, 'order_number','order_no');
    }
    
    public function shipping()
    {
        return $this->belongsTo(Shipping::class, 'shipping_id');
    }
}
