<?php

namespace App;

use Illuminate\Support\Str;

class FinalSteps
{
    public $errors = [];

    public function add(string $response): void
    {
        $this->errors[] = Str::of($response)->replace('<info>', '<span class="text-green-500">')->replace('</info>', '</span>');
    }

    public function all(): array
    {
        return $this->errors;
    }
}
