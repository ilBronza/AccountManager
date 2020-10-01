<?php

namespace IlBronza\AccountManager\Facades;

use Illuminate\Support\Facades\Facade;

class AccountManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'accountmanager';
    }
}
