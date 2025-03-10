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
            'guest_id' => 'required|exists:guest_users,id',  // Ensure the guest ID is valid
            'user_id' => 'required|exists:users,id',  // Ensure the guest ID is valid
            'cart_items' => 'required|array',  // Array of cart items
            'shop_id' => 'required|exists:shops,id',  // Ensure the shop ID is valid
            'currency_id' => 'required|exists:currencies,id',  // Ensure currency ID is valid
            'name' => 'nullable|string'
        ]);

        $pric = $request->total_price;

        // Create a new cart for the guest user

        $cart = Cart::create([
            'guest_id' => $request->guest_id,
            'owner_id' => $request->user_id,
            'shop_id' => $request->shop_id,
            'total_price' => $request->total_price,  // Calculate total price
            'status' => 1, // Active cart
            'currency_id' => $request->currency_id, // Use provided currency ID
            'rate' => 1, // Default rate, or fetch dynamically if needed
            'group' => 0, // Default group, can be changed if needed
        ]);
        $total_pric= $cart->total_price;
        $usercart = UserCart::create([
            'cart_id' => $cart->id,
            'user_id' => $request->user_id,
            'status' => 1,
            'name'=> $request->name
        ]);


        foreach ($request->cart_items as $item) {
            // Create main cart detail
            $cartDetail = CartDetail::create([
                'user_cart_id' => $usercart->id,
                'stock_id' => $item['stock_id'],  
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'bonus' => $item['bonus'] ?? 0,
                'discount' => $item['discount'] ?? 0,
                'bonus_type' => $item['bonus_type'] ?? null,
            ]);
        
            if (!empty($item['addons'])) {
                foreach ($item['addons'] as $addon) {
                    CartDetail::create([
                        'user_cart_id' => $usercart->id,
                        'stock_id' => $addon['stock_id'], 
                        'quantity' => $addon['quantity'],
                        'price' => $addon['price'],
                        'bonus' => 0, 
                        'discount' => 0, 
                        'bonus_type' => null,
                        'parent_id' => $cartDetail->id // Linking addon to main item
                    ]);
                }
            }
        }
        

        return response()->json(['cart_id' => $cart->id, 'cart_uuid' => $usercart->uuid,"price" => $pric,"total_pric" => $total_pric]);
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
