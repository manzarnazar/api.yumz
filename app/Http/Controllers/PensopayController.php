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
            $response = $this->client->post('payments', [
                'json' => [
                    'amount'       => 500, // Amount in lowest currency unit
                    'currency'     => 'DKK',
                    'callback_url' => 'https://example.com/callback',
                    'cancel_url'   => 'https://example.com/cancel',
                    'success_url'  => 'https://example.com/success',
                    'order_id'     => '1234',
                    'methods'      => ['card', 'mobilepay', 'googlepay', 'applepay'],
                    'locale'       => 'en_US'
                ]
            ]);

         
            return response()->json(json_decode($response->getBody(), true));
        } catch (RequestException $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Handle Pensopay callback
    public function handleCallback(Request $request)
    {
        \Log::info('Pensopay Callback:', $request->all());

        return response()->json(['status' => 'received']);
    }
}
