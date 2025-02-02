<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartDetail;
use Illuminate\Http\Request;

class GuestCartController extends Controller
{
    public function store(Request $request)
    {
        // Validate incoming request
        $request->validate([
            // 'guest_id' => 'required|exists:guest_users,id',  // Ensure the guest ID is valid
            'cart_items' => 'required|array',  // Array of cart items
            'shop_id' => 'required|exists:shops,id',  // Ensure the shop ID is valid
            'currency_id' => 'required|exists:currencies,id',  // Ensure currency ID is valid
        ]);
    
        // Create a new cart for the guest user
        $cart = Cart::create([
            'guest_id' => $request->guest_id,
            'shop_id' => $request->shop_id,
            'total_price' => $request->total_price,  // Calculate total price
            'status' => 1, // Active cart
            'currency_id' => $request->currency_id, // Use provided currency ID
            'rate' => 1, // Default rate, or fetch dynamically if needed
            'group' => 0, // Default group, can be changed if needed
        ]);
    
        // Add each item to the cart_details table
        foreach ($request->cart_items as $item) {
            CartDetail::create([
                'user_cart_id' => $cart->id,  // Ensure this correctly references the carts table
                'stock_id' => $item['stock_id'],  
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'bonus' => $item['bonus'] ?? 0,  
                'discount' => $item['discount'] ?? 0,  
                'bonus_type' => $item['bonus_type'] ?? null,  
            ]);
        }
    
        return response()->json(['cart_id' => $cart->id, 'total_price' => $cart->total_price]);
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
