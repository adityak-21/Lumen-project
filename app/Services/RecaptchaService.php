<?php

namespace App\Services;

use GuzzleHttp\Client;

class RecaptchaService
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function verifyCaptcha($token)
    {
        $response = $this->client->post('https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'secret' => env('RECAPTCHA_SECRET'),
                'response' => $token,
            ],
        ]);

        $result = json_decode($response->getBody(), true);
        return $result['success'] ?? false;
    }
}