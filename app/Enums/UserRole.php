<?php

namespace App\Enums;

enum UserRole: string
{
    case BUYER = 'buyer';
    case SELLER = 'seller';
    case DEALER = 'dealer';
    case ADMIN = 'admin';
}
