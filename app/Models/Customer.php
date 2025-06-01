<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'document', 'phone', 'address', 'ip', 'asaas_customer_id'
    ];

    protected $casts = [
        'address' => 'array'
    ];
}