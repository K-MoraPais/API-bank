<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;
    protected $connection='database';
    protected $table='transfers';
    protected $primaryKey = "transferId";
    public $timestamps=false;
}
