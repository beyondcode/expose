<?php

namespace App\Client\Callbacks;

use App\Server\Connections\ControlConnection;
use React\Http\Browser;

class WebHookConnectionCallback
{
    /** @var Browser */
    protected $browser;

    public function __construct(Browser $browser)
    {
        $this->browser = $browser;
    }

    public function handle(ControlConnection $connection)
    {
        $this->browser->post(config('expose.admin.connection_callbacks.webhook.url'), [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Signature' => $this->generateWebhookSigningSecret($connection),
        ], json_encode($connection->toArray()));
    }

    protected function generateWebhookSigningSecret(ControlConnection $connection)
    {
        return hash_hmac('sha256', $connection->client_id, config('expose.admin.connection_callbacks.webhook.secret'));
    }
}
