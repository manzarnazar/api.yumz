<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestCart extends Model
{
    use HasFactory;
    protected $guarded = ['id'];


    public function cartDetails()
    {
        return $this->hasMany(TestCartDetail::class);
    }
}
