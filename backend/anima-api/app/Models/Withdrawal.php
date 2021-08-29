<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory;
    protected $connection='database';
    protected $table='Withdrawals';
    protected $primaryKey = "withdrawalId";
    public $timestamps=false;
}
