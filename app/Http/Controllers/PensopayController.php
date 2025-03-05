<?php

namespace App\Http\Controllers;

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
        try {
            $orderId = 'ORDER_' . time(); 
    
            $response = $this->client->post('payments', [
                'json' => [
                    'amount'       => $request['amount']*100,
                    'currency'     => 'DKK',
                    'order_id'     => $orderId, 
                    "autocapture" => true,
                    "callback_url" => route('payment.callback'), 
                    "cancel_url" => $request['success_url'], 
                    "success_url"=> "https://yumz.dk/cancel",
                    "locale"=> "da-DK", 
                    'methods'      => ['card', 'mobilepay','anyday'],
                    'locale'       => 'en_US',
                    "testmode" => true, 

                ]
            ]);
    
            return response()->json(json_decode($response->getBody(), true));
        } catch (RequestException $e) {
            return response('Error: ' . $e->getMessage(), 500);
        }
    }
    
    // Handle Pensopay callback
    public function handleCallback(Request $request)
    {
        \Log::info('Payment Callback:', $request->all());

        if ($request->event === 'paymentAuthorized') {
            // Update order/payment status in the database
        }

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
