<?php

namespace App\Client\Http\Modifiers;

use App\Client\Configuration;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Arr;
use Psr\Http\Message\RequestInterface;
use Ratchet\Client\WebSocket;

class CheckBasicAuthentication
{
    /** @var Configuration */
    protected $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function handle(RequestInterface $request, ?WebSocket $proxyConnection): ?RequestInterface
    {
        if (! $this->requiresAuthentication() || is_null($proxyConnection)) {
            return $request;
        }

        $username = $this->getAuthorizationUsername($request);

        if (is_null($username)) {
            $proxyConnection->send(
                Message::toString(new Response(401, [
                    'WWW-Authenticate' => 'Basic realm=Expose',
                ], 'Unauthorized'))
            );
            $proxyConnection->close();

            return null;
        }

        return $request;
    }

    protected function getAuthorizationUsername(RequestInterface $request): ?string
    {
        $authorization = $this->parseAuthorizationHeader(Arr::get($request->getHeaders(), 'authorization.0', ''));
        $credentials = $this->getCredentials();

        if (empty($authorization)) {
            return null;
        }

        if (! array_key_exists($authorization['username'], $credentials)) {
            return null;
        }

        if ($credentials[$authorization['username']] !== $authorization['password']) {
            return null;
        }

        return $authorization['username'];
    }

    protected function parseAuthorizationHeader(string $header)
    {
        if (strpos($header, 'Basic') !== 0) {
            return;
        }

        $header = base64_decode(substr($header, 6));

        if ($header === false) {
            return;
        }

        $header = explode(':', $header, 2);

        return [
            'username' => $header[0],
            'password' => isset($header[1]) ? $header[1] : null,
        ];
    }

    protected function requiresAuthentication(): bool
    {
        return ! empty($this->getCredentials());
    }

    protected function getCredentials()
    {
        if (is_null($this->configuration->basicAuth())) {
            return [];
        }

        try {
            $credentials = explode(':', $this->configuration->basicAuth());

            return [
                $credentials[0] => $credentials[1],
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}
