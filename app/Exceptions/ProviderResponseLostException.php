<?php

namespace App\Exceptions;

use RuntimeException;

class ProviderResponseLostException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Provider response lost. Confirmation outcome is unknown.');
    }
}
