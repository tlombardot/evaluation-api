<?php

namespace App\Exception\Order;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OrderNotFoundException extends HttpException
{
    public function __construct()
    {
        parent::__construct(
            Response::HTTP_NOT_FOUND,
            'Commande non trouvée',
        );
    }
}
