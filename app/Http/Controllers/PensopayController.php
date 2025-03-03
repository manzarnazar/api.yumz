<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PensoPayService;

class PensopayController extends Controller
{
    protected $pensopay;

    public function __construct(PensoPayService $pensopay)
    {
        $this->pensopay = $pensopay;
    }

    // Create a new payment
    public function createPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string|max:30',
            'amount'   => 'required|integer|min:1',
            'currency' => 'nullable|string|size:3'
        ]);

        $response = $this->pensopay->createPayment(
            $request->order_id,
            $request->amount,
            $request->currency ?? 'DKK'
        );

        return response()->json($response);
    }

    // Handle Pensopay callback
    public function handleCallback(Request $request)
    {
        \Log::info('Pensopay Callback:', $request->all());

        return response()->json(['status' => 'received']);
    }
}
