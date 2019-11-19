<?php

namespace App\Http\Controllers;


use App\PaymentLog;
use App\Response;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller {

    public function store( Request $request )
    {
        if ($this->isSuccess($status = $request->input('type')))
        {
            $id = $request->input('resource.description');
            $email = $request->input('resource.email');

            $this->saveWebhookData($id, $email, $status, $request->all());

            if (app()->environment() !== 'testing')
            {
                $this->grantAccess($id, $email);
            }

            return response('Ok', 200);
        }

        return response('Failure', 422);
    }

    // used for testing the GRANT ACCESS ENDPOINT
    public function show( $id, $email )
    {
        $this->grantAccess($id, $email);

        return 'Ok';
    }

    /**
     * Prepare the request and grant access on the new platform.
     *
     * @param $id
     * @param $email
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function grantAccess( $id, $email )
    {
        $this->revokeAccess($email);

        $data = $this->getData($id, $email);

        $uri = env('GRANT_ACCESS_URL') . '?' . http_build_query($this->getParams($data));

        $client = new Client();
        $res = $client->request('GET', $uri);
        $contents = (string) $res->getBody();

        $this->logAPayment($this->getPaymentId($contents), $email);
        $this->saveResponse('grant_access', $contents);
    }

    /**
     * Revoke access for the same payment ID before we grant a new one.
     *
     * @param $email
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function revokeAccess( $email )
    {
        $paymentId = optional(PaymentLog::where('email', $email)->latest()->first())->payment_id;

        if ( ! $paymentId)
        {
            Log::info('No payment registered for this user yet.');

            return;
        }

        $data = json_encode([
            'payment_id'   => $paymentId,
            'payment_type' => 'package',
            'client_email' => $email
        ]);

        $uri = env('REVOKE_ACCESS_URL') . '?' . http_build_query($this->getParams($data));

        $client = new Client();
        $res = $client->request('GET', $uri);
        $contents = (string) $res->getBody();

        $this->saveResponse('revoke_access', $contents);
    }

    private function getParams( $data )
    {
        return [
            'data' => $data,
            'sig'  => base64_encode(hash_hmac('sha1', $data, env('GRANT_ACCESS'), TRUE)),
            'key'  => env('PUBLIC_KEY')
        ];
    }

    private function saveResponse( $type, $payload )
    {
        Response::create([
            'type'    => $type,
            'payload' => $payload
        ]);
    }

    private function getPaymentId( $payload )
    {
        $result = json_decode($payload, true);

        if (isset($result['response']['status']) && $result['response']['status'] === 'success')
        {
            return $result['response']['result']['payment_id'];
        }

        return null;
    }

    private function getData( $id, $email )
    {
        return json_encode([
            'item_id'                 => $id,
            'item_type'               => 'package',
            'email'                   => $email,
            'period_length'           => 1,
            'period_type'             => 'month',
            'recurrent'               => 1,
            'referrer'                => 'new platform rebill',
            'transaction_custom_data' => 'Optional custom data',
            'ga_send_receipt'         => 'F',
        ]);
    }

    /**
     * Does the received data has a status of success.
     *
     * @param $type
     * @return bool
     */
    private function isSuccess( $type )
    {
        return $type === 'subscribe.success';
    }

    /**
     * Save the webhook request data to the database.
     *
     * @param $id
     * @param $email
     * @param $status
     * @param $payload
     */
    private function saveWebhookData( $id, $email, $status, $payload )
    {
        \App\Request::create([
            'platform_id' => $payload['id'],
            'item_id'     => $id,
            'email'       => $email,
            'status'      => $status,
            'payload'     => serialize($payload)
        ]);
    }

    /**
     * Log the payment to the database.
     *
     * @param $paymentId
     * @param $email
     */
    private function logAPayment( $paymentId, $email )
    {
        if ( ! $paymentId)
        {
            Log::error('No payment ID found.');
            $this->saveResponse('revoke_access', 'No payment ID in the reponse.');

            return;
        }

        PaymentLog::create([
            'payment_id' => $paymentId,
            'email'      => $email
        ]);
    }
}
