<?php

namespace HasanAlyazidi\Sendables;

use Illuminate\Support\Facades\Facade;

class SendablesFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'sendables';
    }
}
