<?php

namespace App\Server\SubdomainGenerator;

use Illuminate\Support\Str;
use App\Contracts\SubdomainGenerator;

class RandomSubdomainGenerator implements SubdomainGenerator
{
    public function generateSubdomain(): string
    {
        return strtolower(Str::random(10));
    }
}
