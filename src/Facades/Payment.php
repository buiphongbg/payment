<?php
/**
 * Created by Phong Bui.
 * Date: 16/11/2017
 * Time: 13:52
 */

namespace PhongBui\Payment\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Payment Facade
 *
 * @method static getPaymentUrl($orderId, $amount, $successUrl, $failedUrl, $cancelUrl)
 * @method static verifyResponse($params)
 * @package PhongBui\Payment\Facades
 */
class Payment extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'payment';
    }
}