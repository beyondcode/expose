<?php

namespace App\Server\Support;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Clue\React\Buzz\Browser;

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

    public function forUser($user)
    {
        return $this->browser
            ->get($this->url . '?' . http_build_query($user), [
                'Accept' => 'application/json',
            ])
            ->then(function (ResponseInterface $response) {
                $result = json_decode($response->getBody());

                return $result->message ?? '';
            }, function (Exception $e) {
                return '';
            });
    }
}
