<?php

namespace App\Exception\Restaurant;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RestaurantNotFoundException extends HttpException
{
    public function __construct()
    {
        parent::__construct(
            Response::HTTP_NOT_FOUND,
            'Restaurant non trouvé',
        );
    }
}
