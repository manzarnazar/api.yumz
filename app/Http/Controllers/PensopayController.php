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
            'base_uri' => 'https://api.pensopay.com/v2/', // FIXED base_uri
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey, // FIXED Authorization
                'Content-Type'  => 'application/json'
            ]
        ]);
    }

    // Create a new payment
    public function createPayment(Request $request)
    {
        try {
            $response = $this->client->post('payments', [ // FIXED endpoint
                'json' => [
                    'payment' => [
                        'amount'       => 500, // Static for testing
                        'currency'     => 'DKK',
                        'callback_url' => 'https://example.com/callback', // Static for testing
                        'cancel_url'   => 'https://example.com/cancel',
                        'success_url'  => 'https://example.com/success',
                        'order'        => [
                            'order_id' => '1234'
                        ],
                        'methods'      => ['card', 'mobilepay', 'googlepay', 'applepay'],
                        'locale'       => 'en_US'
                    ]
                ]
            ]);

            return 'Payment request successful!';
        } catch (RequestException $e) {
            return 'Error: ' . $e->getMessage(); // Return text response
        }
    }
}
