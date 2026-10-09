<?php

namespace App\Exception\User;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EmailAlreadyUsedException extends HttpException
{
    public function __construct()
    {
        parent::__construct(
            Response::HTTP_CONFLICT,
            'Adresse email déjà utilisée',
        );
    }
}
