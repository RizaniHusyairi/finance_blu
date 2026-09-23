<?php

namespace App\Services\Btn;

use RuntimeException;

class BtnSnapException extends RuntimeException
{
    public function __construct(string $message, public readonly string $responseCode = '5000000')
    {
        parent::__construct($message);
    }
}
