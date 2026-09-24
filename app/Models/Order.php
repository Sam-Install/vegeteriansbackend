<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'items', 'total', 'status',
        'phone', 'delivery_type', 'pickup_point', 'delivery_address', 'payment_method',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}