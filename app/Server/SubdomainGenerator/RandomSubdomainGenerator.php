<?php

namespace App\Server\SubdomainGenerator;

use App\Contracts\SubdomainGenerator;
use Illuminate\Support\Str;

class RandomSubdomainGenerator implements SubdomainGenerator
{
    public function generateSubdomain(): string
    {
        return strtolower(Str::random(10));
    }
}
