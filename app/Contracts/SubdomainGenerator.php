<?php

namespace App\Contracts;

interface SubdomainGenerator
{
    public function generateSubdomain(): string;
}
