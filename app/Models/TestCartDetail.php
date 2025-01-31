<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestCartDetail extends Model
{
    use HasFactory;

    // Use guarded to prevent mass-assignment on specific attributes
    protected $guarded = ['id'];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    // Assuming you have a Stock model (optional based on your system setup)
    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }
}
