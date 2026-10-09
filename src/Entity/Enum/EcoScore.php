<?php

namespace App\Entity\Enum;

/**
 * Éco-score d'un plat, de A (le plus sobre) à E (le plus lourd).
 */
enum EcoScore: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';
    case E = 'E';
}
