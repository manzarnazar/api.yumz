<?php

namespace App\Http\Controllers;
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;


class PensopayController extends Controller
{
    private $client;
    private $apiKey;

    public function __construct()
    {
        $this->apiKey = 'cd24a85dc8d033f488144d56daf1be129f7fb35288622b2eb33c5262309ed9d1';
        $this->client = new Client([
            'base_uri' => 'https://api.pensopay.com/v2/payments',
            'headers' => [
                'Authorization' => 'Bearer ',$this->apiKey,
                'Content-Type'  => 'application/json'
            ]
        ]);
    }

    // Create a new payment
    public function createPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string|max:30',
            'amount'   => 'required|integer|min:1',
            'currency' => 'nullable|string|size:3'
        ]);

        try {
            $response = $this->client->post('payments', [
                'json' => [
                    'payment' => [
                        'amount'       => $request->amount,
                        'currency'     => $request->currency ?? 'DKK',
                        'callback_url' => route('pensopay.callback'),
                        'cancel_url'   => route('pensopay.cancel'),
                        'success_url'  => route('pensopay.success'),
                        'order' => [
                            'order_id' => $request->order_id
                        ],
                        'methods'      => ['card', 'mobilepay', 'googlepay', 'applepay'],
                        'locale'       => 'en_US',
                        'branding_id'  => 'a6692855-ad7a-4f35-81b8-7089988f79b6'
                    ]
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
