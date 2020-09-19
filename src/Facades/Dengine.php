<?php

namespace Cmabugay\Dengine\Facades;

use Illuminate\Support\Facades\Facade;

class Dengine extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'dengine';
    }
}
