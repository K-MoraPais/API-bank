<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    use HasFactory;
    protected $connection='database';
    protected $table='tokens';
    protected $primaryKey = "tokenId";
    public $timestamps=false;
}
