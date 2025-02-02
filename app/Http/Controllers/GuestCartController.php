<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuestCartController extends Controller
{
    public function store(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'guest_id' => 'required|exists:guest_users,id',
            'cart_items' => 'required|array',
            'shop_id' => 'required|exists:shops,id',
            'currency_id' => 'required|exists:currencies,id',
        ]);

        // Calculate total price
        $total_price = $this->calculateTotalPrice($request->cart_items);

        // Insert cart record
        $cart_id = DB::table('carts')->insertGetId([
            'guest_id' => $request->guest_id,
            'shop_id' => $request->shop_id,
            'total_price' => $total_price,
            'status' => 1,
            'currency_id' => $request->currency_id,
            'rate' => 1,
            'group' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert cart items
        $cart_items = [];
        foreach ($request->cart_items as $item) {
            $cart_items[] = [
                'user_cart_id' => $cart_id,
                'stock_id' => $item['stock_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'bonus' => $item['bonus'] ?? 0,
                'discount' => $item['discount'] ?? 0,
                'bonus_type' => $item['bonus_type'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('cart_details')->insert($cart_items);

        return response()->json(['cart_id' => $cart_id, 'total_price' => $total_price]);
    }

    // Helper function to calculate total price
    private function calculateTotalPrice($cartItems)
    {
        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    // Get the cart details for a guest user
    public function getCart($guest_id)
    {
        $cart = DB::table('carts')
            ->where('guest_id', $guest_id)
            ->first();

        if ($cart) {
            $cart->cart_details = DB::table('cart_details')
                ->where('user_cart_id', $cart->id)
                ->get();

            return response()->json($cart);
        }

        return response()->json(['message' => 'Cart not found'], 404);
    }
}
