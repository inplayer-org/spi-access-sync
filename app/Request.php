<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'platform_id', 'item_id', 'email', 'status', 'payload'
    ];

    public function getRouteKeyName()
    {
        return 'payload';
    }

    public function getRouteKey()
    {
        return 'payload';
    }

}
