<?php

namespace Citadel\Aureum\Core\Entity;

enum EmployeeRole: string
{
    case MANAGER = 'Manager';
    case RECEPTION = 'Reception';
    case CONCIERGE = 'Concierge';
    case NIGHTS = 'Nights';
}
