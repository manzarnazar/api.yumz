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
                    "callback_url" => "https://api.yumz.dk/callback", 
                    "cancel_url" => "https://api.yumz.dk/v1/rest/cancel", 
                    "success_url"=> "https://api.yumz.dk/api/v1/rest/success",
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
    public function paymentCallback(Request $request)
    {
        \Log::info('Payment Callback:', $request->all());

        if ($request->event === 'paymentAuthorized') {
            // Update order/payment status in the database
        }

        return response()->json(['message' => 'Callback received'], 200);
    }

    public function paymentSuccess()
    {
        return response()->json(['status' => 'success'], 200);
    }
}
