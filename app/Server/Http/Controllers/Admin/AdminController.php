<?php

namespace App\Server\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\str;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;

abstract class AdminController extends Controller
{
    protected function shouldHandleRequest(Request $request, ConnectionInterface $httpConnection): bool
    {
        try {
            $authorization = Str::after($request->header('Authorization'), 'Basic ');
            $authParts = explode(':', base64_decode($authorization), 2);
            [$user, $password] = $authParts;

            if (! $this->credentialsAreAllowed($user, $password)) {
                throw new \InvalidArgumentException('Invalid Login');
            }

            return true;
        } catch (\Throwable $e) {
            $httpConnection->send(str(new Response(401, [
                'WWW-Authenticate' => 'Basic realm="Expose"',
            ])));
        }

        return false;
    }

    protected function credentialsAreAllowed(string $user, string $password)
    {
        return config('expose.admin.users.'.$user) === $password;
    }
}
