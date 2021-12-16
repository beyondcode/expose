<?php

namespace App\Server\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;

abstract class AdminController extends Controller
{
    protected function shouldHandleRequest(Request $request, ConnectionInterface $httpConnection): bool
    {
        try {
            $authorization = Str::after($request->header('Authorization', ''), 'Basic ');
            $authParts = explode(':', base64_decode($authorization), 2);
            [$user, $password] = $authParts;

            if (! $this->credentialsAreAllowed($user, $password)) {
                throw new \InvalidArgumentException('Invalid Login');
            }

            return true;
        } catch (\Throwable $e) {
            $httpConnection->send(Message::toString(new Response(401, [
                'WWW-Authenticate' => 'Basic realm="Expose"',
            ])));

            $httpConnection->close();
        }

        return false;
    }

    protected function credentialsAreAllowed(string $user, string $password)
    {
        return config('expose.admin.users.'.$user) === $password;
    }
}
