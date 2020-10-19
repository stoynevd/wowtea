<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parking extends Model
{

    use SoftDeletes;

    protected $fillable = [
        'car_reg_number',
        'entry_date',
        'exit_date',
        'payed_amount'
    ];

}
