<?php
/**
 * Created by Phong Bui.
 * Date: 16/11/2017
 * Time: 13:57
 */

namespace PhongBui\Payment\Interfaces;

interface PaymentInterface
{
    public function getPaymentUrl($orderId, $amount, $successUrl, $failedUrl, $cancelUrl);

    public function verifyResponseUrl($params);
}