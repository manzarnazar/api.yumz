<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuestCartController extends Controller
{
    public function store(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'guest_id' => 'required|exists:guest_users,id',  // Ensure the guest ID is valid
            'cart_items' => 'required|array',  // Array of cart items
            'shop_id' => 'required|exists:shops,id',  // Ensure the shop ID is valid
            'currency_id' => 'required|exists:currencies,id',  // Ensure currency ID is valid
            'total_price' => 'required|numeric',  // Ensure the total price is provided and valid
        ]);

        DB::beginTransaction();  // Start transaction

        try {
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

            // Check if cart was created successfully
            if (!$cart) {
                throw new \Exception('Failed to create cart.');
            }

            // Log created cart ID for debugging
            \Log::info('Cart created with ID: ' . $cart->id);

            // Add each item to the cart_details table
            foreach ($request->cart_items as $item) {
                // Log item details for debugging
                \Log::info('Inserting CartDetail for Cart ID: ' . $cart->id . ' and Stock ID: ' . $item['stock_id']);
                
                CartDetail::create([
                    'user_cart_id' => $cart->id,  // Ensure the cart ID is correct
                    'stock_id' => $item['stock_id'],  // Assuming 'stock_id' refers to the product stock
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'bonus' => $item['bonus'] ?? 0,  // Default bonus to 0 if not set
                    'discount' => $item['discount'] ?? 0,  // Default discount to 0 if not set
                    'bonus_type' => $item['bonus_type'] ?? null,  // Default to null if not set
                ]);
            }

            // Commit transaction
            DB::commit();

            return response()->json(['cart_id' => $cart->id, 'total_price' => $cart->total_price]);

        } catch (\Exception $e) {
            // Rollback transaction in case of an error
            DB::rollBack();

            // Log the error message for debugging
            \Log::error('Error during cart creation: ' . $e->getMessage());

            return response()->json(['message' => 'Failed to create cart. ' . $e->getMessage()], 500);
        }
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
