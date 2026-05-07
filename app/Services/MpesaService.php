<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;


class MpesaService
{
    // Step 1: Get the "Day Pass" (Access Token)
    public static function getAccessToken()
    {
        $url = env('MPESA_ENV') === 'sandbox'
            ? 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
            : 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $response = Http::withBasicAuth(env('MPESA_CONSUMER_KEY'), env('MPESA_CONSUMER_SECRET'))
            ->get($url);

        if ($response->failed() || !isset($response->json()['access_token'])) {
            \Log::error('M-Pesa Access Token Error', ['response' => $response->body()]);
            throw new \Exception('Failed to get M-Pesa access token.');
        }

        return $response->json()['access_token'];
    }

    // Step 2: Send the STK Push request
    public static function stkPush($ticket)
    {
        $accessToken = self::getAccessToken();
        $timestamp = Carbon::now()->format('YmdHis');

        // Password = Shortcode + Passkey + Timestamp (encoded in base64)
        $password = base64_encode(env('MPESA_SHORTCODE') . env('MPESA_PASSKEY') . $timestamp);

        $url = env('MPESA_ENV') === 'sandbox'
            ? 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
            : 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

        $payload = [
            'BusinessShortCode' => env('MPESA_SHORTCODE'),
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int)$ticket->amount_paid,
            'PartyA' => $ticket->buyer_phone,
            'PartyB' => env('MPESA_SHORTCODE'),
            'PhoneNumber' => $ticket->buyer_phone,
            'CallBackURL' => env('MPESA_CALLBACK_URL'),
            'AccountReference' => 'Ticket-' . $ticket->id,
            'TransactionDesc' => 'Event Ticket Purchase',
        ];

        $response = Http::withToken($accessToken)->post($url, $payload);
        \Log::info('M-Pesa STK Push Payload:', $payload);
        \Log::info('M-Pesa Response:', $response->json());

        if ($response->failed()) {
            \Log::error('M-Pesa STK Push Error', ['response' => $response->body()]);
            return [
                'error' => true,
                'errorMessage' => 'Failed to initiate STK Push',
                'details' => $response->body(),
            ];
        }

        return $response->json();
    }
}
