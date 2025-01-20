<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopDeliveryZipcode extends Model
{
    use HasFactory;

    // Define the table if it's not named conventionally
    protected $table = 'shop_delivery_zipcodes';

    // Define the primary key (if not 'id')
    protected $primaryKey = 'id';

    // Define the columns that are mass assignable
    protected $guarded = [];

    // Define the inverse of the relationship
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }
}