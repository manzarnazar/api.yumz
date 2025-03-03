<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class PensoPayService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.pensopay.com/v2/payments',
            'headers' => [
                'Authorization' => 'Bearer cd24a85dc8d033f488144d56daf1be129f7fb35288622b2eb33c5262309ed9d1',
                'Content-Type'  => 'application/json'
            ]
        ]);
    }

    public function createPayment($order_id, $amount, $currency = 'DKK')
    {
        try {
            $response = $this->client->post('payments', [
                'json' => [
                    'payment' => [
                        'amount' => $amount,
                        'currency' => $currency,
                        'callback_url' => route('pensopay.callback'),
                        'cancel_url' => route('pensopay.cancel'),
                        'success_url' => route('pensopay.success'),
                        'order' => [
                            'order_id' => $order_id
                        ],
                        "autocapture" => true, 
                        "locale" => "da-DK", 
                        'methods' => ['card', 'mobilepay', 'googlepay', 'applepay'],
                        "testmode"=> true,
                    ]
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }
}
