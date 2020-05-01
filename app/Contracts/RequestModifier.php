<?php

namespace App\Contracts;

use App\Server\Connections\HttpConnection;
use Illuminate\Http\Request;

interface RequestModifier
{
    public function handle(Request $request, HttpConnection $httpConnection): ?Request;
}
