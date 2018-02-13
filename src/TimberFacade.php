<?php

namespace Liteweb\Timber\TimberLaravel;

class TimberFacade extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor()
    {
        return 'timber';
    }
}
