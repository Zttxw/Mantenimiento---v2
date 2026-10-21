<?php

namespace App\Exceptions\Auth;

use Exception;

class UserInactiveException extends Exception
{
    public function __construct()
    {
        parent::__construct('Tu cuenta esta desactivada. Contactate con OTI.');
    }
}
