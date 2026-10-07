<?php

namespace IlBronza\AccountManager\Models\Traits;

use IlBronza\Notifications\Traits\ExtendedNotifiable;
use Illuminate\Notifications\Notifiable;

if (trait_exists(ExtendedNotifiable::class)) {
    trait RoleNotifiableTrait
    {
        use ExtendedNotifiable;
    }
} else {
    trait RoleNotifiableTrait
    {
        use Notifiable;
    }
}
