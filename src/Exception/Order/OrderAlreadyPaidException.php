<?php

namespace App\Exception\Order;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OrderAlreadyPaidException extends HttpException
{
    public function __construct()
    {
        parent::__construct(
            Response::HTTP_CONFLICT,
            'Commande déjà payée',
        );
    }
}
