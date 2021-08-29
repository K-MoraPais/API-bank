<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    use HasFactory;
    protected $connection='database';
    protected $table='Deposits';
    protected $primaryKey = "depositId";
    public $timestamps=false;
}
