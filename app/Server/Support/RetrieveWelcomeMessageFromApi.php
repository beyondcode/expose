<?php

namespace App\Server\Support;

use App\Server\Connections\ControlConnection;
use Exception;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;

class RetrieveWelcomeMessageFromApi
{
    /** @var Browser */
    protected $browser;

    /** @var string */
    protected $url;

    public function __construct(Browser $browser)
    {
        $this->browser = $browser;

        $this->url = config('expose.admin.welcome_message_api_url');
    }

    public function forUser(ControlConnection $connectionInfo, $user)
    {
        return $this->browser
            ->post($this->url, [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ], json_encode([
                'user' => $user,
                'connectionInfo' => $connectionInfo->toArray(),
            ]))
            ->then(function (ResponseInterface $response) {
                $result = json_decode($response->getBody());

                return $result->message ?? '';
            }, function (Exception $e) {
                return '';
            });
    }
}
