<?php

namespace App\Exception\Dish;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DishNotFoundException extends HttpException
{
    public function __construct()
    {
        parent::__construct(
            Response::HTTP_NOT_FOUND,
            'Plat non trouvé',
        );
    }
}
