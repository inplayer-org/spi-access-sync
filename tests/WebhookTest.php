<?php

use Laravel\Lumen\Testing\DatabaseMigrations;

class WebhookTest extends TestCase {

    use DatabaseMigrations;

    /** @test */
    public function it_rejects_request_without_header()
    {
        $this->post('/webhook', [ 'test' => 'value' ])
            ->assertResponseStatus(403);
    }

    /** @test */
    public function it_denies_request_with_correct_header_but_wrong_value()
    {
        $this->post('/webhook', [ 'test' => 'value' ], [ 'HTTP_X_INPLAYER_SIGNATURE' => '' ])
            ->assertResponseStatus(403);
    }

    /** @test */
    public function it_accepts_the_correct_header()
    {
        $data = [
            'id'                         => 'WHE-jAlBR7bGKGIV2mSr',
            'created'                    => 1559906599,
            'type'                       => 'subscribe.success',
            'version'                    => '2.4.2',
            'resource[transaction]'      => 'S-S8CqAw18ihqbgYsxCjIly3MwQ-ST',
            'resource[description]'      => '12345',
            'resource[email]'            => 'example@email.com',
            'resource[customer_id]'      => 27288,
            'resource[formatted_amount]' => '€10.00',
            'resource[amount]'           => 10.00,
            'resource[currency_iso]'     => 'EUR',
            'resource[status]'           => 'success',
            'resource[timestamp]'        => 1559906598,
            'resource[code]'             => 200,
            'resource[access_fee_id]'    => '3936',
            'resource[previewTitle]'     => 'ooyala+muse+mp4',
        ];

        $headerValue = 'sha256=' . hash_hmac('sha256', http_build_query($data), env('SECRET_KEY'));

        $this->post('/webhook', $data, [ 'HTTP_X_INPLAYER_SIGNATURE' => $headerValue ])
            ->assertResponseOk();

        $this->seeInDatabase('requests', ['platform_id' => $data['id']]);
    }
}
