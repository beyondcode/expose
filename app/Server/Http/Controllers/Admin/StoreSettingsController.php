<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\ConnectionManager;
use App\Http\Controllers\Controller;
use App\Server\Configuration;
use Clue\React\SQLite\Result;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Ratchet\ConnectionInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use function GuzzleHttp\Psr7\str;
use function GuzzleHttp\Psr7\stream_for;

class StoreSettingsController extends AdminController
{
    /** @var Configuration */
    protected $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        config()->set('expose.admin.validate_auth_tokens', (bool)$request->get('validate_auth_tokens'));

        $messages = $request->get('messages');

        config()->set('expose.admin.messages.invalid_auth_token', Arr::get($messages, 'invalid_auth_token'));

        config()->set('expose.admin.messages.subdomain_taken', Arr::get($messages, 'subdomain_taken'));

        config()->set('expose.admin.messages.message_of_the_day', Arr::get($messages, 'message_of_the_day'));

        $httpConnection->send(
            respond_json([
                'configuration' => $this->configuration,
            ])
        );
    }
}
