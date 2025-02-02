<?php
namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartDetail;
use Illuminate\Http\Request;
use DB;

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
        ]);

        // Calculate total price using raw SQL query
        $total_price = $this->calculateTotalPrice($request->cart_items);

        // Create a new cart for the guest user
        $cart = Cart::create([
            'guest_id' => $request->guest_id,
            'shop_id' => $request->shop_id,
            'total_price' => $total_price,  // Use the calculated total price
            'status' => 1, // Active cart
            'currency_id' => $request->currency_id, // Use provided currency ID
            'rate' => 1, // Default rate, or fetch dynamically if needed
            'group' => 0, // Default group, can be changed if needed
        ]);

        // Add each item to the cart_details table
        foreach ($request->cart_items as $item) {
            CartDetail::create([
                'user_cart_id' => $cart->id,
                'stock_id' => $item['stock_id'],  // Assuming 'stock_id' refers to the product stock
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'bonus' => $item['bonus'] ?? 0,  // Default bonus to 0 if not set
                'discount' => $item['discount'] ?? 0,  // Default discount to 0 if not set
                'bonus_type' => $item['bonus_type'] ?? null,  // Default to null if not set
            ]);
        }

        return response()->json(['cart_id' => $cart->id, 'total_price' => $cart->total_price]);
    }

    // Helper function to calculate total price
    private function calculateTotalPrice($cartItems)
    {
        // Use raw SQL query to calculate the total price
        $total = 0;

        foreach ($cartItems as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return $total;
    }

    // Get the cart details for a guest user
    public function getCart($guest_id)
    {
        // Using a raw SQL query to fetch cart details
        $cart = DB::table('carts')
            ->where('guest_id', $guest_id)
            ->leftJoin('cart_details', 'carts.id', '=', 'cart_details.user_cart_id')
            ->select('carts.*', DB::raw('SUM(cart_details.price * cart_details.quantity) as total_price'))
            ->groupBy('carts.id')
            ->first();

        if ($cart) {
            // Fetch detailed cart items
            $cartDetails = DB::table('cart_details')
                ->where('user_cart_id', $cart->id)
                ->get();

            return response()->json([
                'cart' => $cart,
                'cart_details' => $cartDetails
            ]);
        } else {
            return response()->json(['message' => 'Cart not found'], 404);
        }
    }
}
