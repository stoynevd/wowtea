<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Parking
 * This model is used to represent each parking spot in the parking
 * @package App
 */
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
