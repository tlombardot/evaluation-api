<?php

namespace App\Exception\Order;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OrderRestaurantMismatchException extends HttpException
{
    public function __construct()
    {
        parent::__construct(
            Response::HTTP_CONFLICT,
            'Une commande ne mélange pas les restaurants',
        );
    }
}
