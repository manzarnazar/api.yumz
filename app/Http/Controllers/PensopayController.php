<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class PensopayController extends Controller
{
    private $client;
    private $apiKey;

    public function __construct()
    {
        $this->apiKey = 'cd24a85dc8d033f488144d56daf1be129f7fb35288622b2eb33c5262309ed9d1';
        $this->client = new Client([
            'base_uri' => 'https://api.pensopay.com/v2/', // Base URL only
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json'
            ]
        ]);
    }


    public function createPayment(Request $request)
{
    Order::where('id', $request->order_id)->update(['status' => 'canceled']);
    $amount = Order::where('order_id', $request->order_id)->first(['total_price']);

    try {

        $orderId = (string) $request['order_id']; // Convert to string

        $response = $this->client->post('payments', [
            'json' => [
                'amount'       => $amount * 100,
                'currency'     => 'DKK',
                'order_id'     => $orderId, // Use the string version
                "autocapture"  => true,
                "callback_url" => route('payment.callback'), 
                "cancel_url"   => $request['cancel_url'],
                "success_url" => $request['success_url'], 
                "locale"       => "da-DK", 
                'methods'      => ['card', 'mobilepay', 'anyday'],
                'locale'       => 'en_US',
                "testmode"    => true, 
            ]
        ]);

        return response()->json(json_decode($response->getBody(), true));
    } catch (RequestException $e) {
        // Return a JSON response with the error message
        return response()->json([
            'error' => 'Payment creation failed',
            'message' => $e->getMessage()
        ], 500);
    }
}
    
    // Handle Pensopay callback
    public function handleCallback(Request $request)
    {
        // Log the incoming callback data for debugging
        \Log::info('Payment Callback:', $request->all());
    
        // Extract the event and resource data from the request
        $event = $request->event;
        $resource = $request->resource;
    
        // Handle different payment events
        if ($event === 'payment.authorized') {
            // Update order status to 'new' for authorized payments

        } elseif ($event === 'payment.captured') {
            // Update order status to 'paid' for captured payments
            Order::where('id', $resource['order_id'])->update(['status' => 'paid']);
        } else {
            // Log unsupported events for further investigation
            \Log::warning('Unsupported payment event:', ['event' => $event]);
        }
    
        // Return a success response
        return response()->json(['message' => 'Callback received'], 200);
    }

    public function paymentSuccess(Request $request)
{
    \Log::info('Payment Success:', $request->all());

    // Validate and update the order/payment status in the database
    if ($request->has('order_id')) {
        $orderId = $request->order_id;

        // Find and update order in the database (example)
        // Order::where('order_id', $orderId)->update(['status' => 'paid']);

        return response()->json([
            'status' => 'success',
            'message' => 'Payment successful',
            'order_id' => $orderId
        ], 200);
    }

    return response()->json(['status' => 'error', 'message' => 'Invalid success response'], 400);
}

}
