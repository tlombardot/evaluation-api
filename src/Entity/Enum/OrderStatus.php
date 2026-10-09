<?php

namespace App\Entity\Enum;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
}
