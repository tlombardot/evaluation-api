<?php

namespace App\Entity\Enum;

enum DishCategory: string
{
    case Starter = 'starter';
    case Main = 'main';
    case Dessert = 'dessert';
}
