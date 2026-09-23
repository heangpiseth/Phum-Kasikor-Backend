<?php

namespace App\Enums;

enum UserRole: string {
    case CUSTOMER = 'customer';
    case FARMER = 'farmer';
}