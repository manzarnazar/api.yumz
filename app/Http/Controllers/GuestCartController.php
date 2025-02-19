<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\UserCart;
use Illuminate\Http\Request;

class GuestCartController extends Controller
{
    public function store(Request $request)
{
    // Validate incoming request
    $request->validate([
        'guest_id' => 'required|exists:guest_users,id',
        'user_id' => 'required|exists:users,id',
        'shop_id' => 'required|exists:shops,id',
        'currency_id' => 'required|exists:currencies,id',
        'name' => 'nullable|string',
        'cart_items' => 'required|array',
        'cart_items.*.stock_id' => 'required|exists:stocks,id',
        'cart_items.*.quantity' => 'required|integer|min:1',
        'cart_items.*.price' => 'required|numeric|min:0',
        'cart_items.*.bonus' => 'nullable|numeric|min:0',
        'cart_items.*.discount' => 'nullable|numeric|min:0',
        'cart_items.*.bonus_type' => 'nullable|string',
    ]);

    // Calculate total price
    $totalPrice = $this->calculateTotalPrice($request->cart_items);

    // Create the cart
    $cart = Cart::create([
        'guest_id' => $request->guest_id,
        'shop_id' => $request->shop_id,
        'owner_id' => $request->user_id,
        'total_price' => $totalPrice,
        'status' => 1,
        'currency_id' => $request->currency_id,
        'rate' => 1,
        'group' => 0,
    ]);

    // Create the user cart
    $usercart = UserCart::create([
        'cart_id' => $cart->id,
        'status' => 1,
        'user_id' => $request->user_id,
        'name' => $request->name
    ]);

    // Add cart items
    foreach ($request->cart_items as $item) {
        CartDetail::create([
            'user_cart_id' => $usercart->id,
            'stock_id' => $item['stock_id'],
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'bonus' => $item['bonus'] ?? 0,
            'discount' => $item['discount'] ?? 0,
            'bonus_type' => $item['bonus_type'] ?? null,
        ]);
    }

    return response()->json(['cart_id' => $cart->id, 'cart_uuid' => $usercart->uuid]);
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
        $cart = Cart::where('guest_id', $guest_id)->with('cartDetails')->first();

        if ($cart) {
            return response()->json($cart);
        } else {
            return response()->json(['message' => 'Cart not found'], 404);
        }
    }
}
