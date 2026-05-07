<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;
use App\Mail\TicketPurchasedMail;
use Illuminate\Support\Facades\Mail;

class MpesaController extends Controller
{
    /*
    public function callback(Request $request)
    {
        $data = $request->all();
        
        // This logs the raw response so we can see it in storage/logs/laravel.log
        Log::info('Mpesa Callback Received', $data);

        $resultCode = $data['Body']['stkCallback']['ResultCode'];

        if ($resultCode == 0) {
            // Success! 
            // We find the ticket using the 'AccountReference' we sent earlier
            // It looks like "Ticket-123", so we strip "Ticket-" to get the ID
            $reference = $data['Body']['stkCallback']['CheckoutRequestID'];
            
            // For now, let's just find the latest pending ticket from this phone
            $ticket = Ticket::where('status', 'pending')->latest()->first();

            if ($ticket) {
                $ticket->update([
                    'status' => 'paid',
                    'mpesa_reference' => $data['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'] ?? 'RECEIVED'
                ]);
            }
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
        */
   /* public function callback(Request $request)
{
    $data = $request->json()->all();
    Log::info('Mpesa Callback Received', $data);

    $callback = $data['Body']['stkCallback'];
    $resultCode = $callback['ResultCode'];
    $checkoutRequestID = $callback['CheckoutRequestID'];

    // 1. Find the specific ticket that matches this M-Pesa session
    // This requires that you saved checkout_request_id when initiating the push
    $ticket = Ticket::where('checkout_request_id', $checkoutRequestID)->first();

    if (!$ticket) {
        Log::error("Ticket not found for CheckoutRequestID: " . $checkoutRequestID);
        return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Ticket not found']);
    }

    if ($resultCode == 0) {
        // 2. Extract metadata (Receipt Number, Phone, etc.)
        $items = $callback['CallbackMetadata']['Item'];
        $metadata = [];
        foreach ($items as $item) {
            $metadata[$item['Name']] = $item['Value'] ?? null;
        }

        // 3. Update Ticket Status
        $ticket->update([
            'status' => 'paid',
            'mpesa_reference' => $metadata['MpesaReceiptNumber'] ?? null, 
            ]);
           // Give the database a millisecond to breathe
    $ticket->refresh();
         try {
        // Send the mail
        Mail::to($ticket->buyer_email)->send(new TicketPurchasedMail($ticket));
        Log::info("Ticket email sent successfully.");
    } catch (\Exception $e) {
        // This is where the "Why" is hidden!
        Log::error("EMAIL ATTEMPT FAILED: " . $e->getMessage());
    }


        // 4. Record the full transaction details
        \App\Models\MpesaTransaction::create([
            'ticket_id'           => $ticket->id,
            'merchant_request_id' => $callback['MerchantRequestID'],
            'checkout_request_id' => $checkoutRequestID,
            'result_code'         => $resultCode,
            'result_desc'         => $callback['ResultDesc'],
            'mpesa_receipt'       => $metadata['MpesaReceiptNumber'] ?? null,
            'amount'              => $metadata['Amount'] ?? 0,
            'phone_number'        => $metadata['PhoneNumber'] ?? null,
            'mpesa_receipt' => $metadata['MpesaReceiptNumber'] ?? null,
            'callback_payload'    => json_encode($data)
        ]);
    } else {
        $ticket->update(['status' => 'failed']);
    }

    return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
}*/
public function callback(Request $request)
{
    $data = $request->json()->all();
    Log::info('Mpesa Callback Received', $data);

    $callback = $data['Body']['stkCallback'];
    $resultCode = $callback['ResultCode'];
    $checkoutRequestID = $callback['CheckoutRequestID'];

    $ticket = Ticket::where('checkout_request_id', $checkoutRequestID)->first();

    if (!$ticket) {
        Log::error("Ticket not found for CheckoutRequestID: " . $checkoutRequestID);
        return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Ticket not found']);
    }

    // Process Success
    if ($resultCode == 0) {
        $items = $callback['CallbackMetadata']['Item'];
        $metadata = [];
        foreach ($items as $item) {
            $metadata[$item['Name']] = $item['Value'] ?? null;
        }

        $ticket->update([
            'status' => 'paid',
            'mpesa_reference' => $metadata['MpesaReceiptNumber'] ?? null, 
        ]);

        // IMPORTANT: Load the event relationship for the PDF/Email
        $ticket->load('event');

        // Record the transaction
        \App\Models\MpesaTransaction::create([
            'ticket_id'           => $ticket->id,
            'merchant_request_id' => $callback['MerchantRequestID'],
            'checkout_request_id' => $checkoutRequestID,
            'result_code'         => $resultCode,
            'result_desc'         => $callback['ResultDesc'],
            'amount'              => $metadata['Amount'] ?? 0,
            'phone_number'        => $metadata['PhoneNumber'] ?? null,
            'mpesa_receipt'       => $metadata['MpesaReceiptNumber'] ?? null,
            'callback_payload'    => json_encode($data)
        ]);

        // Trigger the Email
        try {
            Mail::to($ticket->buyer_email)->send(new TicketPurchasedMail($ticket));
            Log::info("Ticket email sent to: " . $ticket->buyer_email);
        } catch (\Exception $e) {
            Log::error("EMAIL ATTEMPT FAILED: " . $e->getMessage());
        }

    } else {
        // Process Failure
        $ticket->update(['status' => 'failed']);
        Log::warning("STK Push Failed for Ticket: " . $ticket->id);
    }

    return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
}


}
