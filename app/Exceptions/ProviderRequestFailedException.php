<?php

namespace App\Exceptions;

use RuntimeException;

class ProviderRequestFailedException extends RuntimeException
{
    public function __construct(public readonly int $httpStatus)
    {
        parent::__construct('Provider returned HTTP '.$httpStatus);
    }
}
