<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'payment_id', 'email'
    ];

}
