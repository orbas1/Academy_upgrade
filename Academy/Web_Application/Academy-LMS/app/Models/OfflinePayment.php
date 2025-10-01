<?php

namespace App\Models;

use App\Casts\EncryptedAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfflinePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'item_type',
        'items',
        'tax',
        'total_amount',
        'coupon',
        'phone_no',
        'bank_no',
        'doc',
        'status',
    ];

    protected $casts = [
        'items' => EncryptedAttribute::class,
        'phone_no' => EncryptedAttribute::class,
        'bank_no' => EncryptedAttribute::class,
    ];
}
