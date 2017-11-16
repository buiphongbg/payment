<?php
/**
 * Created by Phong Bui.
 * Date: 16/11/2017
 * Time: 13:52
 */

namespace PhongBui\Payment\Facades;

use Illuminate\Support\Facades\Facade;

class Payment extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'payment';
    }
}