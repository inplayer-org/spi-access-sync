<?php

namespace App\Http\Controllers;


use App\Response;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class WebhookController extends Controller {

    public function store( Request $request )
    {
        if ($this->isSuccess($status = $request->get('type')))
        {
            $id = $request->get('resource[description]');
            $email = $request->get('resource[email]');

            $this->saveWebhookData($id, $email, $status, $request->all());

            if(app()->environment() !== 'testing')
            {
                $this->sync($this->getData($id, $email));
            }

            return response('Ok', 200);
        }

        return response('Failure', 422);
    }

    public function show( $id, $email )
    {
        $this->sync($this->getData($id, $email));
    }

    private function sync( $data )
    {
        $uri = env('SYNC_URL');

        $params = [
            'data' => $data,
            'sig'  => base64_encode(hash_hmac('sha1', $data, env('GRANT_ACCESS'), TRUE)),
            'key'  => env('PUBLIC_KEY')
        ];

        $uri .= '?' . http_build_query($params);

        $client = new Client();
        $res = $client->request('GET', $uri);
        $contents = (string) $res->getBody();

        $this->saveResponse($contents);
    }

    private function saveResponse( $payload )
    {
        Response::create(compact('payload'));
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

    private function isSuccess( $type )
    {
        return $type === 'subscribe.success';
    }

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
}
