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

    // Create a new payment
    public function createPayment(Request $request)
    {
        try {
            $orderId = 'ORDER_' . time(); // Ensure unique order_id
    
            $response = $this->client->post('payments', [
                'json' => [
                    'amount'       => $request['amount'],
                    'currency'     => 'DKK',
                    "order_id" => $request['order_id'], // Unique order ID (required)
                    "autocapture" => true, // Optional: Automatically capture the payment
                    "callback_url" => "https://www.google.com/callback", // Optional: Callback URL
                    "cancel_url" => "https://www.google.com/cancel", // Optional: Cancel URL
                    "success_url"=> "https://www.google.com/success", // Optional: Success URL
                    "locale"=> "da-DK", // Locale for payment window (e.g., "da-DK" for Danish)
                    'methods'      => ['card', 'mobilepay', 'googlepay', 'applepay'],
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
        \Log::info('Pensopay Callback:', $request->all());

        return response()->json(['status' => 'received']);
    }
}
