<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TradeAccount extends Model
{
    
    protected $fillable = ['name', 'balance'];

    public function user() {
        return $this->belongsTo('App\User');
    }

}
